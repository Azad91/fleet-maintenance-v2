<?php

namespace App\Services\Complaint;

use App\Enums\ComplaintStatus;
use App\Enums\Location;
use App\Models\Complaint;
use App\Models\ComplaintDetail;
use App\Models\Driver;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComplaintService
{
    /**
     * Resolved lazily in the constructor so tests that manually
     * construct this service (without a PDF dependency) keep
     * working. Production always goes through the container, which
     * injects a real instance.
     */
    protected ComplaintPdfService $pdfService;

    public function __construct(
        protected ComplaintStockService $stockService,
        protected ComplaintItemService $itemService,
        protected ComplaintStatusTransitionService $transitionService,
        ?ComplaintPdfService $pdfService = null,
    ) {
        $this->pdfService = $pdfService ?? app(ComplaintPdfService::class);
    }

    public function create(array $data, ?array $detallar = null, array $shikayet = []): Complaint
    {
        $this->applyLocationContext($data);

        $data['created_by'] = auth()->id();

        return DB::transaction(function () use ($data, $detallar, $shikayet) {
            $processedDetails = [];

            if (! empty($detallar) && is_array($detallar)) {
                $location = ($data['yer'] ?? null) instanceof Location
                    ? $data['yer']->value
                    : ($data['yer'] ?? 'garage');

                $processedDetails = $this->stockService->deductStock(
                    $detallar,
                    $location,
                    $data['service_vehicle_id'] ?? null
                );
            }

            $complaint = Complaint::create($data);

            if (! empty($processedDetails)) {
                $complaint->details()->createMany($processedDetails);
            }

            $this->itemService->syncItems($complaint, $shikayet, $data['complaint_type'] ?? null);

            return $complaint;
        });
    }

    public function update(Complaint $complaint, array $data, ?array $detallar = null, array $shikayet = []): Complaint
    {
        $this->applyLocationContext($data);

        $updated = DB::transaction(function () use ($complaint, $data, $detallar, $shikayet) {
            // ── CONCURRENCY GUARD ──
            // Lock the complaint row FIRST so concurrent updates on
            // the same complaint are serialized. Without this, two
            // admins editing the same card simultaneously each read
            // the same "old" details, each compute a stock diff
            // against those details, and each write back — corrupting
            // the stock ledger with a double restore + double deduct.
            $locked = Complaint::withoutGlobalScopes()
                ->whereKey($complaint->getKey())
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->firstOrFail();

            // Re-validate the transition INSIDE the lock so the
            // current status is the one we are actually transitioning
            // from, not a snapshot that could be stale by now.
            if (isset($data['status'])) {
                $this->transitionService->validateTransition($locked, $data['status']);
            }

            $oldDetails = $locked->details()->orderBy('id')->get()->toArray();

            // Historical complaints (imported for archival) must never
            // trigger a stock diff — their details were saved without
            // stock deduction, so "restoring" and "re-deducting" would
            // either corrupt inventory or double-count.
            $hasHistoricalDetails = collect($oldDetails)->contains(
                fn ($d) => ($d['source_type'] ?? '') === 'historical'
            );

            $processedDetails = null;

            if (! $hasHistoricalDetails && $detallar !== null && is_array($detallar)) {
                $location = ($data['yer'] ?? null) instanceof Location
                    ? $data['yer']->value
                    : ($data['yer'] ?? 'garage');

                $processedDetails = $this->stockService->syncStockDiff(
                    $oldDetails,
                    $detallar,
                    $location,
                    $data['service_vehicle_id'] ?? null,
                    $locked->service_vehicle_id
                );
            }

            $locked->update($data);

            // Historical complaints keep their original details untouched.
            if ($processedDetails !== null) {
                $this->syncDetails($locked, $processedDetails);
            }

            $this->itemService->syncItems($locked, $shikayet, $data['complaint_type'] ?? null);

            return $locked->fresh(['details', 'items']);
        });

        // The PDF (if any) now shows an outdated snapshot. Delete it
        // after the transaction commits so a rollback never removes
        // a still-valid file.
        $this->invalidatePdfAfterCommit($updated);

        return $updated;
    }

    public function close(Complaint $complaint, array $data): Complaint
    {
        return DB::transaction(function () use ($complaint, $data) {
            // ── CONCURRENCY GUARD ──
            // Lock the complaint row so two concurrent close attempts
            // are serialized. Without this, both could pass the
            // "isCompleted?" pre-check outside the transaction and
            // both succeed — the second one silently overwriting the
            // first one's end_date / end_time / closed_by values.
            $locked = Complaint::withoutGlobalScopes()
                ->whereKey($complaint->getKey())
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->firstOrFail();

            $this->transitionService->validateTransition(
                $locked,
                ComplaintStatus::Completed
            );

            $locked->update([
                'status' => ComplaintStatus::Completed,
                'end_date' => $data['end_date'],
                'end_time' => $data['end_time'],
                'work_done_by' => $data['work_done'],
                'closed_at' => now(),
                'closed_by' => auth()->id(),
            ]);

            return $locked;
        });
    }

    public function delete(Complaint $complaint): void
    {
        DB::transaction(function () use ($complaint) {
            // ── CONCURRENCY GUARD ──
            // Lock the complaint row so a concurrent update / close /
            // delete on the same row is serialized. Without this, a
            // delete could run in parallel with an update that has
            // already read the "old" details — both would restore
            // stock for the same rows, doubling the credited quantity.
            $locked = Complaint::withoutGlobalScopes()
                ->whereKey($complaint->getKey())
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                // Already deleted by another process. Nothing to do.
                return;
            }

            // Restore stock for all details. For road complaints, the
            // stock goes back to the specific service vehicle that was
            // linked to this complaint.
            if ($locked->details->isNotEmpty()) {
                $this->stockService->restoreStock(
                    $locked->details->toArray(),
                    $locked->service_vehicle_id
                );
            }

            // AUDIT NOTE
            // ----------
            // `$locked->details()->delete()` is a Builder-level bulk
            // delete — it bypasses Eloquent's `deleted` event, so the
            // Auditable trait writes NO log for the details. We take
            // an explicit audit snapshot first, mirroring the pattern
            // used by BusService::bulkDelete().
            //
            // The complaint row itself is deleted via `$locked->delete()`
            // (per-model), so its audit entry is written normally.
            $detailIds = $locked->details()->pluck('id')->all();

            if (! empty($detailIds)) {
                ComplaintDetail::auditBulkDelete($detailIds);
            }

            // Soft-delete details
            $locked->details()->delete();

            // Soft-delete complaint
            $locked->delete();
        });

        $this->invalidatePdfAfterCommit($complaint);
    }

    /**
     * Bulk soft-delete multiple complaints.
     *
     * Each complaint is deleted through the existing delete() method
     * so that stock restoration, detail cascades, and per-row audit
     * logging all run exactly as they do for single-row deletes.
     *
     * The IDs are processed in CHUNKS, each chunk inside its own
     * transaction. This is a deliberate trade-off:
     *
     *   - One giant transaction over 2000+ rows holds table locks
     *     for too long, blocks other users, and risks PHP timeouts.
     *   - One transaction per row would be correct but slow.
     *
     * Chunking gives us atomicity at a reasonable granularity while
     * keeping each transaction short. If a chunk fails, earlier
     * chunks stay committed — the operator sees a partial-success
     * count and can retry the remainder.
     *
     * Eager-loads `details` on every chunk to avoid an N+1 query
     * inside delete()->restoreStock().
     *
     * @param  array<int>  $ids
     * @return int Number of complaints actually deleted
     */
    public function bulkDelete(array $ids, int $chunkSize = 100): int
    {
        if (empty($ids)) {
            return 0;
        }

        $deleted = 0;

        foreach (array_chunk($ids, $chunkSize) as $chunk) {
            DB::transaction(function () use ($chunk, &$deleted) {
                $complaints = Complaint::with('details')
                    ->whereIn('id', $chunk)
                    ->get();

                foreach ($complaints as $complaint) {
                    $this->delete($complaint);
                    $deleted++;
                }
            });
        }

        return $deleted;
    }

    /**
     * Bulk soft-delete ALL complaints matching the given Eloquent query.
     *
     * Memory-safe: the ID stream is pulled from the database in
     * fixed-size chunks via chunkById().
     *
     * Chunk-level atomicity means a failure partway through leaves
     * earlier chunks committed. The caller receives the partial count
     * so it can show the operator what actually happened; the log
     * entry records which chunk failed.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Complaint>  $query
     * @return array{deleted: int, error: ?string}
     */
    public function bulkDeleteByQuery($query, int $chunkSize = 100): array
    {
        $deleted = 0;
        $error = null;

        $idQuery = $query->clone()
            ->reorder()
            ->setEagerLoads([])
            ->select('complaints.id');

        try {
            $idQuery->chunkById($chunkSize, function ($rows) use (&$deleted) {
                $ids = $rows->pluck('id')->all();

                if (empty($ids)) {
                    return;
                }

                DB::transaction(function () use ($ids, &$deleted) {
                    $complaints = Complaint::with('details')
                        ->whereIn('id', $ids)
                        ->get();

                    foreach ($complaints as $complaint) {
                        $this->delete($complaint);
                        $deleted++;
                    }
                });
            }, 'complaints.id', 'id');
        } catch (\Throwable $e) {
            // Preserve the partial count so the caller can report it.
            $error = $e->getMessage();

            Log::error('Bulk complaint delete aborted mid-run', [
                'deleted_so_far' => $deleted,
                'error' => $error,
                'user_id' => auth()->id(),
                'request_id' => Context::get('request_id'),
            ]);
        }

        return ['deleted' => $deleted, 'error' => $error];
    }

    /**
     * Register PDF invalidation to run after the enclosing transaction
     * commits.
     *
     * Rationale: delete() is called inside bulkDelete()'s chunk
     * transaction. If we deleted the PDF immediately and the chunk
     * later rolled back, the complaint would still exist but its PDF
     * would be gone — forcing a needless regeneration. DB::afterCommit()
     * defers the deletion until the outermost transaction commits.
     *
     * When there is no active transaction, the callback fires
     * immediately — which is the correct behavior for the standalone
     * update() / delete() paths.
     */
    protected function invalidatePdfAfterCommit(Complaint $complaint): void
    {
        DB::afterCommit(function () use ($complaint) {
            $this->invalidatePdf($complaint);
        });
    }

    /**
     * Best-effort removal of the stale PDF for a complaint.
     *
     * Failure is logged but never propagated: the primary operation
     * (update or delete) has already succeeded, and a leftover file
     * is strictly better than a rollback.
     */
    protected function invalidatePdf(Complaint $complaint): void
    {
        try {
            $this->pdfService->delete($complaint);
        } catch (\Throwable $e) {
            Log::warning('Failed to invalidate stale complaint PDF', [
                'complaint_id' => $complaint->id,
                'error' => $e->getMessage(),
                'request_id' => Context::get('request_id'),
            ]);
        }
    }

    /**
     * Normalise the driver + service vehicle fields based on the location.
     *
     * Rules:
     *   yer = road   → driver_id required (from form), driver_name filled
     *                  from the driver's full name; service_vehicle_id kept
     *                  exactly as the operator selected it (NEVER inferred)
     *   yer = garage → driver + service vehicle both cleared
     */
    private function applyLocationContext(array &$data): void
    {
        $location = $data['yer'] ?? null;

        $isRoad = $location === Location::Road->value
            || ($location instanceof Location && $location->isRoad());

        if ($isRoad) {
            if (! empty($data['driver_id'])) {
                $driver = Driver::active()->findOrFail($data['driver_id']);
                $data['driver_name'] = $driver->full_name;
            }

            return;
        }

        // Garage complaints have no driver and no service vehicle.
        $data['driver_id'] = null;
        $data['driver_name'] = null;
        $data['service_vehicle_id'] = null;
    }

    /**
     * In-place sync of complaint details.
     *
     * Rows are matched by their `code` value and updated in place (IDs preserved).
     * New codes are inserted, removed codes are soft-deleted.
     *
     * @param  array<int, array<string, mixed>>  $processedDetails
     */
    private function syncDetails(Complaint $complaint, array $processedDetails): void
    {
        // ==================== 0. PREPARE ====================
        // NOTE: this method keys details by their `code` value. If two
        // rows in $processedDetails share a code, the later one silently
        // overwrites the earlier. FormRequests reject this at the input
        // layer (see ComplaintStoreRequest/ComplaintUpdateRequest
        // withValidator), so this is a defense-in-depth assertion only.
        $existingByCode = $complaint->details()
            ->orderBy('id')
            ->get()
            ->keyBy('code');

        $newByCode = [];
        foreach ($processedDetails as $detail) {
            $code = $detail['code'] ?? null;
            if ($code !== null && $code !== '') {
                $newByCode[$code] = $detail;
            }
        }

        $existingCodes = $existingByCode->keys()->all();
        $newCodes = array_keys($newByCode);

        // ==================== 1. UPDATE EXISTING ====================
        $codesToUpdate = array_intersect($existingCodes, $newCodes);
        foreach ($codesToUpdate as $code) {
            /** @var ComplaintDetail $detail */
            $detail = $existingByCode[$code];
            $payload = $newByCode[$code];

            $detail->update($payload);
        }

        // ==================== 2. CREATE NEW ====================
        $codesToCreate = array_diff($newCodes, $existingCodes);
        $payloadsToCreate = [];
        foreach ($codesToCreate as $code) {
            $payloadsToCreate[] = $newByCode[$code];
        }
        if (! empty($payloadsToCreate)) {
            $complaint->details()->createMany($payloadsToCreate);
        }

        // ==================== 3. SOFT-DELETE REMOVED ====================
        $codesToDelete = array_diff($existingCodes, $newCodes);
        foreach ($codesToDelete as $code) {
            /** @var ComplaintDetail $detail */
            $detail = $existingByCode[$code];
            $detail->delete();
        }
    }
}

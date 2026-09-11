<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\Driver;
use Illuminate\Support\Facades\DB;

class ComplaintService
{
    public function __construct(
        protected ComplaintStockService $stockService,
        protected ComplaintItemService $itemService,
        protected ComplaintStatusTransitionService $transitionService
    ) {}

    public function create(array $data, ?array $detallar = null, array $shikayet = []): Complaint
    {
        $this->applyDriverContext($data);

        $data['created_by'] = auth()->id();

        return DB::transaction(function () use ($data, $detallar, $shikayet) {
            $processedDetails = [];
            if (! empty($detallar) && is_array($detallar)) {
                $processedDetails = $this->stockService->deductStock($detallar);
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
        if (isset($data['status'])) {
            $this->transitionService->validateTransition($complaint, $data['status']);
        }

        $this->applyDriverContext($data);

        return DB::transaction(function () use ($complaint, $data, $detallar, $shikayet) {
            // Snapshot old details for stock diff calculation
            $oldDetails = $complaint->details()->orderBy('id')->get()->toArray();

            $processedDetails = null;

            if ($detallar !== null && is_array($detallar)) {
                // syncStockDiff restores old stock and deducts new stock atomically
                $processedDetails = $this->stockService->syncStockDiff($oldDetails, $detallar);
            }

            $complaint->update($data);

            if ($processedDetails !== null) {
                $this->syncDetails($complaint, $processedDetails);
            }

            // Sync complaints (items) — always in-place
            $this->itemService->syncItems($complaint, $shikayet, $data['complaint_type'] ?? null);

            return $complaint->fresh(['details', 'items']);
        });
    }

    public function close(Complaint $complaint, array $data): Complaint
    {
        $this->transitionService->validateTransition($complaint, 'completed');

        $complaint->update([
            'status'       => 'completed',
            'end_date'     => $data['end_date'],
            'end_time'     => $data['end_time'],
            'work_done_by' => $data['work_done'],
            'closed_at'    => now(),
            'closed_by'    => auth()->id(),
        ]);

        return $complaint;
    }

    public function delete(Complaint $complaint): void
    {
        DB::transaction(function () use ($complaint) {
            // Restore stock for all details
            if ($complaint->details->isNotEmpty()) {
                $this->stockService->restoreStock($complaint->details->toArray());
            }

            // Soft-delete details
            $complaint->details()->delete();

            // Soft-delete complaint
            $complaint->delete();
        });
    }

    /**
     * Resolve the driver_name from driver_id when the complaint origin is 'road'.
     *
     * The database stores 'road' (English), not 'yol' (legacy Azerbaijani).
     * When the origin is 'garage' or the driver_id is missing, the driver
     * fields are explicitly cleared to prevent stale data.
     */
    private function applyDriverContext(array &$data): void
    {
        if (($data['yer'] ?? null) === 'road' && ! empty($data['driver_id'])) {
            $driver = Driver::active()->findOrFail($data['driver_id']);
            $data['driver_name'] = $driver->full_name;
        } else {
            $data['driver_id']   = null;
            $data['driver_name'] = null;
        }
    }

    /**
     * In-place sync of complaint details.
     *
     * Rows are matched by their `code` value and updated in place (IDs preserved).
     * New codes are inserted, removed codes are soft-deleted.
     *
     * Benefits of code-based matching:
     *   - Position changes do not create/delete rows
     *   - Audit history stays clean (only real changes are logged)
     *   - IDs are stable for external references
     *
     * @param  array<int, array<string, mixed>>  $processedDetails
     */
    private function syncDetails(Complaint $complaint, array $processedDetails): void
    {
        // ==================== 0. PREPARE ====================
        // Index existing details by `code`
        $existingByCode = $complaint->details()
            ->orderBy('id')
            ->get()
            ->keyBy('code');

        // Index new details by `code`
        $newByCode = [];
        foreach ($processedDetails as $detail) {
            $code = $detail['code'] ?? null;
            if ($code !== null && $code !== '') {
                $newByCode[$code] = $detail;
            }
        }

        $existingCodes = $existingByCode->keys()->all();
        $newCodes      = array_keys($newByCode);

        // ==================== 1. UPDATE EXISTING ====================
        $codesToUpdate = array_intersect($existingCodes, $newCodes);
        foreach ($codesToUpdate as $code) {
            /** @var \App\Models\ComplaintDetail $detail */
            $detail  = $existingByCode[$code];
            $payload = $newByCode[$code];

            // Laravel's update() only saves changed fields.
            $detail->update($payload);
        }

        // ==================== 2. CREATE NEW ====================
        $codesToCreate    = array_diff($newCodes, $existingCodes);
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
            /** @var \App\Models\ComplaintDetail $detail */
            $detail = $existingByCode[$code];
            $detail->delete();  // Triggers Auditable 'deleted' event
        }
    }
}

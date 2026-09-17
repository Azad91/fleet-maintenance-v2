<?php

namespace App\Services\Complaint;

use App\Enums\ComplaintStatus;
use App\Enums\Location;
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
        $this->applyLocationContext($data);

        $data['created_by'] = auth()->id();

        return DB::transaction(function () use ($data, $detallar, $shikayet) {
            $processedDetails = [];

            if (! empty($detallar) && is_array($detallar)) {
                // `yer` may arrive as a Location enum (from a model) or
                // as a plain string (from a form request). Normalise it
                // to a string before handing it to the stock service.
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
        if (isset($data['status'])) {
            $this->transitionService->validateTransition($complaint, $data['status']);
        }

        $this->applyLocationContext($data);

        return DB::transaction(function () use ($complaint, $data, $detallar, $shikayet) {
            // Snapshot old details for stock diff calculation
            $oldDetails = $complaint->details()->orderBy('id')->get()->toArray();

            $processedDetails = null;

            if ($detallar !== null && is_array($detallar)) {
                // Same normalisation as create(): `yer` may be an enum
                // or a string depending on the caller.
                $location = ($data['yer'] ?? null) instanceof Location
                    ? $data['yer']->value
                    : ($data['yer'] ?? 'garage');

                // syncStockDiff restores old stock and deducts new stock atomically.
                // The OLD vehicle id is passed separately so that a vehicle
                // change on edit still restores to the original source.
                $processedDetails = $this->stockService->syncStockDiff(
                    $oldDetails,
                    $detallar,
                    $location,
                    $data['service_vehicle_id'] ?? null,
                    $complaint->service_vehicle_id
                );
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
        $this->transitionService->validateTransition(
            $complaint,
            ComplaintStatus::Completed
        );

        $complaint->update([
            'status' => ComplaintStatus::Completed,
            'end_date' => $data['end_date'],
            'end_time' => $data['end_time'],
            'work_done_by' => $data['work_done'],
            'closed_at' => now(),
            'closed_by' => auth()->id(),
        ]);

        return $complaint;
    }

    public function delete(Complaint $complaint): void
    {
        DB::transaction(function () use ($complaint) {
            // Restore stock for all details. For road complaints, the
            // stock goes back to the specific service vehicle that was
            // linked to this complaint.
            if ($complaint->details->isNotEmpty()) {
                $this->stockService->restoreStock(
                    $complaint->details->toArray(),
                    $complaint->service_vehicle_id
                );
            }

            // Soft-delete details
            $complaint->details()->delete();

            // Soft-delete complaint
            $complaint->delete();
        });
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
            /** @var \App\Models\ComplaintDetail $detail */
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
            /** @var \App\Models\ComplaintDetail $detail */
            $detail = $existingByCode[$code];
            $detail->delete();
        }
    }
}

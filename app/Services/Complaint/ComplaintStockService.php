<?php

namespace App\Services\Complaint;

use App\Models\ServiceVehicle;
use App\Models\ServiceVehicleStock;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Handles stock changes caused by complaint details.
 *
 * ── SOURCE SELECTION ────────────────────────────────────────────────
 * The location of the complaint (`yer`) determines the source of parts:
 *
 *   yer = 'garage' → the source garage's own warehouse
 *   yer = 'road'   → the SPECIFIC service vehicle selected on the
 *                    complaint (service_vehicle_id)
 *
 * There is NO fallback. If the selected vehicle does not have enough
 * stock, the complaint write is rejected. Filling the service vehicle
 * is the warehouse officer's job — the complaint operator must not
 * silently pull from the warehouse instead.
 *
 * Legacy road complaints created before the service_vehicle_id column
 * existed have a NULL vehicle. Their stock cannot be restored
 * automatically on delete; this is logged at warning level rather than
 * silently dropped.
 */
class ComplaintStockService
{
    /**
     * Deduct stock for the given detail payloads.
     *
     * @param  array<int, array<string, mixed>>  $details
     * @param  string  $location        'road' | 'garage'
     * @param  int|null  $serviceVehicleId  Required when $location === 'road'
     * @return array<int, array<string, mixed>> Only rows that actually affect stock
     */
    public function deductStock(
        array $details,
        string $location = 'garage',
        ?int $serviceVehicleId = null
    ): array {
        $processed = [];

        foreach ($details as $detail) {
            $code = $detail['code'] ?? null;

            if (empty($code)) {
                continue;
            }

            $usedQuantity = (int) ($detail['used_quantity'] ?? 0);

            // ── Inspection / repair-only row ──
            if ($usedQuantity <= 0) {
                $processed[] = [
                    'shikayet_index' => $detail['shikayet_index'] ?? 0,
                    'code'           => $code,
                    'name'           => $detail['name'] ?? $code,
                    'stock_quantity' => 0,
                    'used_quantity'  => 0,
                    'price_at_use'   => 0,           // ← YENİ
                    'employee_id'    => $detail['employee_id'] ?? null,
                    'notes'          => $detail['notes'] ?? null,
                    'source_type'    => 'inspection',
                ];

                continue;
            }

            // ── Existing logic for consumed parts ──
            if ($location === 'road') {
                $processed[] = $this->deductFromServiceVehicle(
                    $detail, $code, $usedQuantity, $serviceVehicleId
                );
                continue;
            }

            $processed[] = $this->deductFromWarehouse($detail, $code, $usedQuantity);
        }

        return $processed;
    }

    /**
     * Restore stock for the given details (used on complaint delete
     * or when replacing details during an update).
     *
     * The `source_type` recorded on each detail decides where the
     * stock goes back to. For `service_vehicle` rows, the caller must
     * pass the complaint's `service_vehicle_id`.
     *
     * @param  array<int, array<string, mixed>>  $details
     * @param  int|null  $serviceVehicleId  Vehicle to credit when restoring
     */
    public function restoreStock(array $details, ?int $serviceVehicleId = null): void
    {
        foreach ($details as $detail) {
            $code = $detail['code'] ?? null;
            $usedQuantity = (int) ($detail['used_quantity'] ?? 0);

            if (empty($code) || $usedQuantity <= 0) {
                continue;
            }

            $sourceType = $detail['source_type'] ?? 'warehouse';

            // Historical imports and inspection rows never touched
            // stock on creation, so there is nothing to restore.
            if (in_array($sourceType, ['historical', 'inspection'], true)) {
                continue;
            }

            // ── Service vehicle: restore to the specific vehicle ──
            if ($sourceType === 'service_vehicle') {
                if ($serviceVehicleId === null) {
                    // Legacy road complaint created before the
                    // service_vehicle_id column existed — cannot
                    // restore automatically. Log and skip.
                    Log::warning('Service vehicle id missing — stock restore skipped', [
                        'code' => $code,
                        'quantity' => $usedQuantity,
                    ]);

                    continue;
                }

                $this->restoreToSpecificVehicle(
                    $code,
                    $usedQuantity,
                    $serviceVehicleId,
                    $detail['name'] ?? null
                );

                continue;
            }

            // ── Warehouse: restore to the garage warehouse ──
            $warehouse = Warehouse::where('code', $code)->lockForUpdate()->first();

            if ($warehouse) {
                $warehouse->increment('quantity', $usedQuantity);

                continue;
            }

            // Warehouse row not found — this should not happen if the
            // original deduction succeeded, but log it for safety.
            Log::warning('Warehouse row not found — stock restore skipped', [
                'code' => $code,
                'quantity' => $usedQuantity,
            ]);
        }
    }

    /**
     * Reconcile stock after an edit: restore old usage and deduct new usage.
     *
     * @param  array<int, array<string, mixed>>  $oldDetails
     * @param  array<int, array<string, mixed>>  $newDetails
     */
    public function syncStockDiff(
        array $oldDetails,
        array $newDetails,
        string $location = 'garage',
        ?int $newServiceVehicleId = null,
        ?int $oldServiceVehicleId = null
    ): array {
        // Step 1: restore everything the OLD details had taken.
        // The old details belong to the old complaint state — in practice
        // the vehicle is the same, but we accept both to be safe.
        $this->restoreStock($oldDetails, $oldServiceVehicleId ?? $newServiceVehicleId);

        // Step 2: deduct the NEW details from scratch.
        return $this->deductStock($newDetails, $location, $newServiceVehicleId);
    }

    // ==================== PRIVATE HELPERS ====================

    /**
     * Deduct from the garage warehouse. Throws if insufficient.
     */
    private function deductFromWarehouse(array $detail, string $code, int $usedQuantity): array
    {
        $warehouse = Warehouse::where('code', $code)->lockForUpdate()->first();

        if (! $warehouse) {
            throw ValidationException::withMessages([
                'details' => __('messages.flash.stock_item_not_found', ['code' => $code]),
            ]);
        }

        if ($warehouse->quantity < $usedQuantity) {
            throw ValidationException::withMessages([
                'details' => __('messages.flash.stock_insufficient', [
                    'name'      => $warehouse->name,
                    'requested' => $usedQuantity,
                    'available' => $warehouse->quantity,
                ]),
            ]);
        }

        $row = $this->buildProcessedRow(
            $detail,
            $code,
            $usedQuantity,
            'warehouse',
            $warehouse,
            null
        );

        $warehouse->quantity -= $usedQuantity;
        $warehouse->save();

        return $row;
    }

    /**
     * Deduct from the SPECIFIC service vehicle selected on the complaint.
     * Throws if the vehicle is not provided, not found, not in the
     * current garage, or has insufficient stock.
     *
     * ✅ SECURITY (defense-in-depth): the vehicle must belong to the
     * current garage context. The FormRequest already validates this,
     * but a future controller, queue job, or console command might
     * bypass the request layer. Re-checking here closes that gap.
     */
    private function deductFromServiceVehicle(
        array $detail,
        string $code,
        int $usedQuantity,
        ?int $serviceVehicleId
    ): array {
        if ($serviceVehicleId === null) {
            throw ValidationException::withMessages([
                'service_vehicle_id' => __('messages.flash.service_vehicle_required'),
            ]);
        }

        // Defense-in-depth: resolve the current garage and require the
        // vehicle to belong to it.
        $currentGarageId = \App\Services\GarageContext::resolveGarageId();

        $vehicleQuery = ServiceVehicle::withoutGlobalScopes()
            ->whereKey($serviceVehicleId);

        if ($currentGarageId !== null) {
            $vehicleQuery->where('garage_id', $currentGarageId);
        }

        $vehicle = $vehicleQuery->first();

        if (! $vehicle) {
            throw ValidationException::withMessages([
                'service_vehicle_id' => __('messages.flash.service_vehicle_not_found'),
            ]);
        }

        $stock = ServiceVehicleStock::withoutGlobalScopes()
            ->where('service_vehicle_id', $serviceVehicleId)
            ->where('code', $code)
            ->lockForUpdate()
            ->first();

        if (! $stock) {
            throw ValidationException::withMessages([
                'details' => __('messages.flash.service_vehicle_part_not_found', [
                    'code'    => $code,
                    'vehicle' => $vehicle->name,
                ]),
            ]);
        }

        if ($stock->quantity < $usedQuantity) {
            throw ValidationException::withMessages([
                'details' => __('messages.flash.service_vehicle_stock_insufficient', [
                    'name'      => $stock->name,
                    'vehicle'   => $vehicle->name,
                    'requested' => $usedQuantity,
                    'available' => $stock->quantity,
                ]),
            ]);
        }

        $row = $this->buildProcessedRow(
            $detail,
            $code,
            $usedQuantity,
            'service_vehicle',
            null,
            $stock
        );

        $stock->decrement('quantity', $usedQuantity);

        return $row;
    }

    /**
     * Give stock back to the specific service vehicle.
     *
     * If the vehicle no longer has a stock row for this code (e.g. it
     * was fully depleted and cleaned up), the row is re-created from
     * the detail's metadata so the quantity is never silently lost.
     */
    private function restoreToSpecificVehicle(
        string $code,
        int $quantity,
        int $serviceVehicleId,
        ?string $nameFromDetail = null
    ): void {
        $vehicle = ServiceVehicle::withoutGlobalScopes()->find($serviceVehicleId);

        if (! $vehicle) {
            Log::warning('Service vehicle not found — stock restore skipped', [
                'service_vehicle_id' => $serviceVehicleId,
                'code' => $code,
                'quantity' => $quantity,
            ]);

            return;
        }

        $stock = ServiceVehicleStock::withoutGlobalScopes()
            ->where('service_vehicle_id', $serviceVehicleId)
            ->where('code', $code)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            $stock->increment('quantity', $quantity);

            return;
        }

        // Recreate the row. Fall back to the warehouse row for a
        // canonical name/unit when the detail carried no name.
        $warehouse = Warehouse::withoutGlobalScopes()
            ->where('garage_id', $vehicle->garage_id)
            ->where('code', $code)
            ->first();

        ServiceVehicleStock::withoutGlobalScopes()->create([
            'service_vehicle_id' => $serviceVehicleId,
            'garage_id'          => $vehicle->garage_id,
            'company_id'         => $vehicle->company_id,
            'code'               => $code,
            'name'               => $warehouse->name ?? $nameFromDetail ?? $code,
            'unit'               => $warehouse->unit ?? null,
            'quantity'           => $quantity,
        ]);
    }

    /**
     * Build the row that will be persisted on complaint_details.
     */
    private function buildProcessedRow(
        array $detail,
        string $code,
        int $usedQuantity,
        string $sourceType,
        ?Warehouse $warehouse = null,
        ?ServiceVehicleStock $stock = null
    ): array {
        $name = $warehouse?->name ?? $stock?->name ?? $code;

        // Price snapshot — see migration 2026_09_18_120000
        $priceAtUse = $this->resolvePrice($warehouse, $stock, $code);

        return [
            'shikayet_index' => $detail['shikayet_index'] ?? 0,
            'code'           => $code,
            'name'           => $name,
            'stock_quantity' => $sourceType === 'warehouse'
                ? ($warehouse?->quantity ?? 0)
                : ($stock?->quantity ?? 0),
            'used_quantity'  => $usedQuantity,
            'price_at_use'   => $priceAtUse,
            'employee_id'    => $detail['employee_id'] ?? null,
            'notes'          => $detail['notes'] ?? null,
            'source_type'    => $sourceType,
        ];
    }

    /**
     * Resolve the unit price to snapshot for this detail row.
     *
     *   - warehouse source     → warehouse.price
     *   - service vehicle      → warehouse.price for the same code in the
     *                            same garage (vehicle stocks have no price)
     *   - unknown              → null
     */
    private function resolvePrice(
        ?Warehouse $warehouse,
        ?ServiceVehicleStock $stock,
        string $code
    ): ?float {
        if ($warehouse !== null) {
            return $warehouse->price !== null ? (float) $warehouse->price : null;
        }

        if ($stock !== null) {
            $fallback = Warehouse::withoutGlobalScopes()
                ->where('garage_id', $stock->garage_id)
                ->where('code', $code)
                ->whereNull('deleted_at')
                ->value('price');

            return $fallback !== null ? (float) $fallback : null;
        }

        return null;
    }
}

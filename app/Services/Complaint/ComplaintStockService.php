<?php

namespace App\Services\Complaint;

use App\Models\ServiceVehicleStock;
use App\Models\Warehouse;
use Illuminate\Validation\ValidationException;

/**
 * Handles stock changes caused by complaint details.
 *
 * Two sources are supported:
 *
 *   1. Warehouse       → the source garage's own warehouse (default)
 *   2. ServiceVehicle  → stock held on any service vehicle belonging
 *                        to the source garage
 *
 * Selection rule (Variant C):
 *   - For "garage" location: always use the warehouse.
 *   - For "road" location: try service vehicles first; if the
 *     total available across all service vehicles of the garage is
 *     insufficient, fall back to the warehouse.
 *
 * The chosen source is stored on each detail via `source_type` so
 * restore / sync operations know where to put the stock back.
 */
class ComplaintStockService
{
    /**
     * Deduct stock for the given detail payloads.
     *
     * @param  array<int, array<string, mixed>>  $details
     * @param  string  $location  'road' | 'garage'
     * @return array<int, array<string, mixed>> Only rows that actually affect stock
     */
    public function deductStock(array $details, string $location = 'garage'): array
    {
        $processed = [];

        foreach ($details as $detail) {
            $code = $detail['code'] ?? null;

            if (empty($code)) {
                continue;
            }

            $usedQuantity = (int) ($detail['used_quantity'] ?? 0);

            if ($usedQuantity <= 0) {
                continue;
            }

            // Road + service vehicle available → prefer service vehicle
            if ($location === 'road') {
                if ($this->tryServiceVehicle($code, $usedQuantity)) {
                    $processed[] = $this->buildProcessedRow($detail, $code, $usedQuantity, 'service_vehicle');

                    continue;
                }
            }

            // Fallback / default → warehouse
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

            $processed[] = $this->buildProcessedRow($detail, $code, $usedQuantity, 'warehouse', $warehouse);

            $warehouse->quantity -= $usedQuantity;
            $warehouse->save();
        }

        return $processed;
    }

    /**
     * Restore stock for the given details (used on complaint delete
     * or when replacing details during an update).
     *
     * The source_type recorded on each detail decides where the
     * stock goes back to.
     *
     * @param  array<int, array<string, mixed>>  $details
     */
    public function restoreStock(array $details): void
    {
        foreach ($details as $detail) {
            $code = $detail['code'] ?? null;
            $usedQuantity = (int) ($detail['used_quantity'] ?? 0);

            if (empty($code) || $usedQuantity <= 0) {
                continue;
            }

            $sourceType = $detail['source_type'] ?? 'warehouse';

            if ($sourceType === 'service_vehicle') {
                $this->restoreToServiceVehicle($code, $usedQuantity);

                continue;
            }

            $warehouse = Warehouse::where('code', $code)->lockForUpdate()->first();

            if ($warehouse) {
                $warehouse->quantity += $usedQuantity;
                $warehouse->save();
            }
        }
    }

    /**
     * Reconcile stock after an edit: restore old usage and deduct new usage.
     *
     * @param  array<int, array<string, mixed>>  $oldDetails
     * @param  array<int, array<string, mixed>>  $newDetails
     * @param  string  $location
     * @return array<int, array<string, mixed>> The processed new details
     */
    public function syncStockDiff(array $oldDetails, array $newDetails, string $location = 'garage'): array
    {
        // Step 1: restore everything the OLD details had taken.
        $this->restoreStock($oldDetails);

        // Step 2: deduct the NEW details from scratch.
        return $this->deductStock($newDetails, $location);
    }

    // ==================== PRIVATE HELPERS ====================

    /**
     * Try to take `$quantity` of `$code` from the service vehicles
     * of the current garage.
     *
     * Returns true when the quantity was successfully deducted.
     * Returns false (with no side effects) when the total across all
     * service vehicles is insufficient — the caller then falls back
     * to the warehouse.
     *
     * Deduction order: largest stock first, so a single vehicle is
     * drained before moving on. This keeps the split visible in the
     * service vehicle stock view.
     */
    private function tryServiceVehicle(string $code, int $quantity): bool
    {
        $stocks = ServiceVehicleStock::withoutGlobalScopes()
            ->where('code', $code)
            ->where('quantity', '>', 0)
            ->orderByDesc('quantity')
            ->lockForUpdate()
            ->get();

        if ($stocks->sum('quantity') < $quantity) {
            return false;
        }

        $remaining = $quantity;

        foreach ($stocks as $stock) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($stock->quantity, $remaining);
            $stock->decrement('quantity', $take);
            $remaining -= $take;
        }

        return true;
    }

    /**
     * Give stock back to the service vehicles of the current garage.
     *
     * The stock is added back to the largest existing row so a
     * single vehicle accumulates the returned quantity. If no row
     * exists at all, we cannot infer which vehicle to credit, so the
     * quantity is silently dropped — this only happens if a service
     * vehicle was deleted between the deduction and the restore.
     */
    private function restoreToServiceVehicle(string $code, int $quantity): void
    {
        $stock = ServiceVehicleStock::withoutGlobalScopes()
            ->where('code', $code)
            ->orderByDesc('quantity')
            ->lockForUpdate()
            ->first();

        if ($stock) {
            $stock->increment('quantity', $quantity);
        }
    }

    /**
     * Build the row that will be persisted on complaint_details.
     */
    private function buildProcessedRow(
        array $detail,
        string $code,
        int $usedQuantity,
        string $sourceType,
        ?Warehouse $warehouse = null
    ): array {
        $name = $warehouse?->name
            ?? ServiceVehicleStock::withoutGlobalScopes()->where('code', $code)->value('name')
            ?? $code;

        return [
            'shikayet_index' => $detail['shikayet_index'] ?? 0,
            'code'           => $code,
            'name'           => $name,
            'stock_quantity' => $sourceType === 'warehouse'
                ? ($warehouse?->quantity ?? 0)
                : ServiceVehicleStock::withoutGlobalScopes()->where('code', $code)->sum('quantity'),
            'used_quantity'  => $usedQuantity,
            'employee_id'    => $detail['employee_id'] ?? null,
            'notes'          => $detail['notes'] ?? null,
            'source_type'    => $sourceType,
        ];
    }
}

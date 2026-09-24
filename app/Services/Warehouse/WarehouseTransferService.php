<?php

namespace App\Services\Warehouse;

use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Models\Garage;
use App\Models\ServiceVehicleStock;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Business logic for warehouse transfers.
 *
 * All stock mutations happen inside a DB transaction with row-level
 * locking (lockForUpdate) so two concurrent transfers can never
 * oversell the same stock.
 *
 * The service is deliberately unaware of HTTP concerns — no
 * redirects, no session, no request. Controllers handle those.
 */
class WarehouseTransferService
{
    /**
     * Create a new transfer in draft state.
     *
     * @param  array{
     *     from_garage_id: int,
     *     to_garage_id?: int|null,
     *     to_service_vehicle_id?: int|null,
     *     type: string,
     *     notes?: string|null,
     *     items: array<int, array{warehouse_id: int, declared_quantity: int, notes?: string|null}>,
     * }  $data
     */
    public function create(array $data, int $companyId): WarehouseTransfer
    {
        return DB::transaction(function () use ($data, $companyId) {
            $type = TransferType::from($data['type']);

            // ─────────────────────────────────────────────────────────
            // SECURITY GUARD: cross-company / cross-garage destination
            //
            // The FormRequest already validates this, but we re-check
            // here as defense-in-depth. If a future controller, job,
            // or console command calls this service directly, it must
            // not be possible to inject stock into a foreign tenant.
            // ─────────────────────────────────────────────────────────
            if ($type->isGarageToGarage() && ! empty($data['to_garage_id'])) {
                $destinationGarage = Garage::withoutGlobalScopes()
                    ->whereKey($data['to_garage_id'])
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at')
                    ->first();

                if (! $destinationGarage) {
                    throw ValidationException::withMessages([
                        'to_garage_id' => __('validation.exists', [
                            'attribute' => __('messages.transfers.to_garage'),
                        ]),
                    ]);
                }
            }

            if ($type->isToServiceVehicle() && ! empty($data['to_service_vehicle_id'])) {
                $vehicleExists = \App\Models\ServiceVehicle::withoutGlobalScopes()
                    ->whereKey($data['to_service_vehicle_id'])
                    ->where('garage_id', $data['from_garage_id'])
                    ->whereNull('deleted_at')
                    ->exists();

                if (! $vehicleExists) {
                    throw ValidationException::withMessages([
                        'to_service_vehicle_id' => __('validation.exists', [
                            'attribute' => __('messages.transfers.to_service_vehicle'),
                        ]),
                    ]);
                }
            }

            // Item rows must exist and belong to the source garage.
            // We lock them now so nobody can delete them mid-create.
            $warehouseIds = array_column($data['items'], 'warehouse_id');

            $warehouses = Warehouse::withoutGlobalScopes()
                ->whereIn('id', $warehouseIds)
                ->where('garage_id', $data['from_garage_id'])
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($warehouses->count() !== count(array_unique($warehouseIds))) {
                throw ValidationException::withMessages([
                    'items' => __('messages.transfers.invalid_warehouse_items'),
                ]);
            }

            $total = 0;
            foreach ($data['items'] as $item) {
                $total += (int) $item['declared_quantity'];
            }

            $transfer = WarehouseTransfer::create([
                'company_id' => $companyId,
                'from_garage_id' => $data['from_garage_id'],
                'to_garage_id' => $type->isGarageToGarage() ? $data['to_garage_id'] : null,
                'to_service_vehicle_id' => $type->isToServiceVehicle() ? $data['to_service_vehicle_id'] : null,
                'type' => $type->value,
                'status' => TransferStatus::Draft->value,
                'declared_total' => $total,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $transfer->items()->create([
                    'warehouse_id' => $item['warehouse_id'],
                    'declared_quantity' => (int) $item['declared_quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $transfer->fresh(['items']);
        });
    }

    /**
     * Dispatch a draft transfer: decrement stock on the source garage
     * and move the transfer to "dispatched".
     *
     * ── CONCURRENCY GUARD ──
     * The transfer row is locked FIRST inside the transaction, and the
     * status check runs AFTER the lock is held. Two concurrent dispatch
     * requests for the same transfer are therefore serialized: the
     * second request sees the updated status (dispatched) and is
     * rejected before touching stock.
     *
     * Without this lock, both requests would pass the initial status
     * check, then both would decrement the source stock — a double-
     * deduction that corrupts inventory.
     *
     * Items are sorted by warehouse_id before any warehouse lock is
     * acquired to eliminate deadlocks between concurrent transfers
     * that reference the same warehouses in different orders.
     */
    public function dispatch(WarehouseTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            // ── Lock the transfer row FIRST ──
            $locked = WarehouseTransfer::withoutGlobalScopes()
                ->whereKey($transfer->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->status->isDraft()) {
                throw ValidationException::withMessages([
                    'status' => __('messages.transfers.cannot_dispatch'),
                ]);
            }

            $locked->load('items.warehouse');

            // ── Deterministic lock order for warehouses ──
            $sortedItems = $locked->items
                ->sortBy('warehouse_id')
                ->values();

            foreach ($sortedItems as $item) {
                $warehouse = Warehouse::withoutGlobalScopes()
                    ->where('id', $item->warehouse_id)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();

                if (! $warehouse) {
                    throw ValidationException::withMessages([
                        'items' => __('messages.flash.stock_item_not_found', [
                            'code' => $item->warehouse?->code ?? '—',
                        ]),
                    ]);
                }

                if ($warehouse->quantity < $item->declared_quantity) {
                    throw ValidationException::withMessages([
                        'items' => __('messages.flash.stock_insufficient', [
                            'name' => $warehouse->name,
                            'requested' => $item->declared_quantity,
                            'available' => $warehouse->quantity,
                        ]),
                    ]);
                }

                $warehouse->decrement('quantity', $item->declared_quantity);
            }

            $locked->update([
                'status' => TransferStatus::Dispatched->value,
                'dispatched_by' => auth()->id(),
                'dispatched_at' => now(),
            ]);
        });
    }

    /**
     * Receive a dispatched transfer.
     *
     * ── CONCURRENCY GUARD ──
     * Same pattern as dispatch(): the transfer row is locked first,
     * the status is re-checked under the lock. Two concurrent receive
     * requests would otherwise both pass the "isDispatched" check and
     * both increment destination stock — a double-credit.
     *
     * Destination warehouse rows are locked in `warehouse_id` order
     * to eliminate deadlocks between concurrent transfers.
     *
     * ── SURPLUS GUARD ──
     * The source garage only ever dispatched `declared` units, and
     * dispatch() already deducted exactly that many from the source
     * stock. Crediting MORE than `declared` to the destination would
     * create stock out of thin air — the exact same physical unit
     * would be counted in two places at once.
     *
     * @param  array<int, int>  $receivedQuantities  [transfer_item_id => received_qty]
     */
    public function receive(WarehouseTransfer $transfer, array $receivedQuantities): void
    {
        DB::transaction(function () use ($transfer, $receivedQuantities) {
            // ── Lock the transfer row FIRST ──
            $locked = WarehouseTransfer::withoutGlobalScopes()
                ->whereKey($transfer->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->status->isDispatched()) {
                throw ValidationException::withMessages([
                    'status' => __('messages.transfers.cannot_receive'),
                ]);
            }

            $locked->load('items');

            $receivedTotal = 0;
            $hasDiscrepancy = false;
            $discrepancies = [];

            // ── Deterministic lock order for destination warehouses ──
            $sortedItems = $locked->items
                ->sortBy('warehouse_id')
                ->values();

            foreach ($sortedItems as $item) {
                $received = $receivedQuantities[$item->id] ?? null;

                if ($received === null) {
                    throw ValidationException::withMessages([
                        'received' => __('messages.transfers.missing_received_quantity'),
                    ]);
                }

                $received = (int) $received;

                if ($received < 0) {
                    throw ValidationException::withMessages([
                        'received' => __('messages.transfers.negative_quantity'),
                    ]);
                }

                // ── SURPLUS GUARD ──
                // The source garage only ever dispatched `declared`
                // units and the dispatch step already deducted exactly
                // that many from the source stock. Crediting MORE than
                // `declared` to the destination would create stock out
                // of thin air — the exact same physical unit would be
                // counted in two places at once.
                if ($received > $item->declared_quantity) {
                    throw ValidationException::withMessages([
                        'received' => __('messages.transfers.received_exceeds_declared', [
                            'declared' => $item->declared_quantity,
                            'received' => $received,
                        ]),
                    ]);
                }

                $item->update(['received_quantity' => $received]);
                $receivedTotal += $received;

                $diff = $received - $item->declared_quantity;

                if ($diff !== 0) {
                    $hasDiscrepancy = true;

                    $itemLabel = $item->warehouse?->code
                        ? $item->warehouse->code.' — '.($item->warehouse->name ?? '')
                        : "Item #{$item->id}";

                    $discrepancies[] = sprintf(
                        '%s: declared %d, received %d (%+d)',
                        $itemLabel,
                        $item->declared_quantity,
                        $received,
                        $diff
                    );
                }

                if ($received > 0) {
                    $this->incrementDestinationStock($locked, $item->warehouse_id, $received);
                }
            }

            $newStatus = $hasDiscrepancy
                ? TransferStatus::Disputed->value
                : TransferStatus::Received->value;

            $locked->update([
                'status' => $newStatus,
                'received_total' => $receivedTotal,
                'discrepancy_notes' => $hasDiscrepancy
                    ? implode("\n", $discrepancies)
                    : null,
                'received_by' => auth()->id(),
                'received_at' => now(),
            ]);
        });
    }

    /**
     * Reject a dispatched transfer entirely.
     * All quantities are returned to the source garage.
     *
     * ── CONCURRENCY GUARD ──
     * The transfer row is locked first, the status is re-checked under
     * the lock. Two concurrent rejects would otherwise both restore the
     * source stock — a double-credit.
     *
     * Source warehouse rows are locked in `warehouse_id` order to
     * eliminate deadlocks.
     */
    public function reject(WarehouseTransfer $transfer, ?string $reason = null): void
    {
        DB::transaction(function () use ($transfer, $reason) {
            // ── Lock the transfer row FIRST ──
            $locked = WarehouseTransfer::withoutGlobalScopes()
                ->whereKey($transfer->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->status->isDispatched()) {
                throw ValidationException::withMessages([
                    'status' => __('messages.transfers.cannot_reject'),
                ]);
            }

            $locked->load('items');

            // ── Deterministic lock order for source warehouses ──
            $sortedItems = $locked->items
                ->sortBy('warehouse_id')
                ->values();

            foreach ($sortedItems as $item) {
                $warehouse = Warehouse::withoutGlobalScopes()
                    ->where('id', $item->warehouse_id)
                    ->lockForUpdate()
                    ->first();

                if ($warehouse) {
                    $warehouse->increment('quantity', $item->declared_quantity);
                }
            }

            $locked->update([
                'status' => TransferStatus::Rejected->value,
                'received_total' => 0,
                'discrepancy_notes' => $reason,
                'received_by' => auth()->id(),
                'received_at' => now(),
            ]);
        });
    }

    /**
     * Resolve a disputed transfer.
     *
     * ── CONCURRENCY GUARD ──
     * The transfer row is locked first, the status is re-checked under
     * the lock. Two concurrent resolves (one choosing `retransfer`, the
     * other `loss_accepted`) would otherwise both succeed and corrupt
     * the state — e.g. create TWO follow-up drafts.
     *
     * @param  string  $resolution  'retransfer' or 'loss_accepted'
     */
    public function resolveDisputed(WarehouseTransfer $transfer, string $resolution): void
    {
        DB::transaction(function () use ($transfer, $resolution) {
            // ── Lock the transfer row FIRST ──
            $locked = WarehouseTransfer::withoutGlobalScopes()
                ->whereKey($transfer->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->status->isDisputed()) {
                throw ValidationException::withMessages([
                    'status' => __('messages.transfers.cannot_resolve'),
                ]);
            }

            if (! in_array($resolution, ['retransfer', 'loss_accepted'], true)) {
                throw ValidationException::withMessages([
                    'resolution' => __('messages.transfers.invalid_resolution'),
                ]);
            }

            $locked->load('items');

            if ($resolution === 'retransfer') {
                $missingItems = [];
                $nullReceived = [];

                foreach ($locked->items as $item) {
                    if ($item->received_quantity === null) {
                        $nullReceived[] = $item->id;
                    }

                    $missing = $item->declared_quantity - ($item->received_quantity ?? 0);

                    if ($missing > 0) {
                        $missingItems[] = [
                            'warehouse_id' => $item->warehouse_id,
                            'declared_quantity' => $missing,
                            'notes' => __('messages.transfers.retransfer_note', [
                                'original' => $locked->id,
                            ]),
                        ];
                    }
                }

                if (! empty($nullReceived)) {
                    Log::warning('Disputed transfer has items with null received_quantity', [
                        'transfer_id' => $locked->id,
                        'item_ids' => $nullReceived,
                    ]);
                }

                if (empty($missingItems)) {
                    Log::info('Retransfer requested but no missing items found', [
                        'transfer_id' => $locked->id,
                    ]);
                } else {
                    // Nested create() acquires its own locks on source
                    // warehouses. Sorting is inherited from dispatch()'s
                    // deterministic lock order pattern.
                    $this->create([
                        'from_garage_id' => $locked->from_garage_id,
                        'to_garage_id' => $locked->to_garage_id,
                        'to_service_vehicle_id' => $locked->to_service_vehicle_id,
                        'type' => $locked->type->value,
                        'notes' => __('messages.transfers.retransfer_note', ['original' => $locked->id]),
                        'items' => $missingItems,
                    ], $locked->company_id);
                }
            }

            $locked->update([
                'status' => TransferStatus::Resolved->value,
                'resolution' => $resolution,
                'resolved_by' => auth()->id(),
                'resolved_at' => now(),
            ]);
        });
    }

    /**
     * Cancel a draft transfer. No stock has been touched at this point,
     * so nothing to restore.
     *
     * ── CONCURRENCY GUARD ──
     * Even though no stock is mutated, the status check must be
     * serialized: without the row lock, a concurrent dispatch could
     * transition the transfer to "dispatched" while this cancel() is
     * still deciding — and cancel() would then mark a dispatched
     * transfer as cancelled, orphaning the stock that was already
     * deducted by the other request.
     */
    public function cancel(WarehouseTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            // ── Lock the transfer row FIRST ──
            $locked = WarehouseTransfer::withoutGlobalScopes()
                ->whereKey($transfer->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->status->isDraft()) {
                throw ValidationException::withMessages([
                    'status' => __('messages.transfers.cannot_cancel'),
                ]);
            }

            $locked->update(['status' => TransferStatus::Cancelled->value]);
        });
    }

    // ==================== PRIVATE ====================

    /**
     * Increment the destination's stock for the given item.
     *
     * For garage-to-garage transfers, the destination warehouse row
     * is the SAME code within the destination garage — we look it up
     * or create it on the fly.
     */
    private function incrementDestinationStock(WarehouseTransfer $transfer, int $sourceWarehouseId, int $quantity): void
    {
        if (! $transfer->to_garage_id) {
            return; // Service-vehicle / quarantine: handled elsewhere.
        }

        $source = Warehouse::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->find($sourceWarehouseId);

        if (! $source) {
            return;
        }

        $destination = Warehouse::withoutGlobalScopes()
            ->where('garage_id', $transfer->to_garage_id)
            ->where('code', $source->code)
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->first();

        if ($destination) {
            $destination->increment('quantity', $quantity);

            return;
        }

        // The destination garage does not yet have this item.
        // Create it from the source row, preserving code/name/unit/
        // price/minimum_quantity so the two rows stay aligned.
        //
        // ✅ SECURITY: We set company_id from the DESTINATION garage,
        // not from the transfer's company_id. This keeps the warehouse
        // row internally consistent even if the transfer data were
        // somehow corrupted upstream.
        $destinationGarage = Garage::withoutGlobalScopes()
            ->whereKey($transfer->to_garage_id)
            ->first();

        Warehouse::withoutGlobalScopes()->create([
            'garage_id' => $transfer->to_garage_id,
            'company_id' => $destinationGarage?->company_id ?? $transfer->company_id,
            'code' => $source->code,
            'name' => $source->name,
            'category' => $source->category,
            'unit' => $source->unit,
            'quantity' => $quantity,
            'minimum_quantity' => $source->minimum_quantity,
            'price' => $source->price,
            'supplier' => $source->supplier,
        ]);
    }

    /**
     * Create AND immediately complete a transfer that does not
     * require the dispatch → receive workflow.
     *
     * Currently used for:
     *   - return_to_quarantine (within the same garage)
     *   - to_service_vehicle
     */
    public function createAndComplete(array $data, int $companyId): WarehouseTransfer
    {
        $type = TransferType::from($data['type']);

        if ($type->requiresWorkflow()) {
            throw ValidationException::withMessages([
                'type' => __('messages.transfers.type_requires_workflow'),
            ]);
        }

        return DB::transaction(function () use ($data, $companyId, $type) {
            // SECURITY: same-vehicle guard as in create().
            if ($type->isToServiceVehicle() && ! empty($data['to_service_vehicle_id'])) {
                $vehicleExists = \App\Models\ServiceVehicle::withoutGlobalScopes()
                    ->whereKey($data['to_service_vehicle_id'])
                    ->where('garage_id', $data['from_garage_id'])
                    ->whereNull('deleted_at')
                    ->exists();

                if (! $vehicleExists) {
                    throw ValidationException::withMessages([
                        'to_service_vehicle_id' => __('validation.exists', [
                            'attribute' => __('messages.transfers.to_service_vehicle'),
                        ]),
                    ]);
                }
            }

            $warehouseIds = array_column($data['items'], 'warehouse_id');

            $warehouses = Warehouse::withoutGlobalScopes()
                ->whereIn('id', $warehouseIds)
                ->where('garage_id', $data['from_garage_id'])
                ->where('is_quarantine', false)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($warehouses->count() !== count(array_unique($warehouseIds))) {
                throw ValidationException::withMessages([
                    'items' => __('messages.transfers.invalid_warehouse_items'),
                ]);
            }

            $total = 0;
            foreach ($data['items'] as $item) {
                $total += (int) $item['declared_quantity'];
            }

            $transfer = WarehouseTransfer::create([
                'company_id' => $companyId,
                'from_garage_id' => $data['from_garage_id'],
                'to_garage_id' => null,
                'to_service_vehicle_id' => $type->isToServiceVehicle() ? ($data['to_service_vehicle_id'] ?? null) : null,
                'type' => $type->value,
                'status' => TransferStatus::Received->value,
                'declared_total' => $total,
                'received_total' => $total,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
                'dispatched_by' => auth()->id(),
                'dispatched_at' => now(),
                'received_by' => auth()->id(),
                'received_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                $source = $warehouses->get($item['warehouse_id']);

                if ($source->quantity < $item['declared_quantity']) {
                    throw ValidationException::withMessages([
                        'items' => __('messages.flash.stock_insufficient', [
                            'name' => $source->name,
                            'requested' => $item['declared_quantity'],
                            'available' => $source->quantity,
                        ]),
                    ]);
                }

                $transfer->items()->create([
                    'warehouse_id' => $source->id,
                    'declared_quantity' => $item['declared_quantity'],
                    'received_quantity' => $item['declared_quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);

                $source->decrement('quantity', $item['declared_quantity']);

                if ($type->isReturnToQuarantine()) {
                    $this->moveToQuarantine($source, $item['declared_quantity']);
                } elseif ($type->isToServiceVehicle()) {
                    $this->moveToServiceVehicle(
                        $transfer->to_service_vehicle_id,
                        $source,
                        $item['declared_quantity']
                    );
                }
            }

            return $transfer->fresh(['items']);
        });
    }

    /**
     * Move `quantity` units of the given source warehouse item to its
     * matching quarantine row in the SAME garage.
     */
    private function moveToQuarantine(Warehouse $source, int $quantity): void
    {
        $quarantineCode = 'Q-'.$source->code;

        $quarantine = Warehouse::withoutGlobalScopes()
            ->where('garage_id', $source->garage_id)
            ->where('code', $quarantineCode)
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->first();

        if ($quarantine) {
            $quarantine->increment('quantity', $quantity);

            return;
        }

        Warehouse::withoutGlobalScopes()->create([
            'garage_id' => $source->garage_id,
            'company_id' => $source->company_id,
            'code' => $quarantineCode,
            'name' => $source->name,
            'category' => $source->category,
            'unit' => $source->unit,
            'is_quarantine' => true,
            'quantity' => $quantity,
            'minimum_quantity' => 0,
            'price' => $source->price,
            'supplier' => $source->supplier,
        ]);
    }

    /**
     * Move `quantity` units of the given source warehouse item to
     * the stock of the target service vehicle.
     */
    private function moveToServiceVehicle(int $serviceVehicleId, Warehouse $source, int $quantity): void
    {
        $stock = ServiceVehicleStock::withoutGlobalScopes()
            ->where('service_vehicle_id', $serviceVehicleId)
            ->where('code', $source->code)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            $stock->increment('quantity', $quantity);

            return;
        }

        ServiceVehicleStock::withoutGlobalScopes()->create([
            'service_vehicle_id' => $serviceVehicleId,
            'garage_id' => $source->garage_id,
            'company_id' => $source->company_id,
            'code' => $source->code,
            'name' => $source->name,
            'category' => $source->category,
            'unit' => $source->unit,
            'quantity' => $quantity,
        ]);
    }
}

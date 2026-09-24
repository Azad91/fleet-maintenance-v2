<?php

namespace App\Http\Requests;

use App\Models\WarehouseTransfer;
use Illuminate\Foundation\Http\FormRequest;

class WarehouseTransferReceiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'received' => 'required|array|min:1',
            'received.*' => 'required|integer|min:0',
        ];
    }

    /**
     * The base rules only validate each received value in isolation.
     * We must also enforce, per transfer item, that the received
     * quantity does NOT exceed the declared quantity.
     *
     * Allowing received > declared would create stock out of thin
     * air: the source garage only ever sent `declared` units, and the
     * dispatch step already deducted exactly that many from the source
     * warehouse. If the destination credited `received` (> declared)
     * to its own warehouse, the extra units would exist in two places
     * at once.
     *
     * Surplus handling (if it is ever needed) belongs to a dedicated
     * adjustment workflow, not to the receive form.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $transfer = $this->route('transfer');

            if (! $transfer instanceof WarehouseTransfer) {
                return;
            }

            // Map: item_id => declared_quantity
            $declared = $transfer->items()
                ->pluck('declared_quantity', 'id');

            $received = (array) $this->input('received', []);

            foreach ($received as $itemId => $qty) {
                $itemId = (int) $itemId;
                $qty = (int) $qty;

                if (! $declared->has($itemId)) {
                    // Unknown item id — service layer rejects this
                    // separately with a generic message.
                    continue;
                }

                $declaredQty = (int) $declared->get($itemId);

                if ($qty > $declaredQty) {
                    $validator->errors()->add(
                        "received.{$itemId}",
                        __('messages.transfers.received_exceeds_declared', [
                            'declared' => $declaredQty,
                            'received' => $qty,
                        ])
                    );
                }
            }
        });
    }
}

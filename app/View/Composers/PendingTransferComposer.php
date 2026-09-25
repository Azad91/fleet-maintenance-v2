<?php

namespace App\View\Composers;

use App\Enums\TransferStatus;
use App\Models\WarehouseTransfer;
use App\Services\GarageContext;
use Illuminate\View\View;

/**
 * Injects the count of transfers that require action from the
 * current garage into the main application layout.
 *
 * "Requires action" means:
 *   - Inbound  + status = dispatched → destination must receive
 *   - Outbound + status = disputed   → source must resolve
 *
 * Drafts and already-received transfers do NOT count — they are
 * not pending anyone's attention.
 */
class PendingTransferComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();

        // Directors operate at company level and have no garage context.
        if (! $user || $user->isDirector()) {
            $view->with('sidebarPendingTransfers', 0);

            return;
        }

        $garageId = GarageContext::resolveGarageId();

        if (! $garageId) {
            $view->with('sidebarPendingTransfers', 0);

            return;
        }

        $count = WarehouseTransfer::query()
            ->visibleToGarage($garageId)
            ->where(function ($q) use ($garageId) {
                // Inbound: dispatched, waiting for us to receive.
                $q->where(function ($sub) use ($garageId) {
                    $sub->where('to_garage_id', $garageId)
                        ->where('status', TransferStatus::Dispatched->value);
                })
                // Outbound: disputed, we (source) must resolve.
                ->orWhere(function ($sub) use ($garageId) {
                    $sub->where('from_garage_id', $garageId)
                        ->where('status', TransferStatus::Disputed->value);
                });
            })
            ->count();

        $view->with('sidebarPendingTransfers', $count);
    }
}

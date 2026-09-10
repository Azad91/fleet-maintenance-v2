<?php

namespace App\Services\Complaint;

use App\Models\Warehouse;
use Illuminate\Validation\ValidationException;

class ComplaintStockService
{
    public function deductStock(array $details): array
    {
        $processed = [];

        foreach ($details as $detail) {
            $code = $detail['code'] ?? null;
            if (empty($code)) {
                continue;
            }

            $warehouse = Warehouse::where('code', $code)->lockForUpdate()->first();

            if (! $warehouse) {
                throw ValidationException::withMessages([
                    'details' => __('messages.flash.stock_item_not_found', ['code' => $code]),
                ]);
            }

            $usedQuantity = (int) ($detail['used_quantity'] ?? 0);

            if ($warehouse->quantity < $usedQuantity) {
                throw ValidationException::withMessages([
                    'details' => __('messages.flash.stock_insufficient', [
                        'name'      => $warehouse->name,
                        'requested' => $usedQuantity,
                        'available' => $warehouse->quantity,
                    ]),
                ]);
            }

            $processed[] = [
                'shikayet_index' => $detail['shikayet_index'] ?? 0,
                'code'           => $code,
                'name'           => $warehouse->name,
                'stock_quantity' => $warehouse->quantity,
                'used_quantity'  => $usedQuantity,
                'employee_id'    => $detail['employee_id'] ?? null,
                'notes'          => $detail['notes'] ?? null,
            ];

            if ($usedQuantity > 0) {
                $warehouse->quantity -= $usedQuantity;
                $warehouse->save();
            }
        }

        return $processed;
    }

    public function restoreStock(array $details): void
    {
        foreach ($details as $detail) {
            $code = $detail['code'] ?? null;
            $usedQuantity = (int) ($detail['used_quantity'] ?? 0);

            if (empty($code) || $usedQuantity <= 0) {
                continue;
            }

            $warehouse = Warehouse::where('code', $code)->lockForUpdate()->first();
            if ($warehouse) {
                $warehouse->quantity += $usedQuantity;
                $warehouse->save();
            }
        }
    }

    public function syncStockDiff(array $oldDetails, array $newDetails): array
    {
        $oldUsage = [];
        foreach ($oldDetails as $detail) {
            $code = $detail['code'] ?? null;
            $qty = (int) ($detail['used_quantity'] ?? 0);
            if (! empty($code) && $qty > 0) {
                $oldUsage[$code] = ($oldUsage[$code] ?? 0) + $qty;
            }
        }

        $newUsage = [];
        foreach ($newDetails as $detail) {
            $code = $detail['code'] ?? null;
            $qty = (int) ($detail['used_quantity'] ?? 0);
            if (! empty($code) && $qty > 0) {
                $newUsage[$code] = ($newUsage[$code] ?? 0) + $qty;
            }
        }

        $allCodes = array_unique(array_merge(array_keys($oldUsage), array_keys($newUsage)));

        $warehouses = [];
        foreach ($allCodes as $code) {
            $oldQty = $oldUsage[$code] ?? 0;
            $newQty = $newUsage[$code] ?? 0;
            $diff = $newQty - $oldQty;

            $warehouse = Warehouse::where('code', $code)->lockForUpdate()->first();

            if ($diff > 0 && ! $warehouse) {
                throw ValidationException::withMessages([
                    'details' => __('messages.flash.stock_item_not_found', ['code' => $code]),
                ]);
            }

            if ($warehouse) {
                if ($diff > 0 && $warehouse->quantity < $diff) {
                    throw ValidationException::withMessages([
                        'details' => __('messages.flash.stock_insufficient', [
                            'name'      => $warehouse->name,
                            'requested' => $diff,
                            'available' => $warehouse->quantity,
                        ]),
                    ]);
                }

                if ($diff > 0) {
                    $warehouse->decrement('quantity', $diff);
                } elseif ($diff < 0) {
                    $warehouse->increment('quantity', abs($diff));
                }

                $warehouses[$code] = $warehouse->fresh();
            } else {
                $warehouses[$code] = null;
            }
        }

        $processed = [];
        foreach ($newDetails as $detail) {
            $code = $detail['code'] ?? null;
            if (empty($code)) {
                continue;
            }

            $warehouse = $warehouses[$code] ?? Warehouse::where('code', $code)->first();
            $usedQuantity = (int) ($detail['used_quantity'] ?? 0);

            $processed[] = [
                'shikayet_index' => $detail['shikayet_index'] ?? 0,
                'code'           => $code,
                'name'           => $warehouse?->name ?? ($detail['name'] ?? $code),
                'stock_quantity' => $warehouse?->quantity ?? 0,
                'used_quantity'  => $usedQuantity,
                'employee_id'    => $detail['employee_id'] ?? null,
                'notes'          => $detail['notes'] ?? null,
            ];
        }

        return $processed;
    }
}
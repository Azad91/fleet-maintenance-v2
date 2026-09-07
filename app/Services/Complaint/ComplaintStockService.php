<?php

namespace App\Services\Complaint;

use App\Models\Warehouse;
use Illuminate\Validation\ValidationException;

class ComplaintStockService
{
    public function deductStock(array $detallar): array
    {
        $processed = [];

        foreach ($detallar as $detal) {
            $code = $detal['code'] ?? $detal['kodu'] ?? null;
            if (empty($code)) {
                continue;
            }

            $warehouse = Warehouse::where('code', $code)->lockForUpdate()->first();

            if (! $warehouse) {
                throw ValidationException::withMessages([
                    'detallar' => "'{$code}' kodlu detal cari qarajın anbarında tapılmadı.",
                ]);
            }

            $usedQuantity = (int) ($detal['used_quantity'] ?? $detal['islenen_miqdar'] ?? 0);

            if ($warehouse->quantity < $usedQuantity) {
                throw ValidationException::withMessages([
                    'detallar' => "Anbarda kifayət qədər '{$warehouse->name}' yoxdur. (Tələb: {$usedQuantity}, Mövcud: {$warehouse->quantity})",
                ]);
            }

            $processed[] = [
                'shikayet_index' => $detal['shikayet_index'] ?? 0,
                'code' => $code,
                'name' => $warehouse->name,
                'stock_quantity' => $warehouse->quantity,
                'used_quantity' => $usedQuantity,
                'employee_id' => $detal['employee_id'] ?? null,
                'notes' => $detal['notes'] ?? $detal['qeyd'] ?? null,
            ];

            if ($usedQuantity > 0) {
                $warehouse->quantity -= $usedQuantity;
                $warehouse->save();
            }
        }

        return $processed;
    }

    public function restoreStock(array $detallar): void
    {
        foreach ($detallar as $detal) {
            $code = $detal['code'] ?? $detal['kodu'] ?? null;
            $usedQuantity = (int) ($detal['used_quantity'] ?? $detal['islenen_miqdar'] ?? 0);

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
        // 1. Köhnə və yeni miqdarları kod üzrə cəmlə
        $oldUsage = [];
        foreach ($oldDetails as $detail) {
            $code = $detail['code'] ?? $detail['kodu'] ?? null;
            $qty = (int) ($detail['used_quantity'] ?? $detail['islenen_miqdar'] ?? 0);
            if (! empty($code) && $qty > 0) {
                $oldUsage[$code] = ($oldUsage[$code] ?? 0) + $qty;
            }
        }

        $newUsage = [];
        foreach ($newDetails as $detail) {
            $code = $detail['code'] ?? $detail['kodu'] ?? null;
            $qty = (int) ($detail['used_quantity'] ?? $detail['islenen_miqdar'] ?? 0);
            if (! empty($code) && $qty > 0) {
                $newUsage[$code] = ($newUsage[$code] ?? 0) + $qty;
            }
        }

        $allCodes = array_unique(array_merge(array_keys($oldUsage), array_keys($newUsage)));

        // 2. Diff hesabla və anbarı yenilə
        $warehouses = [];
        foreach ($allCodes as $code) {
            $oldQty = $oldUsage[$code] ?? 0;
            $newQty = $newUsage[$code] ?? 0;
            $diff = $newQty - $oldQty; // Müsbət = əlavə silinməli, Mənfi = geri qaytarılmalı

            $warehouse = Warehouse::where('code', $code)->lockForUpdate()->first();
            if (! $warehouse && $diff > 0) {
                throw ValidationException::withMessages([
                    'detallar' => "'{$code}' kodlu detal cari qarajın anbarında tapılmadı.",
                ]);
            }

            if ($warehouse) {
                if ($diff > 0 && $warehouse->quantity < $diff) {
                    throw ValidationException::withMessages([
                        'detallar' => "Anbarda kifayət qədər '{$warehouse->name}' yoxdur. (Tələb olunan əlavə: {$diff}, Mövcud: {$warehouse->quantity})",
                    ]);
                }

                if ($diff > 0) {
                    $warehouse->decrement('quantity', $diff);
                } elseif ($diff < 0) {
                    $warehouse->increment('quantity', abs($diff));
                }

                $warehouses[$code] = $warehouse->fresh();
            }
        }

        // 3. Yeni detallar siyahısını hazırla
        $processed = [];
        foreach ($newDetails as $detal) {
            $code = $detal['code'] ?? $detal['kodu'] ?? null;
            if (empty($code)) {
                continue;
            }

            $warehouse = $warehouses[$code] ?? Warehouse::where('code', $code)->first();
            $usedQuantity = (int) ($detal['used_quantity'] ?? $detal['islenen_miqdar'] ?? 0);

            $processed[] = [
                'shikayet_index' => $detal['shikayet_index'] ?? 0,
                'code' => $code,
                'name' => $warehouse?->name ?? ($detal['name'] ?? $detal['adi'] ?? $code),
                'stock_quantity' => $warehouse?->quantity ?? 0,
                'used_quantity' => $usedQuantity,
                'employee_id' => $detal['employee_id'] ?? null,
                'notes' => $detal['notes'] ?? $detal['qeyd'] ?? null,
            ];
        }

        return $processed;
    }
}

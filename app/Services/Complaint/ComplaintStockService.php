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
                    'detallar' => "'{$code}' kodlu detal cari qarajın anbarında tapılmadı."
                ]);
            }

            $usedQuantity = (int) ($detal['used_quantity'] ?? $detal['islenen_miqdar'] ?? 0);

            if ($warehouse->quantity < $usedQuantity) {
                throw ValidationException::withMessages([
                    'detallar' => "Anbarda kifayət qədər '{$warehouse->name}' yoxdur. (Tələb: {$usedQuantity}, Mövcud: {$warehouse->quantity})"
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
}

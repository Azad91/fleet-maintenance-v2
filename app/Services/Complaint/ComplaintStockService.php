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
            if (empty($detal['kodu'])) {
                continue;
            }

            $warehouse = Warehouse::where('code', $detal['kodu'])->lockForUpdate()->first(); // kod → code

            if (! $warehouse) {
                throw ValidationException::withMessages([
                    'detallar' => "'{$detal['kodu']}' kodlu detal cari qarajın anbarında tapılmadı."
                ]);
            }

            $usedQuantity = (int) ($detal['islenen_miqdar'] ?? 0);

            if ($warehouse->quantity < $usedQuantity) { // miqdar → quantity
                throw ValidationException::withMessages([
                    'detallar' => "Anbarda kifayət qədər '{$warehouse->name}' yoxdur. (Tələb: {$usedQuantity}, Mövcud: {$warehouse->quantity})" // ad → name
                ]);
            }

            $processed[] = [
                'shikayet_index' => $detal['shikayet_index'] ?? 0,
                'kodu' => $detal['kodu'],
                'adi' => $warehouse->name,   // ad → name
                'depo_miqdari' => $warehouse->quantity, // miqdar → quantity
                'islenen_miqdar' => $usedQuantity,
                'employee_id' => $detal['employee_id'] ?? null,
                'qeyd' => $detal['qeyd'] ?? null,
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
            if (empty($detal['kodu']) || empty($detal['islenen_miqdar'])) {
                continue;
            }

            $warehouse = Warehouse::where('code', $detal['kodu'])->lockForUpdate()->first(); // kod → code
            if ($warehouse) {
                $warehouse->quantity += $detal['islenen_miqdar']; // miqdar → quantity
                $warehouse->save();
            }
        }
    }
}

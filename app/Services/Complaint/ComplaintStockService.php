<?php

namespace App\Services\Complaint;

use App\Models\Warehouse;
use Illuminate\Validation\ValidationException;

class ComplaintStockService
{
    /**
     * Anbardan detalları çıxar
     */
    public function deductStock(array $detallar): array
    {
        $processed = [];

        foreach ($detallar as $detal) {
            if (empty($detal['kodu'])) {
                continue;
            }

            $warehouse = Warehouse::where('kod', $detal['kodu'])->lockForUpdate()->first();

            if (! $warehouse) {
                throw ValidationException::withMessages([
                    'detallar' => "'{$detal['kodu']}' kodlu detal cari qarajın anbarında tapılmadı."
                ]);
            }

            $usedQuantity = (int) ($detal['islenen_miqdar'] ?? 0);

            if ($warehouse->miqdar < $usedQuantity) {
                throw ValidationException::withMessages([
                    'detallar' => "Anbarda kifayət qədər '{$warehouse->ad}' yoxdur. (Tələb: {$usedQuantity}, Mövcud: {$warehouse->miqdar})"
                ]);
            }

            $processed[] = [
                'shikayet_index' => $detal['shikayet_index'] ?? 0,
                'kodu' => $detal['kodu'],
                'adi' => $warehouse->ad,
                'depo_miqdari' => $warehouse->miqdar,
                'islenen_miqdar' => $usedQuantity,
                'employee_id' => $detal['employee_id'] ?? null,
                'qeyd' => $detal['qeyd'] ?? null,
            ];

            if ($usedQuantity > 0) {
                $warehouse->miqdar -= $usedQuantity;
                $warehouse->save();
            }
        }

        return $processed;
    }

    /**
     * Detalları anbara geri qaytar
     */
    public function restoreStock(array $detallar): void
    {
        foreach ($detallar as $detal) {
            if (empty($detal['kodu']) || empty($detal['islenen_miqdar'])) {
                continue;
            }

            $warehouse = Warehouse::where('kod', $detal['kodu'])->lockForUpdate()->first();
            if ($warehouse) {
                $warehouse->miqdar += $detal['islenen_miqdar'];
                $warehouse->save();
            }
        }
    }
}

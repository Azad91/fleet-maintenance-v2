<?php

namespace App\Imports;

use App\Models\Warehouse;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Row;
use Illuminate\Support\Facades\Log;

class WarehouseImport implements OnEachRow, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public function onRow(Row $row)
    {
        $rowArray = $row->toArray();

        // 🔥 KODU TAM TƏMİZLƏ
        $kod = trim((string) ($rowArray['kod'] ?? ''));

        // 🔥 Əgər kod boşdursa, keç
        if (empty($kod)) {
            Log::warning('Boş kod sətri keçildi');
            return;
        }

        // 🔥 Cari qaraj və şirkət ID-lərini al
        $garageId = session('current_garage_id');
        $companyId = session('current_company_id');

        // 🔥 Kodu bazada axtar (tam uyğun)
        $warehouse = Warehouse::where('kod', $kod)->first();

        // 🔥 LOG: Nə tapıldığını yoxla
        Log::info('Kod axtarışı:', [
            'kod' => $kod,
            'tapildi' => $warehouse ? 'Bəli' : 'Xeyr',
            'warehouse_id' => $warehouse ? $warehouse->id : null,
        ]);

        if ($warehouse) {
            // ✅ Mövcuddursa - yenilə
            $warehouse->update([
                'miqdar' => $warehouse->miqdar + (int) ($rowArray['miqdar'] ?? 0),
                'qiymet' => $rowArray['qiymet'] ?? $warehouse->qiymet,
                'ad' => $rowArray['ad'] ?? $warehouse->ad,
                'olcu_vahidi' => $rowArray['olcu_vahidi'] ?? $warehouse->olcu_vahidi,
                'garage_id' => $garageId ?? $warehouse->garage_id,
                'company_id' => $companyId ?? $warehouse->company_id,
            ]);
        } else {
            // 🆕 Yeni məhsul yarat
            try {
                Warehouse::create([
                    'kod' => $kod,
                    'ad' => $rowArray['ad'] ?? '',
                    'miqdar' => (int) ($rowArray['miqdar'] ?? 0),
                    'olcu_vahidi' => $rowArray['olcu_vahidi'] ?? null,
                    'qiymet' => (float) ($rowArray['qiymet'] ?? 0),
                    'garage_id' => $garageId,
                    'company_id' => $companyId,
                ]);
            } catch (\Exception $e) {
                Log::error('Yeni məhsul yaradılmadı:', [
                    'kod' => $kod,
                    'xəta' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    }

    public function rules(): array
    {
        return [
            'kod' => 'required',
            'ad' => 'required|string|max:255',
        ];
    }
}

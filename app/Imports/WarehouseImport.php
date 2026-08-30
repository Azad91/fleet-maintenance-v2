<?php

namespace App\Imports;

use App\Models\Warehouse;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Row;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WarehouseImport implements OnEachRow, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public function onRow(Row $row)
    {
        $rowArray = $row->toArray();

        $kod = trim((string) ($rowArray['kod'] ?? ''));

        if (empty($kod)) {
            Log::warning('Boş kod sətri keçildi');
            return;
        }

        $garageId = session('current_garage_id');
        $companyId = session('current_company_id');

        DB::transaction(function () use ($kod, $rowArray, $garageId, $companyId) {
            $warehouse = Warehouse::where('kod', $kod)->lockForUpdate()->first();

            if ($warehouse) {
                // ✅ ƏVƏZ ET: miqdarı toplama əvəzinə birbaşa təyin et
                $warehouse->update([
                    'miqdar' => (int) ($rowArray['miqdar'] ?? 0),
                    'qiymet' => isset($rowArray['qiymet']) ? (float) $rowArray['qiymet'] : $warehouse->qiymet,
                    'ad' => $rowArray['ad'] ?? $warehouse->ad,
                    'olcu_vahidi' => $rowArray['olcu_vahidi'] ?? $warehouse->olcu_vahidi,
                ]);
            } else {
                Warehouse::create([
                    'kod' => $kod,
                    'ad' => $rowArray['ad'] ?? '',
                    'miqdar' => (int) ($rowArray['miqdar'] ?? 0),
                    'olcu_vahidi' => $rowArray['olcu_vahidi'] ?? null,
                    'qiymet' => (float) ($rowArray['qiymet'] ?? 0),
                    'garage_id' => $garageId,
                    'company_id' => $companyId,
                ]);
            }
        });
    }

    public function rules(): array
    {
        return [
            'kod' => 'required|string|max:255',
            'ad' => 'required|string|max:255',
            'miqdar' => 'nullable|numeric|min:0',
            'qiymet' => 'nullable|numeric|min:0',
            'olcu_vahidi' => 'nullable|string|max:50',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'kod.required' => 'Kod sütunu boş ola bilməz.',
            'ad.required' => 'Ad sütunu boş ola bilməz.',
            'miqdar.numeric' => 'Miqdar yalnız rəqəm ola bilər.',
            'miqdar.min' => 'Miqdar 0-dan kiçik ola bilməz.',
            'qiymet.numeric' => 'Qiymət yalnız rəqəm ola bilər.',
            'qiymet.min' => 'Qiymət 0-dan kiçik ola bilməz.',
        ];
    }
}

<?php

namespace App\Imports;

use App\Models\Bus;
use App\Models\Complaint;
use App\Models\Warehouse;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ComplaintsImport implements OnEachRow, WithHeadingRow, WithValidation
{
public function onRow(Row $row)
{
    $rowArray = $row->toArray();

    $bus = Bus::where('dqn', $rowArray['bus_dqn'])->first();
    if (!$bus) {
        return;
    }

    DB::transaction(function () use ($rowArray, $bus) {
        $detallar = [];
        if (!empty($rowArray['detal_kodu']) && !empty($rowArray['islenen_miqdar']) && $rowArray['islenen_miqdar'] > 0) {
            $warehouse = Warehouse::where('kod', $rowArray['detal_kodu'])->lockForUpdate()->first();

            if (!$warehouse) {
                throw ValidationException::withMessages(['detal_kodu' => 'Detal cari qarajın anbarında tapılmadı.']);
            }

            $quantity = (int) $rowArray['islenen_miqdar'];
            if ($quantity > $warehouse->miqdar) {
                throw ValidationException::withMessages(['islenen_miqdar' => "Anbarda kifayət qədər '{$warehouse->ad}' yoxdur."]);
            }

            $warehouse->decrement('miqdar', $quantity);
            $detallar[] = [
                'kodu' => $rowArray['detal_kodu'],
                'adi' => $rowArray['detal_adi'] ?? $warehouse->ad,
                'depo_miqdari' => $warehouse->miqdar,
                'islenen_miqdar' => $quantity,
                'qeyd' => $rowArray['qeyd'] ?? null,
                'shikayet_index' => 0,
            ];
        }

        Complaint::create([
            'bus_id' => $bus->id,
            'yer' => $rowArray['yer'] ?? null,
            'surucu_adi' => $rowArray['surucu_adi'] ?? null,
            'shikayet' => $rowArray['shikayet'] ?? null,
            'sikayet_tipi' => $rowArray['sikayet_tipi'] ?? null,
            'bildirilme_tarix' => $rowArray['bildirilme_tarix'] ?? null,
            'bildirilme_saat' => $rowArray['bildirilme_saat'] ?? null,
            'is_baslama_tarix' => $rowArray['is_baslama_tarix'] ?? null,
            'is_baslama_saat' => $rowArray['is_baslama_saat'] ?? null,
            'is_bitme_tarix' => $rowArray['is_bitme_tarix'] ?? null,
            'is_bitme_saat' => $rowArray['is_bitme_saat'] ?? null,
            'status' => $rowArray['status'] ?? 'gözləmədə',
            'detallar' => $detallar,
            'km' => $rowArray['km'] ?? null,
            'kim_is_gorub' => $rowArray['kim_is_gorub'] ?? null,
        ]);
    });
}

    public function rules(): array
    {
        return [
            'bus_dqn' => 'required',
            'status' => 'nullable|in:gözləmədə,işdə,həll olundu',
            'yer' => 'nullable|in:yol,qaraj',
            'sikayet_tipi' => 'nullable|in:qezali,nasazliq,texniki_xidmet',
            'km' => 'nullable|integer|min:0',
        ];
    }
}

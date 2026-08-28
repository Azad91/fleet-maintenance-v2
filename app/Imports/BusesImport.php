<?php

namespace App\Imports;

use App\Models\Bus;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BusesImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $dqn = trim($row['dqn'] ?? '');

        if (empty($dqn)) {
            return null;
        }

        // 🔥 QARAJ ID AVTOMATİK YAZ
        $garageId = session('current_garage_id');
        $companyId = session('current_company_id');

        return Bus::updateOrCreate(
            ['dqn' => $dqn],
            [
                'garage_id' => $garageId,
                'company_id' => $companyId,
                'bus_project' => $row['bus_project'] ?? null,
                'vin' => $row['vin'] ?? null,
                'uzunluq' => $row['uzunluq'] ?? null,
                'xett_no' => $row['xett'] ?? null,
                'motor_no' => $row['motor'] ?? null,
                'tarix' => now()->format('Y-m-d'),
                'aktiv' => true,
            ]
        );
    }
}

<?php

namespace App\Imports;

use App\Models\Bus;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Validation\ValidationException;

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

        $existingInAnotherGarage = Bus::withoutGlobalScopes()
            ->where('dqn', $dqn)
            ->where('garage_id', '!=', $garageId)
            ->exists();

        if ($existingInAnotherGarage) {
            throw ValidationException::withMessages([
                'file' => "DQN {$dqn} başqa qaraja aiddir və idxal edilə bilməz.",
            ]);
        }

        $bus = Bus::withTrashed()->where('dqn', $dqn)->where('garage_id', $garageId)->first();
        if ($bus?->trashed()) {
            $bus->restore();
        }
        $bus ??= new Bus();

        $bus->fill([
            'garage_id' => $garageId,
            'company_id' => $companyId,
            'dqn' => $dqn,
            'bus_project' => $row['bus_project'] ?? null,
            'vin' => $row['vin'] ?? null,
            'uzunluq' => $row['uzunluq'] ?? null,
            'xett_no' => $row['xett'] ?? null,
            'motor_no' => $row['motor'] ?? null,
            'tarix' => now()->format('Y-m-d'),
            'aktiv' => true,
        ]);

        return $bus;
    }
}

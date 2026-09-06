<?php

namespace App\Imports;

use App\Models\Bus;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Validation\ValidationException;

class BusesImport implements ToModel, WithHeadingRow, ShouldQueue, WithChunkReading
{
    public function __construct(
        public ?int $garageId = null,
        public ?int $companyId = null
    ) {
        $this->garageId ??= session('current_garage_id');
        $this->companyId ??= session('current_company_id');
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function model(array $row)
    {
        $dqn = trim($row['dqn'] ?? '');

        if (empty($dqn)) {
            return null;
        }

        $garageId = $this->garageId;
        $companyId = $this->companyId;

        $existingInAnotherGarage = Bus::withoutGlobalScopes()
            ->where('dqn', $dqn)
            ->when($garageId, fn($q) => $q->where('garage_id', '!=', $garageId))
            ->exists();

        if ($existingInAnotherGarage) {
            throw ValidationException::withMessages([
                'file' => "DQN {$dqn} başqa qaraja aiddir və idxal edilə bilməz.",
            ]);
        }

        $bus = Bus::withoutGlobalScopes()
            ->withTrashed()
            ->where('dqn', $dqn)
            ->when($garageId, fn($q) => $q->where('garage_id', $garageId))
            ->first();

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
            'route_number' => $row['route_number'] ?? $row['xett'] ?? $row['xett_no'] ?? null,
            'engine_number' => $row['engine_number'] ?? $row['motor'] ?? $row['motor_no'] ?? null,
            'date' => now()->format('Y-m-d'),
            'is_active' => true,
        ]);

        return $bus;
    }
}


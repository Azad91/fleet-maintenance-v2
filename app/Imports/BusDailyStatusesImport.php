<?php

namespace App\Imports;

use App\Models\Bus;
use App\Models\BusDailyStatus;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class BusDailyStatusesImport implements ToModel, WithHeadingRow, ShouldQueue, WithChunkReading
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
        $dqn = $row['dqn'] ?? $row['DQN'] ?? null;
        $durum = $row['status'] ?? $row['durum'] ?? $row['STATUS'] ?? $row['DURUM'] ?? null;

        if (empty($dqn)) {
            return null;
        }

        $garageId = $this->garageId;
        $companyId = $this->companyId;

        $bus = Bus::withoutGlobalScopes()
            ->where('dqn', trim((string) $dqn))
            ->when($garageId, fn($q) => $q->where('garage_id', $garageId))
            ->first();

        if (!$bus) {
            return null;
        }

        return BusDailyStatus::withoutGlobalScopes()->updateOrCreate(
            [
                'bus_id' => $bus->id,
                'date'  => now()->toDateString(),
            ],
            [
                'garage_id' => $bus->garage_id ?? $garageId,
                'company_id' => $bus->company_id ?? $companyId,
                'status' => $durum ?? 'MƏLUMAT YOXDUR',
                'notes'   => $row['notes'] ?? $row['qeyd'] ?? null,
            ]
        );
    }
}


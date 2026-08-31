<?php

namespace App\Imports;

use App\Models\Bus;
use App\Models\BusDailyStatus;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Carbon\Carbon;

class BusDailyStatusesImport implements ToModel, WithHeadingRow, ShouldQueue, WithChunkReading
{
    public function chunkSize(): int
    {
        return 100;
    }

    public function model(array $row)
    {
        $dqn = $row['dqn'] ?? $row['DQN'] ?? null;
        $durum = $row['durum'] ?? $row['DURUM'] ?? null;

        if (empty($dqn)) {
            return null;
        }

        $bus = Bus::where('dqn', trim($dqn))->first();

        if (!$bus) {
            return null;
        }

        return BusDailyStatus::updateOrCreate(
            [
                'bus_id' => $bus->id,
                'tarix'  => now()->toDateString(),
            ],
            [
                'status' => $durum ?? 'MƏLUMAT YOXDUR',
                'qeyd'   => null,
            ]
        );
    }
}

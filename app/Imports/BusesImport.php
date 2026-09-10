<?php

namespace App\Imports;

use App\Models\Bus;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BusesImport implements ToModel, WithChunkReading, WithHeadingRow
{
    /**
     * Manual olaraq atlanan sətirlər.
     *
     * @var array<int, array{row: int, dqn: string, reason: string}>
     */
    public array $skipped = [];

    /**
     * Uğurla idxal olunan sətir sayı.
     */
    public int $importedCount = 0;

    /**
     * Sətir sayğacı (chunk-lar arasında davam edir).
     */
    private int $rowCounter = 0;

    public function __construct(
        public int $garageId,
        public ?int $companyId = null
    ) {}

    public function chunkSize(): int
    {
        return 100;
    }

    public function model(array $row)
    {
        $this->rowCounter++;
        $currentRow = $this->rowCounter + 1; // +1 çünki heading row

        $dqn = trim($row['dqn'] ?? '');

        if (empty($dqn)) {
            $this->skipped[] = [
                'row'    => $currentRow,
                'dqn'    => '—',
                'reason' => 'DQN boşdur',
            ];
            return null;
        }

        $garageId = $this->garageId;
        $companyId = $this->companyId;

        $existingInAnotherGarage = Bus::withoutGlobalScopes()
            ->where('dqn', $dqn)
            ->where('garage_id', '!=', $garageId)
            ->exists();

        if ($existingInAnotherGarage) {
            $this->skipped[] = [
                'row'    => $currentRow,
                'dqn'    => $dqn,
                'reason' => 'DQN başqa qaraja aiddir',
            ];
            return null;
        }

        $bus = Bus::withoutGlobalScopes()
            ->withTrashed()
            ->where('dqn', $dqn)
            ->when($garageId, fn ($q) => $q->where('garage_id', $garageId))
            ->first();

        if ($bus?->trashed()) {
            $bus->restore();
        }
        $bus ??= new Bus;

        $bus->fill([
            'garage_id'      => $garageId,
            'company_id'     => $companyId,
            'dqn'            => $dqn,
            'bus_project'    => $row['bus_project'] ?? null,
            'vin'            => $row['vin'] ?? null,
            'uzunluq'        => $row['uzunluq'] ?? null,
            'route_number'   => $row['route_number'] ?? $row['xett'] ?? $row['xett_no'] ?? null,
            'engine_number'  => $row['engine_number'] ?? $row['motor'] ?? $row['motor_no'] ?? null,
            'date'           => now()->format('Y-m-d'),
            'is_active'      => true,
            'km'             => isset($row['km']) ? (int) $row['km'] : null,
        ]);

        $this->importedCount++;

        return $bus;
    }
}

<?php

namespace App\Imports;

use App\Models\Bus;
use App\Models\BusDailyStatus;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BusDailyStatusesImport implements ToModel, WithChunkReading, WithHeadingRow
{
    public array $skipped = [];
    public int $importedCount = 0;
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
        $currentRow = $this->rowCounter + 1;

        $dqn = trim((string) ($row['dqn'] ?? ''));
        $status = $row['status'] ?? null;

        if (empty($dqn)) {
            $this->skipped[] = [
                'row'    => $currentRow,
                'dqn'    => '—',
                'reason' => __('messages.imports.reasons.dqn_empty'),
            ];
            return null;
        }

        $bus = Bus::withoutGlobalScopes()
            ->where('dqn', $dqn)
            ->when($this->garageId, fn ($q) => $q->where('garage_id', $this->garageId))
            ->first();

        if (! $bus) {
            $this->skipped[] = [
                'row'    => $currentRow,
                'dqn'    => $dqn,
                'reason' => __('messages.imports.reasons.dqn_not_found'),
            ];
            return null;
        }

        $record = BusDailyStatus::withoutGlobalScopes()->updateOrCreate(
            [
                'bus_id' => $bus->id,
                'date'   => now()->toDateString(),
            ],
            [
                'garage_id'  => $bus->garage_id ?? $this->garageId,
                'company_id' => $bus->company_id ?? $this->companyId,
                'status'     => $status ?? 'NO DATA',
                'notes'      => $row['notes'] ?? null,
            ]
        );

        $this->importedCount++;

        return $record;
    }
}
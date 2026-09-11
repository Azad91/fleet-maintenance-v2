<?php

namespace App\Imports;

use App\Models\Bus;
use App\Models\BusDailyStatus;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

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

        $dqn    = trim((string) ($row['dqn'] ?? ''));
        $status = $row['status'] ?? null;

        if (empty($dqn)) {
            $this->skipped[] = [
                'row'    => $currentRow,
                'dqn'    => '—',
                'reason' => __('messages.imports.reasons.dqn_empty'),
            ];
            return null;
        }

        // Resolve the date: prefer explicit 'date' column, fall back to today.
        $date = $this->resolveDate($row['date'] ?? $row['tarix'] ?? null);

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
                'date'   => $date,
            ],
            [
                'garage_id'  => $bus->garage_id ?? $this->garageId,
                'company_id' => $bus->company_id ?? $this->companyId,
                'status'     => $status ?? 'NO DATA',
                'notes'      => $row['notes'] ?? $row['qeyd'] ?? null,
            ]
        );

        $this->importedCount++;

        return $record;
    }

    /**
     * Convert the incoming date value to a Y-m-d string.
     *
     * Handles:
     *   - Excel serial numbers (e.g. 45000)
     *   - DateTimeInterface instances
     *   - ISO / local date strings (e.g. "2026-09-11", "11.09.2026")
     *   - Empty / invalid values → today
     */
    private function resolveDate(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        // Excel serial number
        if (is_numeric($value) && (float) $value > 20000 && (float) $value < 100000) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
            } catch (\Throwable $e) {
                // Fall through to today
            }
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                return Carbon::parse(trim($value))->toDateString();
            } catch (\Throwable $e) {
                // Fall through to today
            }
        }

        return now()->toDateString();
    }
}

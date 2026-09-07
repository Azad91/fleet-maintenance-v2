<?php

namespace App\Imports;

use App\Models\Bus;
use App\Models\DailyKmRecord;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class DailyKmRecordsImport implements ToCollection, WithCalculatedFormulas, ShouldQueue, WithChunkReading
{
    public function __construct(
        public int $garageId,
        public ?int $companyId = null
    ) {}

    public function chunkSize(): int
    {
        return 100;
    }

    public function collection(Collection $rows): void
    {
        if ($rows->count() < 3) {
            return;
        }

        $dateRow = $rows->get(0);
        $dailyKmColumns = [];

        foreach ($dateRow as $column => $value) {
            $date = $this->toDate($value);
            if ($date !== null) {
                $dailyKmColumns[$column + 2] = $date;
            }
        }

        $garageId = $this->garageId;

        foreach ($rows->slice(2) as $row) {
            $dqn = trim((string) ($row[3] ?? ''));
            if ($dqn === '') {
                continue;
            }

            $bus = Bus::withoutGlobalScopes()
                ->where('dqn', $dqn)
                ->when($garageId, fn($q) => $q->where('garage_id', $garageId))
                ->first();

            if (! $bus) {
                continue;
            }

            foreach ($dailyKmColumns as $column => $dateVal) {
                $km = $row[$column] ?? null;
                if (! is_numeric($km) || (int) $km <= 0) {
                    continue;
                }

                DailyKmRecord::withoutGlobalScopes()->updateOrCreate(
                    [
                        'bus_id' => $bus->id,
                        'date' => $dateVal,
                    ],
                    [
                        'km' => (int) $km,
                        'notes' => $row['notes'] ?? $row['qeyd'] ?? null,
                        'garage_id' => $bus->garage_id ?? $garageId,
                        'company_id' => $bus->company_id ?? $companyId,
                    ]
                );
            }
        }
    }

    private function toDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        if (is_numeric($value) && (float) $value > 20000) {
            return Carbon::instance(Date::excelToDateTimeObject((float) $value))->toDateString();
        }

        return null;
    }
}

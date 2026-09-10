<?php

namespace App\Imports;

use App\Models\Bus;
use App\Models\DailyKmRecord;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class DailyKmRecordsImport implements ToCollection, WithCalculatedFormulas, WithChunkReading
{
    public array $skipped = [];
    public int $importedCount = 0;

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
        if ($rows->isEmpty()) {
            return;
        }

        $isFirstChunk = false;
        if ($rows->count() > 1) {
            $colC = strtolower(trim((string) ($rows->get(1)[2] ?? '')));
            if ($colC === 'dqn') {
                $isFirstChunk = true;
            }
        }

        $cacheKey = 'km_mapping_' . $this->garageId . '_' . ($this->companyId ?? 0);

        if ($isFirstChunk) {
            $dateRow   = $rows->get(0)->toArray();
            $headerRow = $rows->get(1)->toArray();

            $kmColumns = [];

            foreach ($headerRow as $index => $value) {
                $val = trim(strtolower((string) $value));

                if ($val === 'km') {
                    $dateVal = null;
                    for ($k = $index; $k >= 0; $k--) {
                        if (! empty(trim((string) ($dateRow[$k] ?? '')))) {
                            $dateVal = $dateRow[$k];
                            break;
                        }
                    }

                    if ($dateVal) {
                        $parsedDate = $this->toDate($dateVal);
                        if ($parsedDate) {
                            $kmColumns[$index] = $parsedDate;
                        }
                    }
                }
            }

            Cache::put($cacheKey, $kmColumns, now()->addHours(1));

            $dataRows = $rows->slice(2);
        } else {
            $kmColumns = Cache::get($cacheKey, []);
            $dataRows = $rows;
        }

        if (empty($kmColumns)) {
            return;
        }

        foreach ($dataRows as $row) {
            $dqn = trim((string) ($row[2] ?? ''));
            if ($dqn === '') {
                continue; // Boş sətirlər üçün skip report yazmırıq (çox gürültü olur)
            }

            $bus = Bus::withoutGlobalScopes()
                ->where('dqn', $dqn)
                ->when($this->garageId, fn ($q) => $q->where('garage_id', $this->garageId))
                ->first();

            if (! $bus) {
                $this->skipped[] = [
                    'row'    => '—',
                    'dqn'    => $dqn,
                    'reason' => 'Bu DQN cari qarajın avtobus siyahısında yoxdur',
                ];
                continue;
            }

            foreach ($kmColumns as $colIndex => $dateString) {
                $km = $row[$colIndex] ?? null;

                if (! is_numeric($km) || (int) $km <= 0) {
                    continue;
                }

                DailyKmRecord::withoutGlobalScopes()->updateOrCreate(
                    [
                        'bus_id' => $bus->id,
                        'date'   => $dateString,
                    ],
                    [
                        'km'         => (int) $km,
                        'garage_id'  => $bus->garage_id ?? $this->garageId,
                        'company_id' => $bus->company_id ?? $this->companyId,
                    ]
                );

                $this->importedCount++;
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

        if (is_string($value) && ! empty(trim($value))) {
            try {
                return Carbon::parse(trim($value))->toDateString();
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }
}

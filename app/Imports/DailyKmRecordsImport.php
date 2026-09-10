<?php

namespace App\Imports;

use App\Models\Bus;
use App\Models\DailyKmRecord;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class DailyKmRecordsImport implements ShouldQueue, ToCollection, WithCalculatedFormulas, WithChunkReading
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
        if ($rows->isEmpty()) {
            return;
        }

        // Bunun birinci blok (başlıqların olduğu) və ya sonrakı sətirlər olduğunu təyin edirik
        $isFirstChunk = false;
        if ($rows->count() > 1) {
            // Excel-in 2-ci sətrində C sütunu (indeks 2) 'dqn'-dirsə, deməli bu ilk hissədir
            $colC = strtolower(trim((string)($rows->get(1)[2] ?? '')));
            if ($colC === 'dqn') {
                $isFirstChunk = true;
            }
        }

        $cacheKey = 'km_mapping_' . $this->garageId . '_' . ($this->companyId ?? 0);

        if ($isFirstChunk) {
            $dateRow = $rows->get(0)->toArray();
            $headerRow = $rows->get(1)->toArray();

            $kmColumns = [];

            // 2-ci sətri (alt başlıqları) axtarıb 'km' sütunlarını tapırıq
            foreach ($headerRow as $index => $value) {
                $val = trim(strtolower((string) $value));

                if ($val === 'km') {
                    // Tapdığımız 'km' sütununun üstündəki (1-ci sətirdəki) aid olduğu tarixi tapmaq üçün geriyə baxırıq
                    $dateVal = null;
                    for ($k = $index; $k >= 0; $k--) {
                        if (!empty(trim((string)($dateRow[$k] ?? '')))) {
                            $dateVal = $dateRow[$k];
                            break;
                        }
                    }

                    if ($dateVal) {
                        $parsedDate = $this->toDate($dateVal);
                        if ($parsedDate) {
                            $kmColumns[$index] = $parsedDate; // Məsələn: [5 => '2026-01-01', 8 => '2026-01-02']
                        }
                    }
                }
            }

            // Gələcək sətir blokları (chunk) üçün bu sütun xəritəsini yaddaşda (cache) saxlayırıq
            Cache::put($cacheKey, $kmColumns, now()->addHours(1));

            // Verilənləri 3-cü sətirdən (indeks 2) emal etməyə başlayırıq
            $dataRows = $rows->slice(2);
        } else {
            // Əgər 100 sətirdən çoxdursa, sonrakı hissələr üçün tarix xəritəsini yaddaşdan götürürük
            $kmColumns = Cache::get($cacheKey, []);
            $dataRows = $rows;
        }

        if (empty($kmColumns)) {
            return; // Etibarlı KM sütunu tapılmadı
        }

        $garageId = $this->garageId;

        // Bütün verilənləri (Dataları) bazaya yazırıq
        foreach ($dataRows as $row) {
            // Excel-də C sütunu (indeks 2) DQN saxlayır
            $dqn = trim((string) ($row[2] ?? ''));
            if ($dqn === '') {
                continue;
            }

            $bus = Bus::withoutGlobalScopes()
                ->where('dqn', $dqn)
                ->when($garageId, fn ($q) => $q->where('garage_id', $garageId))
                ->first();

            if (! $bus) {
                continue;
            }

            // Aşkarlanan hər bir tarix sütunu üzrə km dəyərini oxuyub bazaya yazırıq
            foreach ($kmColumns as $colIndex => $dateString) {
                $km = $row[$colIndex] ?? null;

                if (!is_numeric($km) || (int) $km <= 0) {
                    continue;
                }

                DailyKmRecord::withoutGlobalScopes()->updateOrCreate(
                    [
                        'bus_id' => $bus->id,
                        'date' => $dateString,
                    ],
                    [
                        'km' => (int) $km,
                        'garage_id' => $bus->garage_id ?? $garageId,
                        'company_id' => $bus->company_id ?? $this->companyId,
                    ]
                );
            }
        }
    }

    // Tarixləri formatlamaq üçün universal köməkçi
    private function toDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        if (is_numeric($value) && (float) $value > 20000) {
            return Carbon::instance(Date::excelToDateTimeObject((float) $value))->toDateString();
        }

        if (is_string($value) && !empty(trim($value))) {
            try {
                return Carbon::parse(trim($value))->toDateString();
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }
}

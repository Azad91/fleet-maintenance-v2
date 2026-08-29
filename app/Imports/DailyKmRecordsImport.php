<?php

namespace App\Imports;

use App\Models\Bus;
use App\Models\DailyKmRecord;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class DailyKmRecordsImport implements ToCollection, WithCalculatedFormulas
{
    /**
     * Source layout: row 1 has dates, row 2 has daily headings, and row 3+
     * stores buses. DQN is column D; the KM is the third column of each day.
     */
    public function collection(Collection $rows): void
    {
        if ($rows->count() < 3) {
            return;
        }

        $dateRow = $rows->get(0);
        $dailyKmColumns = [];

        foreach ($dateRow as $column => $value) {
            $date = $this->toDate($value);
            // Date is at the start of a four-column daily block. The KM cell
            // in the date row is intentionally empty; actual values begin on
            // the vehicle rows, two columns to the right.
            if ($date !== null) {
                $dailyKmColumns[$column + 2] = $date;
            }
        }

        foreach ($rows->slice(2) as $row) {
            $dqn = trim((string) ($row[3] ?? ''));
            if ($dqn === '') {
                continue;
            }

            $bus = Bus::where('dqn', $dqn)->first();
            if (! $bus) {
                continue;
            }

            foreach ($dailyKmColumns as $column => $tarix) {
                $km = $row[$column] ?? null;
                if (! is_numeric($km) || (int) $km <= 0) {
                    continue;
                }

                // Eyni avtobus və tarix üçün qeyd sistem üzrə təkdir. Qaraj
                // scope-u köhnə qeydi gizlətməsin deyə onu qlobal tapırıq və
                // avtobusun hazırkı qaraj/şirkət kontekstinə keçiririk.
                DailyKmRecord::withoutGlobalScopes()->updateOrCreate(
                    ['bus_id' => $bus->id, 'tarix' => $tarix],
                    [
                        'km' => (int) $km,
                        'garage_id' => $bus->garage_id,
                        'company_id' => $bus->company_id,
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

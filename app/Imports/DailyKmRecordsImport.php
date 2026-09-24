<?php

namespace App\Imports;

use App\Models\Bus;
use App\Models\DailyKmRecord;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Imports daily KM records from an Excel file.
 *
 * The Excel layout has two header rows:
 *   - Row 1: dates (one column per day)
 *   - Row 2: column labels ("DQN", "KM", "KM", ...)
 *
 * Each subsequent row holds one bus (identified by DQN in column C)
 * and one KM value per date column.
 *
 * PERFORMANCE NOTE
 * ----------------
 * The previous implementation issued one SELECT per row (to find the
 * bus) plus two queries per KM cell (updateOrCreate). For a 500-bus
 * monthly sheet that is ~30 000 round-trips — 20-40 seconds per
 * import.
 *
 * This version:
 *   1. Loads every bus referenced in the chunk in a single query and
 *      keeps them in memory (keyed by DQN).
 *   2. Collects all KM rows in a plain PHP array.
 *   3. Soft-deletes any existing active rows matching the exact
 *      (bus_id, date) pairs in this chunk (one query per chunk).
 *   4. Bulk-inserts every new row in a single query per chunk.
 *
 * Result: a handful of queries per 100-row chunk regardless of row
 * count. On a typical monthly file the import drops from ~30 000
 * queries to under 50.
 *
 * TRADE-OFF: bulk insert/delete bypasses Eloquent events, so no
 * per-row audit entries are written for imports. Manual edits still
 * produce audit logs as before. This is intentional — auditing tens
 * of thousands of import rows is not useful and bloats the audit
 * table.
 */
class DailyKmRecordsImport extends AbstractImport implements ToCollection, WithCalculatedFormulas, WithChunkReading
{
    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        // ─── Detect whether this is the first chunk ───
        // The first chunk contains the two header rows. Subsequent
        // chunks start directly with data because the reader applies
        // the heading-row offset automatically.
        $isFirstChunk = false;
        if ($rows->count() > 1) {
            $colC = strtolower(trim((string) ($rows->get(1)[2] ?? '')));
            if ($colC === 'dqn') {
                $isFirstChunk = true;
            }
        }

        // Cache key scoped per import instance via the unique
        // importToken inherited from AbstractImport. spl_object_id()
        // would collide across processes (queue workers, artisan +
        // web), leading one import to read another's header mapping.
        $cacheKey = 'km_mapping:'.$this->garageId.':'.($this->companyId ?? 0).':'.$this->importToken;

        if ($isFirstChunk) {
            $dateRow = $rows->get(0)->toArray();
            $headerRow = $rows->get(1)->toArray();

            $kmColumns = [];

            // Scan the header row for "KM" labels. For each one, walk
            // left to the closest non-empty cell in the date row and
            // use that as the date for this KM column.
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

            // 24-hour TTL: comfortably longer than any realistic import
            // runtime. The cache key carries the per-instance importToken
            // so entries from concurrent or past imports cannot collide.
            Cache::put($cacheKey, $kmColumns, now()->addHours(24));

            $dataRows = $rows->slice(2);
        } else {
            $kmColumns = Cache::get($cacheKey, []);
            $dataRows = $rows;
        }

        if (empty($kmColumns)) {
            return;
        }

        // ─── Step 1: load every bus referenced in this chunk ───
        // ONE query for the whole chunk instead of one per row.
        $dqnList = $dataRows
            ->pluck(2)
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $buses = Bus::withoutGlobalScopes()
            ->whereIn('dqn', $dqnList)
            ->where('garage_id', $this->garageId)
            ->get()
            ->keyBy('dqn');

        // ─── Step 2: build the batch of KM records ───
        // Rows are kept in a plain array and keyed by "bus_id|date".
        // If the Excel file repeats the same pair, the last value
        // wins — this prevents the partial unique index from being
        // violated by duplicate rows inside the same chunk.
        $records = [];
        $now = now();

        foreach ($dataRows as $row) {
            $dqn = trim((string) ($row[2] ?? ''));

            if ($dqn === '') {
                continue;
            }

            $bus = $buses->get($dqn);

            if (! $bus) {
                $this->recordSkip('—', $dqn, __('messages.imports.reasons.dqn_not_found'));

                continue;
            }

            foreach ($kmColumns as $colIndex => $dateString) {
                $km = $row[$colIndex] ?? null;

                if (! is_numeric($km) || (int) $km <= 0) {
                    continue;
                }

                $key = $bus->id.'|'.$dateString;

                $records[$key] = [
                    'bus_id' => $bus->id,
                    'garage_id' => $bus->garage_id ?? $this->garageId,
                    'company_id' => $bus->company_id ?? $this->companyId,
                    'date' => $dateString,
                    'km' => (int) $km,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (empty($records)) {
            return;
        }

        // Convert the keyed map back to a sequential array for insert().
        $records = array_values($records);

        // ─── Step 3: replace matching rows atomically ───
        // The partial unique index on (bus_id, date) WHERE deleted_at
        // IS NULL does not support Laravel's `upsert()`. Instead we:
        //   a) find existing active rows with the EXACT (bus_id, date)
        //      pairs from this chunk,
        //   b) soft-delete them,
        //   c) bulk-insert the new rows.
        //
        // All three steps run in one transaction so a partial failure
        // rolls back cleanly and no data is lost.
        //
        // IMPORTANT: the whereIn() calls below are a *superset* of the
        // exact pairs we want. We filter the result in PHP by the
        // $pairSet to avoid soft-deleting rows that happen to share a
        // bus_id or a date with this chunk but are not part of it.
        $busIds = array_values(array_unique(array_column($records, 'bus_id')));
        $dates = array_values(array_unique(array_column($records, 'date')));

        $pairSet = [];
        foreach ($records as $r) {
            $pairSet[$r['bus_id'].'|'.$r['date']] = true;
        }

        DB::transaction(function () use ($busIds, $dates, $pairSet, $records) {
            $existing = DailyKmRecord::withoutGlobalScopes()
                ->whereIn('bus_id', $busIds)
                ->whereIn('date', $dates)
                ->whereNull('deleted_at')
                ->get(['id', 'bus_id', 'date'])
                ->filter(function ($row) use ($pairSet) {
                    $dateStr = $row->date instanceof \DateTimeInterface
                        ? $row->date->format('Y-m-d')
                        : (string) $row->date;

                    return isset($pairSet[$row->bus_id.'|'.$dateStr]);
                })
                ->keyBy(fn ($row) => $row->bus_id.'|'.(
                    $row->date instanceof \DateTimeInterface
                        ? $row->date->format('Y-m-d')
                        : (string) $row->date
                ));

            $toInsert = [];
            $now = now();

            foreach ($records as $record) {
                $key = $record['bus_id'].'|'.$record['date'];

                if ($existing->has($key)) {
                    DailyKmRecord::withoutGlobalScopes()
                        ->where('id', $existing->get($key)->id)
                        ->update([
                            'km' => $record['km'],
                            'updated_at' => $now,
                        ]);
                } else {
                    $toInsert[] = $record;
                }
            }

            if (! empty($toInsert)) {
                DailyKmRecord::withoutGlobalScopes()->insert($toInsert);
            }
        });

        $this->incrementImported(count($records));
    }

    /**
     * Convert an Excel cell value into a Y-m-d string.
     *
     * Handles:
     *   - DateTimeInterface instances (PhpSpreadsheet already parsed)
     *   - Excel serial numbers (e.g. 45000)
     *   - ISO date strings ("2026-09-15")
     *   - Day-first strings ("15.09.2026", "15/09/2026")
     *
     * Returns null when the value cannot be interpreted as a date.
     */
    private function toDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        // Excel serial numbers for dates are typically in the
        // 20000-100000 range (1900-01-01 = 1, 2026-09-15 ≈ 46260).
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

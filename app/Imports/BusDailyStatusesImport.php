<?php

namespace App\Imports;

use App\Models\Bus;
use App\Models\BusDailyStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Imports daily bus statuses from an Excel file.
 *
 * Expected columns (heading row, case-insensitive):
 *   - DQN      (required) — bus identifier within the current garage
 *   - Date     (optional) — d.m.Y, Y-m-d, or Excel serial.
 *                           Defaults to today when omitted.
 *   - Status   (optional) — free-text label. Defaults to "NO DATA".
 *   - Notes    (optional)
 *
 * PERFORMANCE NOTE
 * ----------------
 * The previous implementation used the ToModel contract: every row
 * triggered one bus lookup and one updateOrCreate (SELECT + write).
 * For a 500-bus daily sheet that is ~1500 round-trips — a few
 * seconds per file, and the cost compounds when importing a month
 * of historical data in a single sheet.
 *
 * This version mirrors DailyKmRecordsImport:
 *   1. Loads all referenced buses in ONE query per chunk.
 *   2. Accumulates every status row in memory, keyed by
 *      (bus_id, date) so duplicate rows in the same chunk cannot
 *      violate the partial unique index.
 *   3. Soft-deletes the exact (bus_id, date) pairs already stored
 *      in this chunk, then bulk-inserts the new rows.
 *   4. Wraps the delete + insert in a transaction so a failure
 *      rolls back cleanly.
 *
 * TRADE-OFF: bulk insert/delete bypasses Eloquent events, so no
 * per-row audit entries are written for imports. Manual edits keep
 * their full audit trail.
 */
class BusDailyStatusesImport extends AbstractImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        // ─── Step 1: preload every bus referenced in this chunk ───
        // ONE query for the whole chunk instead of one per row.
        $dqnList = $rows
            ->pluck('dqn')
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

        // ─── Step 2: build the batch of status rows ───
        // Rows are keyed by "bus_id|date" so that if the Excel file
        // repeats the same (bus, date) inside one chunk, the last
        // value wins instead of triggering a unique-constraint error.
        $records = [];
        $now     = now();

        foreach ($rows as $row) {
            $currentRow = $this->nextRowIndex();

            $dqn    = trim((string) ($row['dqn'] ?? ''));
            $status = $row['status'] ?? null;

            if ($dqn === '') {
                $this->recordSkip($currentRow, '—', __('messages.imports.reasons.dqn_empty'));

                continue;
            }

            $bus = $buses->get($dqn);

            if (! $bus) {
                $this->recordSkip($currentRow, $dqn, __('messages.imports.reasons.dqn_not_found'));

                continue;
            }

            $date = $this->resolveDate($row['date'] ?? $row['tarix'] ?? null);

            $key = $bus->id.'|'.$date;

            $records[$key] = [
                'bus_id'     => $bus->id,
                'garage_id'  => $bus->garage_id  ?? $this->garageId,
                'company_id' => $bus->company_id ?? $this->companyId,
                'date'       => $date,
                'status'     => $status ?? 'NO DATA',
                'notes'      => $row['notes'] ?? $row['qeyd'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (empty($records)) {
            return;
        }

        $records = array_values($records);

        // ─── Step 3: replace matching rows atomically ───
        // The partial unique index on (bus_id, date) WHERE deleted_at
        // IS NULL does not support Laravel's `upsert()`. We therefore
        // soft-delete the exact pairs first, then bulk-insert.
        //
        // The whereIn() calls below are a superset of the pairs we
        // want to remove; we filter the result in PHP by $pairSet so
        // rows that merely share a bus_id or a date are not touched.
        $busIds = array_values(array_unique(array_column($records, 'bus_id')));
        $dates  = array_values(array_unique(array_column($records, 'date')));

        $pairSet = [];
        foreach ($records as $r) {
            $pairSet[$r['bus_id'].'|'.$r['date']] = true;
        }

        DB::transaction(function () use ($busIds, $dates, $pairSet, $records) {
            $existingIds = BusDailyStatus::withoutGlobalScopes()
                ->whereIn('bus_id', $busIds)
                ->whereIn('date', $dates)
                ->whereNull('deleted_at')
                ->get(['id', 'bus_id', 'date'])
                ->filter(fn ($row) => isset($pairSet[$row->bus_id.'|'.$row->date]))
                ->pluck('id')
                ->all();

            if (! empty($existingIds)) {
                BusDailyStatus::withoutGlobalScopes()
                    ->whereIn('id', $existingIds)
                    ->delete();
            }

            BusDailyStatus::withoutGlobalScopes()->insert($records);
        });

        $this->incrementImported(count($records));
    }

    /**
     * Convert an Excel cell value into a Y-m-d string.
     *
     * Handles:
     *   - DateTimeInterface instances (PhpSpreadsheet already parsed)
     *   - Excel serial numbers (e.g. 45000)
     *   - Day-first strings ("15.09.2026", "15/09/2026", "15-09-2026")
     *   - ISO date strings ("2026-09-15")
     *   - Anything else → today's date (never fails the whole import)
     *
     * The explicit day-first regex guarantees that "15.09.2026" is
     * always parsed as 15 September 2026, independent of the PHP
     * locale or Carbon's default parsing rules.
     */
    private function resolveDate(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        // Excel serial numbers for dates fall in the 20000-100000
        // range (1900-01-01 = 1, 2026-09-15 ≈ 46260).
        if (is_numeric($value) && (float) $value > 20000 && (float) $value < 100000) {
            try {
                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject((float) $value)
                )->toDateString();
            } catch (\Throwable $e) {
                // Fall through to today
            }
        }

        if (is_string($value) && trim($value) !== '') {
            $value = trim($value);

            // Explicit day-first formats: d.m.Y, d/m/Y, d-m-Y.
            if (preg_match('/^(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})$/', $value, $m)) {
                try {
                    return Carbon::createFromDate(
                        (int) $m[3],
                        (int) $m[2],
                        (int) $m[1]
                    )->toDateString();
                } catch (\Throwable $e) {
                    // Fall through to generic parse
                }
            }

            try {
                return Carbon::parse($value)->toDateString();
            } catch (\Throwable $e) {
                // Fall through to today
            }
        }

        return now()->toDateString();
    }
}

<?php

namespace App\Imports;

use App\Enums\OilType;
use App\Models\Bus;
use App\Models\BusOilChange;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

/**
 * Generic import for bus oil change history.
 *
 * One class handles all three oil types. The behavior varies by type:
 *
 *   MOTOR    — columns like "324000 BAKIMI" hold the scheduled km,
 *              the cell value is the actual km. Interval is derived
 *              from the bus length (12m=36000, 18m=30000).
 *
 *   GEARBOX  — columns like "SHELL 1", "LUK 2" — the brand is taken
 *              from the column header. Interval depends on the brand
 *              (SHELL=180k, LUK=120k).
 *
 *   AXLE     — columns are pure numbers ("360", "540") meaning
 *              "360000 km", "540000 km". Interval is fixed at 180k.
 *
 * The import is idempotent: re-uploading the same file will not
 * create duplicates. Existing (bus, type, actual_km) rows are skipped.
 */
class BusOilChangesImport extends AbstractImport implements ToCollection, WithChunkReading
{
    /**
     * Column mappings detected from the header row.
     *
     * @var array<int, array{scheduled: int|null, brand: string|null}>
     */
    protected array $kmColumns = [];

    protected ?int $dqnColumnIndex = null;

    protected bool $headerParsed = false;

    public function __construct(
        ?int $garageId,
        ?int $companyId,
        public readonly OilType $type,
        public readonly ?int $busLengthM = null,
    ) {
        parent::__construct($garageId, $companyId);

        if ($type === OilType::Motor
            && $busLengthM !== null
            && ! in_array($busLengthM, [12, 18], true)) {
            throw new \InvalidArgumentException(
                'busLengthM must be 12, 18 or null for motor oil imports.'
            );
        }
    }

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        // Cache key survives between chunks so the header mapping
        // detected in the first chunk is still available later.
        $cacheKey = "oil_import_header:{$this->garageId}:{$this->type->value}:"
            .spl_object_id($this);

        if (! $this->headerParsed) {
            $firstRow = $rows->first()->toArray();

            // Header row is the one whose DQN column contains "DQN"
            // or "PLAKA" instead of an actual bus DQN.
            if ($this->looksLikeHeader($firstRow)) {
                $this->parseHeader($firstRow);
                $this->headerParsed = true;
                Cache::put($cacheKey, [
                    'dqnCol'   => $this->dqnColumnIndex,
                    'kmCols'   => $this->kmColumns,
                ], now()->addHours(2));

                $rows = $rows->slice(1);
            } else {
                // Continuing chunk — restore mapping from cache.
                $cached = Cache::get($cacheKey);
                if (! $cached) {
                    throw new \RuntimeException(
                        'Oil import header mapping lost. Please re-upload the file.'
                    );
                }
                $this->dqnColumnIndex = $cached['dqnCol'];
                $this->kmColumns      = $cached['kmCols'];
                $this->headerParsed   = true;
            }
        }

        if ($this->dqnColumnIndex === null || empty($this->kmColumns)) {
            return;
        }

        // Preload every referenced bus in ONE query.
        $dqnList = $rows
            ->map(fn ($r) => trim((string) ($r[$this->dqnColumnIndex] ?? '')))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $buses = Bus::withoutGlobalScopes()
            ->whereIn('dqn', $dqnList)
            ->where('garage_id', $this->garageId)
            ->get()
            ->keyBy('dqn');

        foreach ($rows as $row) {
            $this->processRow($row->toArray(), $buses);
        }
    }

    protected function looksLikeHeader(array $row): bool
    {
        foreach ($row as $value) {
            $v = strtolower(trim((string) $value));
            if ($v === 'dqn' || str_starts_with($v, 'plaka')) {
                return true;
            }
        }

        return false;
    }

    protected function parseHeader(array $header): void
    {
        foreach ($header as $idx => $value) {
            $v = trim((string) $value);

            if ($v === '') {
                continue;
            }

            // DQN column — matches "DQN", "PLAKA No", "PLAKA"
            if (preg_match('/^(dqn|plaka)/i', $v)) {
                $this->dqnColumnIndex ??= (int) $idx;
                continue;
            }

            // MOTOR: "324000 BAKIMI" / "396000 BAKIMI" ...
            if ($this->type === OilType::Motor
                && preg_match('/^(\d{4,7})\s*bak/i', $v, $m)) {
                $this->kmColumns[(int) $idx] = [
                    'scheduled' => (int) $m[1],
                    'brand'     => null,
                ];
                continue;
            }

            // GEARBOX: "SHELL 1", "LUK 2", "SHELL" ...
            if ($this->type === OilType::Gearbox
                && preg_match('/^(shell|luk)\b/i', $v, $m)) {
                $this->kmColumns[(int) $idx] = [
                    'scheduled' => null,
                    'brand'     => strtoupper($m[1]),
                ];
                continue;
            }

            // AXLE: pure numeric header — "360", "540", "720", "900"
            if ($this->type === OilType::Axle
                && preg_match('/^(\d+)$/', $v, $m)) {
                $km = (int) $m[1];
                // Values under 10000 represent thousands of km.
                if ($km < 10000) {
                    $km *= 1000;
                }
                $this->kmColumns[(int) $idx] = [
                    'scheduled' => $km,
                    'brand'     => null,
                ];
            }
        }
    }

    protected function processRow(array $row, Collection $buses): void
    {
        $rowIndex = $this->nextRowIndex();

        $dqn = trim((string) ($row[$this->dqnColumnIndex] ?? ''));

        if ($dqn === '') {
            return; // Empty rows are common in real Excel files.
        }

        /** @var Bus|null $bus */
        $bus = $buses->get($dqn);

        if (! $bus) {
            $this->recordSkip($rowIndex, $dqn, __('messages.imports.reasons.dqn_not_found'));
            return;
        }

        $created = 0;

        foreach ($this->kmColumns as $colIdx => $meta) {
            $raw = $row[$colIdx] ?? null;

            if ($raw === null || trim((string) $raw) === '') {
                continue;
            }

            $actualKm = (int) preg_replace('/[^0-9]/', '', (string) $raw);

            if ($actualKm <= 0) {
                continue;
            }

            $brand    = $meta['brand'];
            $interval = $this->resolveInterval($bus, $brand);

            // Idempotency: skip if a matching row already exists.
            $exists = BusOilChange::withoutGlobalScopes()
                ->where('bus_id', $bus->id)
                ->where('oil_type', $this->type->value)
                ->where('actual_km', $actualKm)
                ->when($brand !== null, fn ($q) => $q->where('oil_brand', $brand))
                ->exists();

            if ($exists) {
                continue;
            }

            BusOilChange::withoutGlobalScopes()->create([
                'garage_id'    => $this->garageId,
                'company_id'   => $this->companyId,
                'bus_id'       => $bus->id,
                'oil_type'     => $this->type->value,
                'oil_brand'    => $brand,
                'scheduled_km' => $meta['scheduled'],
                'actual_km'    => $actualKm,
                'interval_km'  => $interval,
            ]);

            $created++;
        }

        if ($created > 0) {
            $this->incrementImported($created);
        }
    }

    protected function resolveInterval(Bus $bus, ?string $brand): int
    {
        return match ($this->type) {
            OilType::Motor => match ($this->busLengthM) {
                18      => 30000,
                12      => 36000,
                default => $bus->motorOilIntervalKm(),
            },
            OilType::Gearbox => Bus::gearboxIntervalForBrand($brand),
            OilType::Axle    => 180000,
        };
    }

    public function chunkSize(): int
    {
        return 200;
    }
}

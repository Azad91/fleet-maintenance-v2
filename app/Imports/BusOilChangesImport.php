<?php

namespace App\Imports;

use App\Enums\OilType;
use App\Models\Bus;
use App\Models\BusOilChange;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

/**
 * Generic import for bus oil change history.
 *
 * Handles three oil types with type-specific header parsing:
 *
 *   MOTOR    — headers like "324000 BAKIMI" / "396000 BAKIMI"
 *   GEARBOX  — headers like "SHELL 1" / "LUK 1" / "SHELL 2" / ...
 *   AXLE     — numeric headers like "360" / "540" (thousands of km)
 */
class BusOilChangesImport extends AbstractImport implements ToCollection, WithChunkReading
{
    /**
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

        // Token-based cache key. See AbstractImport::\$importToken
        // for why spl_object_id() is not safe across processes.
        $cacheKey = "oil_import_header:{$this->garageId}:{$this->type->value}:{$this->importToken}";

        if (! $this->headerParsed) {
            $firstRow = $rows->first()->toArray();

            if ($this->looksLikeHeader($firstRow)) {
                $this->parseHeader($firstRow);
                $this->headerParsed = true;
                Cache::put($cacheKey, [
                    'dqnCol' => $this->dqnColumnIndex,
                    'kmCols' => $this->kmColumns,
                ], now()->addHours(24));

                $rows = $rows->slice(1);
            } else {
                $cached = Cache::get($cacheKey);
                if (! $cached) {
                    throw new \RuntimeException(
                        'Oil import header mapping lost. Please re-upload the file.'
                    );
                }
                $this->dqnColumnIndex = $cached['dqnCol'];
                $this->kmColumns = $cached['kmCols'];
                $this->headerParsed = true;
            }
        }

        if ($this->dqnColumnIndex === null || empty($this->kmColumns)) {
            Log::warning('Oil import aborted: no DQN column or no KM columns detected', [
                'type' => $this->type->value,
                'dqn_col' => $this->dqnColumnIndex,
                'km_col_count' => count($this->kmColumns),
            ]);

            return;
        }

        // Preload every referenced bus in ONE query.
        $dqnList = $rows
            ->map(fn ($r) => trim((string) ($r[$this->dqnColumnIndex] ?? '')))
            ->filter()
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
                    'brand' => null,
                ];

                continue;
            }

            // GEARBOX: "SHELL 1", "LUK 2", "SHELL", "LUK", "shell 1 180" ...
            if ($this->type === OilType::Gearbox
                && preg_match('/\b(shell|luk)\b/i', $v, $m)) {
                $this->kmColumns[(int) $idx] = [
                    'scheduled' => null,
                    'brand' => strtoupper($m[1]),
                ];

                continue;
            }

            // AXLE: pure numeric header — "360", "540", "720", "900"
            if ($this->type === OilType::Axle
                && preg_match('/^(\d+)$/', $v, $m)) {
                $km = (int) $m[1];
                if ($km < 10000) {
                    $km *= 1000;
                }
                $this->kmColumns[(int) $idx] = [
                    'scheduled' => $km,
                    'brand' => null,
                ];
            }
        }

        // ─── Debug: log what was detected ───
        Log::info('Oil import header parse', [
            'type' => $this->type->value,
            'header_raw' => $header,
            'detected_dqn_col' => $this->dqnColumnIndex,
            'detected_km_cols' => $this->kmColumns,
        ]);
    }

    protected function processRow(array $row, Collection $buses): void
    {
        $rowIndex = $this->nextRowIndex();

        $dqn = trim((string) ($row[$this->dqnColumnIndex] ?? ''));

        if ($dqn === '') {
            return;
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

            $brand = $meta['brand'];
            $interval = $this->resolveInterval($bus, $brand);

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
                'garage_id' => $this->garageId,
                'company_id' => $this->companyId,
                'bus_id' => $bus->id,
                'oil_type' => $this->type->value,
                'oil_brand' => $brand,
                'scheduled_km' => $meta['scheduled'],
                'actual_km' => $actualKm,
                'interval_km' => $interval,
            ]);

            $created++;
        }

        if ($created > 0) {
            $this->incrementImported($created);
        }
    }

    /**
     * Resolve the interval (km) to snapshot for a specific import row.
     *
     * Must match OilChangeService::resolveInterval() so that an oil
     * change created via the import has the SAME interval_km as one
     * created manually through the form. Previously this method used
     * hardcoded literals (30000 / 36000 / 180000), which silently
     * diverged from the config-driven values whenever an operator
     * tuned the intervals in config/oil.php.
     *
     * Motor oil still uses the length the operator selected on the
     * import form (12m or 18m) because the import file itself does
     * not carry that information — the operator chooses one length
     * for the whole file. We look up the config value for that
     * length instead of hardcoding it.
     *
     * Gearbox and Axle now use the same resolvers as the manual
     * path — gearbox is brand-aware (SHELL vs LUK), axle reads the
     * config default.
     */
    protected function resolveInterval(Bus $bus, ?string $brand): int
    {
        return match ($this->type) {
            OilType::Motor => $this->resolveMotorInterval($bus),
            OilType::Gearbox => Bus::gearboxIntervalForBrand($brand),
            OilType::Axle => $bus->axleOilIntervalKm(),
        };
    }

    /**
     * Resolve the motor-oil interval for this import.
     *
     * If the operator selected a bus length (12 or 18) on the import
     * form, use that length's config value — every bus in the file
     * is treated as having this length, because the file itself does
     * not carry per-row length information.
     *
     * If no length was selected, fall back to the bus's own
     * config-driven interval (motorOilIntervalKm() reads
     * config/oil.intervals.motor.{12m|18m}).
     */
    private function resolveMotorInterval(Bus $bus): int
    {
        if ($this->busLengthM === 12 || $this->busLengthM === 18) {
            $fallback = $this->busLengthM === 18 ? 30000 : 36000;

            return (int) config(
                "oil.intervals.motor.{$this->busLengthM}m",
                $fallback,
            );
        }

        return $bus->motorOilIntervalKm();
    }

    public function chunkSize(): int
    {
        return 200;
    }
}

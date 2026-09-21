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

        $cacheKey = "oil_import_header:{$this->garageId}:{$this->type->value}:"
            .spl_object_id($this);

        if (! $this->headerParsed) {
            $firstRow = $rows->first()->toArray();

            if ($this->looksLikeHeader($firstRow)) {
                $this->parseHeader($firstRow);
                $this->headerParsed = true;
                Cache::put($cacheKey, [
                    'dqnCol' => $this->dqnColumnIndex,
                    'kmCols' => $this->kmColumns,
                ], now()->addHours(2));

                $rows = $rows->slice(1);
            } else {
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
                    'brand'     => null,
                ];
                continue;
            }

            // GEARBOX: "SHELL 1", "LUK 2", "SHELL", "LUK", "shell 1 180" ...
            if ($this->type === OilType::Gearbox
                && preg_match('/\b(shell|luk)\b/i', $v, $m)) {
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
                if ($km < 10000) {
                    $km *= 1000;
                }
                $this->kmColumns[(int) $idx] = [
                    'scheduled' => $km,
                    'brand'     => null,
                ];
            }
        }

        // ─── Debug: log what was detected ───
        Log::info('Oil import header parse', [
            'type'             => $this->type->value,
            'header_raw'       => $header,
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

            $brand    = $meta['brand'];
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
            OilType::Gearbox => 180000,
            OilType::Axle    => 180000,
        };
    }

    public function chunkSize(): int
    {
        return 200;
    }
}

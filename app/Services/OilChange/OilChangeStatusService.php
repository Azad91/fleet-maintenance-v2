<?php

namespace App\Services\OilChange;

use App\Enums\OilType;
use App\Models\Bus;
use App\Models\MotorOilDetail;
use Illuminate\Support\Collection;

class OilChangeStatusService
{
    /**
     * Per-request cache of catalog milestones, keyed by brand_id.
     * Avoids N+1 queries when rendering a long list of buses.
     *
     * @var array<string, array<int>>
     */
    private array $catalogCache = [];

    public function forBus(Bus $bus, OilType $type): OilChangeStatus
    {
        $last = $bus->relationLoaded('oilChanges')
            ? $bus->oilChanges->where('oil_type', $type)->sortByDesc('actual_km')->first()
            : $bus->latestOilChange($type);

        $currentKm = (int) (
            $bus->relationLoaded('latestKmRecord')
                ? ($bus->latestKmRecord?->km ?? $bus->km)
                : ($bus->latestKmRecord()->value('km') ?? $bus->km)
        ) ?? 0;

        if (! $last) {
            return new OilChangeStatus(
                bus: $bus,
                type: $type,
                lastChange: null,
                currentKm: $currentKm,
                nextDueKm: null,
                remainingKm: null,
                status: 'no-history',
                nextCatalogKm: null,
            );
        }

        // Interval selection:
        //   - Gearbox → fixed 180,000 km
        //   - Others  → the interval snapshot stored on the last change
        //               (motor: 18m = 30,000 km, 12m = 36,000 km)
        $interval = match ($type) {
            OilType::Gearbox => 180000,
            default          => $last->interval_km,
        };

        $nextDueKm   = $last->actual_km + $interval;
        $remainingKm = $nextDueKm - $currentKm;

        // For motor oil, look up the next service milestone in the
        // catalog (motor_oil_details). The mathematically computed
        // next_due may not exist in the catalog because the catalog
        // is keyed by 36,000-based service blocks, while an 18m bus
        // uses a 30,000 km interval. The operator needs to see the
        // catalog milestone because that is the one tied to a parts
        // list.
        $nextCatalogKm = $type === OilType::Motor
            ? $this->nextCatalogMilestone($bus, $nextDueKm)
            : null;

        return new OilChangeStatus(
            bus: $bus,
            type: $type,
            lastChange: $last,
            currentKm: $currentKm,
            nextDueKm: $nextDueKm,
            remainingKm: $remainingKm,
            status: $this->classify($remainingKm),
            nextCatalogKm: $nextCatalogKm,
        );
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Bus>  $buses
     * @return \Illuminate\Support\Collection<int, OilChangeStatus>
     */
    public function priorityFor(Collection $buses, OilType $type): Collection
    {
        return $buses
            ->map(fn (Bus $bus) => $this->forBus($bus, $type))
            ->sortBy(fn (OilChangeStatus $s) => $s->remainingKm ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * The next motor-oil catalog milestone for the given bus's brand,
     * at or after the target km.
     *
     * Falls back to the union of ALL catalog entries for the garage
     * when the bus has no brand assigned, so the operator still sees
     * a useful milestone instead of an empty column.
     *
     * Example: bus is 18m, interval = 30,000, next_due = 390,000.
     * The catalog (36,000-based) has ..., 360,000, 396,000, 432,000, ...
     * → returns 396,000 because it is the first catalog entry >= 390,000.
     */
    private function nextCatalogMilestone(Bus $bus, int $targetKm): ?int
    {
        // Cache key includes the brand so different buses with the
        // same brand share one query per request.
        $brandKey = (string) ($bus->brand_id ?? 'all');

        if (! array_key_exists($brandKey, $this->catalogCache)) {
            $query = MotorOilDetail::withoutGlobalScopes()
                ->where('garage_id', $bus->garage_id);

            // Only narrow by brand when the bus actually has one.
            // Without this guard, a bus with brand_id = NULL would
            // filter on `whereNull('brand_id')` and get zero rows,
            // because every imported catalog row carries a brand.
            if ($bus->brand_id !== null) {
                $query->where('brand_id', $bus->brand_id);
            }

            $this->catalogCache[$brandKey] = $query
                ->orderBy('km')
                ->pluck('km')
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values()
                ->all();
        }

        foreach ($this->catalogCache[$brandKey] as $milestone) {
            if ($milestone >= $targetKm) {
                return $milestone;
            }
        }

        return null;
    }

    private function classify(int $remainingKm): string
    {
        $critical = (int) config('oil.thresholds.critical_km', 1000);
        $dueSoon  = (int) config('oil.thresholds.due_soon_km', 5000);

        return match (true) {
            $remainingKm < 0          => 'overdue',
            $remainingKm <= $critical => 'critical',
            $remainingKm <= $dueSoon  => 'due-soon',
            default                   => 'ok',
        };
    }
}

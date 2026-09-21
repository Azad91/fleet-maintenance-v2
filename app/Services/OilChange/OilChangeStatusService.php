<?php

namespace App\Services\OilChange;

use App\Enums\OilType;
use App\Models\Bus;
use Illuminate\Support\Collection;

class OilChangeStatusService
{
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
            );
        }

        // Interval seçimi:
        //   - Korobka → sabit 180 000 km (Excel qarışıq olduğu üçün)
        //   - Digərləri → son dəyişmədə snapshot edilmiş interval
        $interval = match ($type) {
            OilType::Gearbox => 180000,
            default          => $last->interval_km,
        };

        $nextDueKm   = $last->actual_km + $interval;
        $remainingKm = $nextDueKm - $currentKm;

        return new OilChangeStatus(
            bus: $bus,
            type: $type,
            lastChange: $last,
            currentKm: $currentKm,
            nextDueKm: $nextDueKm,
            remainingKm: $remainingKm,
            status: $this->classify($remainingKm),
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

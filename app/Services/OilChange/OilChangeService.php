<?php

namespace App\Services\OilChange;

use App\Enums\OilType;
use App\Models\Bus;
use App\Models\BusOilChange;
use Illuminate\Support\Facades\DB;

class OilChangeService
{
    /**
     * Create a new oil change record.
     *
     * The interval is snapshotted from config at the moment of creation
     * so historical reports stay stable even if config changes later.
     */
    public function create(Bus $bus, array $data): BusOilChange
    {
        return DB::transaction(function () use ($bus, $data) {
            $type   = OilType::from($data['oil_type']);
            $brand  = $type->usesBrand() ? ($data['oil_brand'] ?? null) : null;
            $actual = (int) $data['actual_km'];

            $interval = $this->resolveInterval($bus, $type, $brand);

            return BusOilChange::create([
                'bus_id'       => $bus->id,
                'oil_type'     => $type->value,
                'oil_brand'    => $brand,
                'scheduled_km' => $data['scheduled_km'] ?? null,
                'actual_km'    => $actual,
                'interval_km'  => $interval,
                'changed_at'   => $data['changed_at'] ?? null,
                'notes'        => $data['notes'] ?? null,
            ]);
        });
    }

    public function update(BusOilChange $change, array $data): BusOilChange
    {
        return DB::transaction(function () use ($change, $data) {
            $type  = OilType::from($data['oil_type'] ?? $change->oil_type->value);
            $brand = $type->usesBrand()
                ? ($data['oil_brand'] ?? $change->oil_brand)
                : null;

            $interval = $this->resolveInterval($change->bus, $type, $brand);

            $change->update([
                'oil_type'     => $type->value,
                'oil_brand'    => $brand,
                'scheduled_km' => $data['scheduled_km'] ?? $change->scheduled_km,
                'actual_km'    => (int) ($data['actual_km'] ?? $change->actual_km),
                'interval_km'  => $interval,
                'changed_at'   => $data['changed_at'] ?? $change->changed_at,
                'notes'        => $data['notes'] ?? $change->notes,
            ]);

            return $change->fresh();
        });
    }

    public function delete(BusOilChange $change): void
    {
        $change->delete();
    }

    /**
     * The interval to record for a specific change.
     */
    private function resolveInterval(Bus $bus, OilType $type, ?string $brand): int
    {
        return match ($type) {
            OilType::Motor   => $bus->motorOilIntervalKm(),
            OilType::Axle    => $bus->axleOilIntervalKm(),
            OilType::Gearbox => Bus::gearboxIntervalForBrand($brand),
        };
    }
}

<?php

namespace App\Models;

use App\Enums\OilType;
use App\Models\Traits\Auditable;
use App\Models\Traits\HasCreatedBy;
use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Bus;

class BusOilChange extends Model
{
    use Auditable, HasCreatedBy, HasFactory, HasGarageScope, SoftDeletes;

    protected $fillable = [
        'bus_id',
        'oil_type',
        'oil_brand',
        'scheduled_km',
        'actual_km',
        'interval_km',
        'changed_at',
        'notes',
        'garage_id',
        'company_id',
    ];

    protected $casts = [
        'oil_type'     => OilType::class,
        'scheduled_km' => 'integer',
        'actual_km'    => 'integer',
        'interval_km'  => 'integer',
        'changed_at'   => 'date',
    ];

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function scopeOfType(Builder $query, OilType|string $type): Builder
    {
        $value = $type instanceof OilType ? $type->value : $type;

        return $query->where('oil_type', $value);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('actual_km')->orderByDesc('id');
    }

    /**
     * Determine the interval to use for the NEXT oil change of the given
     * type for the given bus.
     *
     * Priority:
     *   1. The bus's own history — median of consecutive gaps
     *   2. Config fallback — motor: length, gearbox: brand, axle: fixed
     */
    public static function effectiveIntervalFor(Bus $bus, OilType $type): int
    {
        // Get last 4 changes so we can compute up to 3 deltas.
        $kms = static::withoutGlobalScopes()
            ->where('bus_id', $bus->id)
            ->where('oil_type', $type->value)
            ->whereNull('deleted_at')
            ->orderByDesc('actual_km')
            ->limit(4)
            ->pluck('actual_km')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();

        // ─── Derive from history ───
        if (count($kms) >= 2) {
            $deltas = [];

            for ($i = 0; $i < count($kms) - 1; $i++) {
                $delta = $kms[$i] - $kms[$i + 1];

                if ($delta > 0) {
                    $deltas[] = $delta;
                }
            }

            if (! empty($deltas)) {
                sort($deltas);

                // Median is more robust than avg — ignores one-off outliers.
                return $deltas[(int) floor(count($deltas) / 2)];
            }
        }

        // ─── Fallback to config ───
        return match ($type) {
            OilType::Motor => $bus->motorOilIntervalKm(),
            OilType::Axle  => $bus->axleOilIntervalKm(),
            OilType::Gearbox => Bus::gearboxIntervalForBrand(
                static::withoutGlobalScopes()
                    ->where('bus_id', $bus->id)
                    ->where('oil_type', OilType::Gearbox->value)
                    ->whereNull('deleted_at')
                    ->orderByDesc('actual_km')
                    ->value('oil_brand')
            ),
        };
    }

    public function nextDueKm(): int
    {
        return $this->actual_km + $this->interval_km;
    }
}

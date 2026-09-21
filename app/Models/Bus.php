<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\OilType;

class Bus extends Model
{
    use Auditable, HasFactory, HasGarageScope, SoftDeletes;

    protected $fillable = [
        'garage_id',
        'company_id',
        'brand_id',
        'bus_project',
        'vin',
        'uzunluq',
        'route_number',
        'dqn',
        'engine_number',
        'km',
        'date',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'date' => 'date',
        'km' => 'integer',
    ];

    public function brand()
    {
        return $this->belongsTo(BusBrand::class, 'brand_id');
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    public function dailyKmRecords()
    {
        return $this->hasMany(DailyKmRecord::class)->orderBy('date', 'desc');
    }

    public function dailyStatuses()
    {
        return $this->hasMany(BusDailyStatus::class)->orderBy('date', 'desc');
    }

    public function latestKmRecord()
    {
        return $this->hasOne(DailyKmRecord::class)->latestOfMany('date');
    }

    /**
     * The most recent daily status for this bus, ordered by date.
     * Used on the bus show page to display the "Current Status" KPI
     * card without an extra query per request.
     */
    public function latestDailyStatus()
    {
        return $this->hasOne(BusDailyStatus::class)->latestOfMany('date');
    }

    public function getLatestKmAttribute()
    {
        return $this->latestKmRecord?->km;
    }

    /**
     * Daily distance driven — the difference between the two most
     * recent KM records for this bus.
     *
     * Returns null when there are fewer than 2 records (no baseline
     * for comparison). The value is clamped at 0 because a stationary
     * bus logs the same KM on consecutive days, which must render as
     * "0 km", never as a negative number.
     *
     * IMPORTANT: callers should eager-load the `dailyKmRecords`
     * relation with `->limit(2)` to avoid an N+1 query on list pages.
     */
    public function getDailyKmAttribute(): ?int
    {
        $records = $this->relationLoaded('dailyKmRecords')
            ? $this->dailyKmRecords->take(2)
            : $this->dailyKmRecords()->take(2)->get();

        if ($records->count() < 2) {
            return null;
        }

        return max(0, $records[0]->km - $records[1]->km);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
    public function oilChanges()
    {
        return $this->hasMany(BusOilChange::class)->orderByDesc('actual_km');
    }

    public function latestOilChange(OilType|string $type)
    {
        $value = $type instanceof OilType ? $type->value : $type;

        return $this->hasOne(BusOilChange::class)
            ->where('oil_type', $value)
            ->latestOfMany('actual_km');
    }

    public function motorOilIntervalKm(): int
    {
        $threshold = (int) config('oil.bus_length_threshold', 15);
        $isLarge   = ((float) ($this->uzunluq ?? 12)) >= $threshold;

        $key = $isLarge ? '18m' : '12m';

        return (int) config("oil.intervals.motor.{$key}", 36000);
    }

    public function axleOilIntervalKm(): int
    {
        return (int) config('oil.intervals.axle.default', 180000);
    }

    public static function gearboxIntervalForBrand(?string $brand): int
    {
        $intervals = config('oil.intervals.gearbox', []);
        $default   = (int) ($intervals['default'] ?? 180000);

        if ($brand === null || $brand === '') {
            return $default;
        }

        return (int) ($intervals[$brand] ?? $default);
    }

    public function nextOilIntervalKm(OilType $type): int
    {
        return match ($type) {
            OilType::Motor   => $this->motorOilIntervalKm(),
            OilType::Axle    => $this->axleOilIntervalKm(),
            OilType::Gearbox => self::gearboxIntervalForBrand(
                $this->latestOilChange(OilType::Gearbox)?->oil_brand
            ),
        };
    }
}

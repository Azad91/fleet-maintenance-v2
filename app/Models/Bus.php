<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bus extends Model
{
    use Auditable, HasFactory, HasGarageScope, SoftDeletes;

    protected $fillable = [
        'garage_id',
        'company_id',
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
}

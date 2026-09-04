<?php

namespace App\Models;

use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\Auditable;

class Bus extends Model
{
    use HasFactory, HasGarageScope, SoftDeletes, Auditable;

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

    public function getLatestKmAttribute()
    {
        return $this->latestKmRecord?->km;
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

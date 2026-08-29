<?php

namespace App\Models;

use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bus extends Model
{
    use HasFactory, HasGarageScope;

    protected $fillable = [
        'garage_id',
        'company_id',
        'bus_project',
        'vin',
        'uzunluq',
        'xett_no',
        'dqn',
        'motor_no',
        'km',
        'tarix',
        'aktiv',
    ];

    protected $casts = [
        'aktiv' => 'boolean',
        'tarix' => 'date',
        'km' => 'integer',
    ];

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    public function dailyKmRecords()
    {
        return $this->hasMany(DailyKmRecord::class)->orderBy('tarix', 'desc');
    }

    public function dailyStatuses()
    {
        return $this->hasMany(BusDailyStatus::class)->orderBy('tarix', 'desc');
    }

    public function latestKmRecord()
    {
        return $this->hasOne(DailyKmRecord::class)->latestOfMany('tarix');
    }

    public function getLatestKmAttribute()
    {
        return $this->latestKmRecord?->km;
    }

    public function scopeActive($query)
    {
        return $query->where('aktiv', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('aktiv', false);
    }
}

<?php

namespace App\Models;

use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Service templates — now garage-scoped.
 *
 * A template represents a service interval (e.g. "Motor Oil Change
 * (36000 km)") and the list of parts consumed at that interval.
 * Templates are derived from MotorOilDetail, so when the motor oil
 * catalog became garage-scoped, templates had to follow — otherwise
 * one garage's service schedule would leak into another garage.
 *
 * The HasGarageScope trait:
 *   - filters every query by the current garage context
 *   - auto-populates garage_id/company_id on creation
 *   - blocks writes when no garage context is available
 */
class ServiceTemplate extends Model
{
    use HasFactory, HasGarageScope;

    protected $fillable = [
        'garage_id',
        'company_id',
        'name',
        'default_km_interval',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function busIntervals()
    {
        return $this->hasMany(BusServiceInterval::class);
    }

    public function histories()
    {
        return $this->hasMany(BusServiceHistory::class);
    }
}

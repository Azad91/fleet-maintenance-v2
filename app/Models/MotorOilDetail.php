<?php

namespace App\Models;

use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Motor oil change catalog — now garage-scoped.
 *
 * Each garage maintains its own catalog because different garages
 * operate different bus fleets (BMC gas, YUTONG diesel, BYD electric,
 * IVECO, ...) with completely different service intervals. The
 * previous global catalog leaked one garage's schedule into every
 * other garage.
 *
 * The HasGarageScope trait:
 *   - filters every query by the current garage context
 *   - auto-populates garage_id/company_id on creation
 *   - blocks writes when no garage context is available
 */
class MotorOilDetail extends Model
{
    use HasFactory, HasGarageScope;

    protected $fillable = [
        'garage_id',
        'company_id',
        'part_code',
        'part_name',
        'unit',
        'quantity',
        'km',
        'count',
    ];

    public function getTotalMiqdarAttribute()
    {
        return $this->quantity * $this->count;
    }
}

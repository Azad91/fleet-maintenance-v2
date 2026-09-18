<?php

namespace App\Models;

use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Motor oil change catalog — garage- AND brand-scoped.
 *
 * Each garage maintains its own catalog per brand because different
 * manufacturers have completely different service intervals
 * (BMC gas, YUTONG diesel, BYD electric, IVECO, ...).
 *
 * The HasGarageScope trait:
 *   - filters every query by the current garage context
 *   - auto-populates garage_id/company_id on creation
 *   - blocks writes when no garage context is available
 *
 * Brand isolation is enforced at the query layer
 * (->where('brand_id', $brandId)) by the controller.
 */
class MotorOilDetail extends Model
{
    use HasFactory, HasGarageScope;

    protected $fillable = [
        'garage_id',
        'company_id',
        'brand_id',
        'part_code',
        'part_name',
        'unit',
        'quantity',
        'km',
        'count',
    ];

    public function brand()
    {
        return $this->belongsTo(BusBrand::class, 'brand_id');
    }

    public function getTotalMiqdarAttribute()
    {
        return $this->quantity * $this->count;
    }
}

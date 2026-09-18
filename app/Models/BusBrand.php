<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\HasCreatedBy;
use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A bus manufacturer within a single garage.
 *
 * Each garage maintains its own brand list. The HasGarageScope trait
 * filters every query by the current garage and auto-populates
 * garage_id / company_id on creation.
 *
 * Examples: BMC, Yutong, Iveco, Mercedes-Benz, MAN, ...
 */
class BusBrand extends Model
{
    use Auditable, HasCreatedBy, HasFactory, HasGarageScope, SoftDeletes;

    protected $fillable = [
        'garage_id',
        'company_id',
        'name',
        'code',
        'is_active',
        // 'created_by' is intentionally NOT fillable — it is set
        // automatically by the HasCreatedBy trait, and leaving it out
        // prevents mass-assignment spoofing.
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ==================== RELATIONS ====================

    public function buses()
    {
        return $this->hasMany(Bus::class, 'brand_id');
    }

    public function motorOilDetails()
    {
        return $this->hasMany(MotorOilDetail::class, 'brand_id');
    }

    public function serviceTemplates()
    {
        return $this->hasMany(ServiceTemplate::class, 'brand_id');
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\HasCreatedBy;
use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A mobile workshop belonging to a single garage.
 *
 * Uses HasGarageScope so every query is automatically filtered by
 * the current garage, and so garage_id/company_id are populated
 * on creation without any explicit controller logic.
 */
class ServiceVehicle extends Model
{
    use Auditable, HasCreatedBy, HasFactory, HasGarageScope, SoftDeletes;

    protected $fillable = [
        'garage_id',
        'company_id',
        'name',
        'plate_number',
        'driver_name',
        'phone',
        'is_active',
        'notes',
        // 'created_by' is intentionally NOT fillable — it is set
        // automatically by the HasCreatedBy trait.
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Stock currently held on this service vehicle.
     * Ordered by name for stable UI presentation.
     */
    public function stocks()
    {
        return $this->hasMany(ServiceVehicleStock::class)->orderBy('name');
    }

    /**
     * Total quantity across every stock row. Useful for the
     * show-page KPI card.
     */
    public function getTotalStockQuantityAttribute(): int
    {
        return (int) $this->stocks()->sum('quantity');
    }
}

<?php

namespace App\Models;

use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Model;

/**
 * Stock held on a service vehicle.
 *
 * Uses HasGarageScope so every query is filtered by the current
 * garage, and so garage_id/company_id are populated automatically
 * on creation.
 */
class ServiceVehicleStock extends Model
{
    use HasGarageScope;

    protected $fillable = [
        'service_vehicle_id',
        'garage_id',
        'company_id',
        'code',
        'name',
        'category',
        'unit',
        'quantity',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function serviceVehicle()
    {
        return $this->belongsTo(ServiceVehicle::class);
    }

    public function scopeSearch($query, ?string $search)
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('code', 'ILIKE', "%{$search}%")
                ->orWhere('name', 'ILIKE', "%{$search}%");
        });
    }
}

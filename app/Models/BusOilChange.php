<?php

namespace App\Models;

use App\Enums\OilType;
use App\Models\Traits\Auditable;
use App\Models\Traits\HasCreatedBy;
use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusOilChange extends Model
{
    use Auditable, HasCreatedBy, HasFactory, HasGarageScope, SoftDeletes;

    protected $fillable = [
        'bus_id',
        'oil_type',
        'oil_brand',
        'scheduled_km',
        'actual_km',
        'interval_km',
        'changed_at',
        'notes',
        'garage_id',
        'company_id',
    ];

    protected $casts = [
        'oil_type'     => OilType::class,
        'scheduled_km' => 'integer',
        'actual_km'    => 'integer',
        'interval_km'  => 'integer',
        'changed_at'   => 'date',
    ];

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function scopeOfType(Builder $query, OilType|string $type): Builder
    {
        $value = $type instanceof OilType ? $type->value : $type;

        return $query->where('oil_type', $value);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('actual_km')->orderByDesc('id');
    }

    public function nextDueKm(): int
    {
        return $this->actual_km + $this->interval_km;
    }
}

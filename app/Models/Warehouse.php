<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\HasCreatedBy;
use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use Auditable, HasCreatedBy, HasFactory, HasGarageScope, SoftDeletes;

    protected $fillable = [
        'garage_id',
        'company_id',
        'code',
        'name',
        'category',
        'unit',
        'is_quarantine',
        'quantity',
        'minimum_quantity',
        'price',
        'supplier',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'minimum_quantity' => 'integer',
        'price' => 'decimal:2',
        'is_quarantine' => 'boolean',
    ];

        public function scopeActiveStock($query)
    {
        return $query->where('is_quarantine', false);
    }

    public function scopeQuarantine($query)
    {
        return $query->where('is_quarantine', true);
    }
}

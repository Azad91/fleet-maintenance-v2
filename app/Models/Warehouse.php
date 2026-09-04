<?php

namespace App\Models;

use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\Auditable;

class Warehouse extends Model
{
    use HasFactory, HasGarageScope, SoftDeletes, Auditable;

    protected $fillable = [
        'garage_id',
        'company_id',
        'code',
        'name',
        'category',
        'unit',
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
    ];
}

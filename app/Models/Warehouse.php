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
        'created_by',
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
        'quantity'         => 'integer',
        'minimum_quantity' => 'integer',
        'price'            => 'decimal:2',
    ];
}

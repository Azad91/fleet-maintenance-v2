<?php

namespace App\Models;

use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\Auditable;

class Warehouse extends Model
{
    use HasGarageScope, SoftDeletes, Auditable;

    protected $fillable = [
        'garage_id',
        'company_id',
        'code',              // əvvəl: kod
        'name',              // əvvəl: ad
        'category',          // əvvəl: kateqoriya
        'unit',              // əvvəl: olcu_vahidi
        'quantity',          // əvvəl: miqdar
        'minimum_quantity',  // əvvəl: minimum_miqdar
        'price',             // əvvəl: qiymet
        'supplier',          // əvvəl: tedarikci
        'notes',             // əvvəl: qeyd
    ];

    protected $casts = [
        'quantity' => 'integer',
        'minimum_quantity' => 'integer',
        'price' => 'decimal:2',
    ];
}

<?php

namespace App\Models;

use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasGarageScope;

    protected $fillable = [
        'garage_id',
        'company_id',
        'kod',
        'ad',
        'kateqoriya',
        'olcu_vahidi',
        'miqdar',
        'minimum_miqdar',
        'qiymet',
        'tedarikci',
        'qeyd',
    ];

    protected $casts = [
        'miqdar' => 'integer',
        'minimum_miqdar' => 'integer',
        'qiymet' => 'decimal:2',
    ];
}

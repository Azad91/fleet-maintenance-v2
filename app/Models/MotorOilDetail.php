<?php

namespace App\Models;

use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}

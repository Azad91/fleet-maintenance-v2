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
        'name',
        'code',
        'type',
        'address',
        'is_active',
    ];

    public function garage()
    {
        return $this->belongsTo(Garage::class);
    }

    public function stockBalances()
    {
        return $this->hasMany(StockBalance::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}

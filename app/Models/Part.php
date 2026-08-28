<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    protected $fillable = ['kod', 'ad', 'kateqoriya', 'olcu_vahidi', 'qiymet', 'tedarikci'];

    public function stockBalances()
    {
        return $this->hasMany(StockBalance::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}

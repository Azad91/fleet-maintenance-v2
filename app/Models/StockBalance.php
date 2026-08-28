<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockBalance extends Model
{
    protected $fillable = ['warehouse_id', 'part_id', 'quantity', 'minimum_quantity'];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }
}

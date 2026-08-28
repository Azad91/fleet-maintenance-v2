<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'warehouse_id', 'part_id', 'complaint_id', 'type',
        'quantity', 'balance_after', 'from_warehouse_id',
        'to_warehouse_id', 'note', 'created_by'
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplaintDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'complaint_id',
        'shikayet_index',
        'code',
        'name',
        'stock_quantity',
        'used_quantity',
        'employee_id',
        'notes',
    ];

    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}

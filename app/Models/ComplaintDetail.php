<?php

namespace App\Models;

use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplaintDetail extends Model
{
    use SoftDeletes, HasGarageScope;

    protected $fillable = [
        'complaint_id',
        'shikayet_index',
        'code',
        'name',
        'stock_quantity',
        'used_quantity',
        'employee_id',
        'notes',
        'garage_id',
        'company_id',
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

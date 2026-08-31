<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplaintDetail extends Model
{
    protected $fillable = [
        'complaint_id',
        'shikayet_index',
        'kodu',
        'adi',
        'depo_miqdari',
        'islenen_miqdar',
        'employee_id',
        'qeyd',
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplaintDetail extends Model
{
    protected $fillable = [
        'complaint_id',
        'shikayet_index',    // hələlik saxlanılır (sonra 'issue_index' olacaq)
        'code',              // əvvəl: kodu
        'name',              // əvvəl: adi
        'stock_quantity',    // əvvəl: depo_miqdari
        'used_quantity',     // əvvəl: islenen_miqdar
        'employee_id',
        'notes',             // əvvəl: qeyd
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

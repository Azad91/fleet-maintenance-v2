<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplaintItem extends Model
{
    protected $fillable = ['complaint_id', 'description', 'type'];

    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }
}

<?php

namespace App\Models;

use App\Models\Traits\HasCreatedBy;
use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyKmRecord extends Model
{
    use HasCreatedBy, HasFactory, HasGarageScope, SoftDeletes;

    protected $fillable = [
        'bus_id',
        'date',
        'km',
        'notes',
        'garage_id',
        'company_id',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }
}

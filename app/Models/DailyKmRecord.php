<?php

namespace App\Models;

use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyKmRecord extends Model
{
    use HasFactory, SoftDeletes;
    use HasGarageScope;

    protected $fillable = [
        'bus_id', 'tarix', 'km', 'qeyd', 'garage_id', 'company_id'
    ];

    protected $casts = [
        'tarix' => 'date',
    ];

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }
}

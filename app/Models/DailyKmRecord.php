<?php

namespace App\Models;

use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyKmRecord extends Model
{
    use HasFactory;
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

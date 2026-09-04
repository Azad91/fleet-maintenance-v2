<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MotorOilDetail
 *
 * 📌 QEYD: Bu model qlobal kataloqdur, HasGarageScope tətbiq edilmir.
 */
class MotorOilDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'part_code',    // əvvəl: detal_kodu
        'part_name',    // əvvəl: detal_adi
        'unit',         // əvvəl: olcu_vahidi
        'quantity',     // əvvəl: miqdar
        'km',
        'count',        // əvvəl: say
    ];

    public function getTotalMiqdarAttribute()
    {
        return $this->quantity * $this->count;
    }
}

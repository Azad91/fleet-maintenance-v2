<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ServiceTemplate
 *
 * 📌 QEYD: Bu model qlobal kataloqdur, HasGarageScope tətbiq edilmir.
 * Bütün qarajlar üçün ortaq servis şablonlarını saxlayır.
 */
class ServiceTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'default_km_interval', 'details'];

    protected $casts = [
        'details' => 'array',
    ];

    public function busIntervals()
    {
        return $this->hasMany(BusServiceInterval::class);
    }

    public function histories()
    {
        return $this->hasMany(BusServiceHistory::class);
    }
}

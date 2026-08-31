<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ComplaintType
 *
 * 📌 QEYD: Bu model qlobal kataloqdur, HasGarageScope tətbiq edilmir.
 * Bütün qarajlar üçün ortaq şikayət növlərini saxlayır.
 */
class ComplaintType extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'ILIKE', "%{$search}%");
    }
}

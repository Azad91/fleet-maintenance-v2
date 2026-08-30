<?php

namespace App\Models;

use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasFactory, SoftDeletes;
    use HasGarageScope;

    protected $fillable = [
        'kodu', 'ad', 'soyad', 'telefon', 'vezifesi', 'aktiv', 'qeyd', 'garage_id', 'company_id'
    ];

    protected $casts = [
        'aktiv' => 'boolean',
    ];

    public function getFullNameAttribute()
    {
        if (empty($this->soyad)) {
            return $this->ad;
        }
        return $this->ad . ' ' . $this->soyad;
    }

    public function getFullNameWithCodeAttribute()
    {
        return $this->kodu . ' - ' . $this->full_name;
    }

    public function scopeActive($query)
    {
        return $query->where('aktiv', true);
    }
}

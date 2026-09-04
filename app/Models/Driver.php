<?php

namespace App\Models;

use App\Models\Traits\HasGarageScope;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasFactory, SoftDeletes, HasGarageScope, Auditable;

    protected $fillable = [
        'code', 'first_name', 'last_name', 'phone', 'position', 'is_active', 'notes', 'garage_id', 'company_id'
        // əvvəl: kodu, ad, soyad, telefon, vezifesi, aktiv, qeyd
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getFullNameAttribute()
    {
        if (empty($this->last_name)) {
            return $this->first_name;
        }
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getFullNameWithCodeAttribute()
    {
        return $this->code . ' - ' . $this->full_name;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

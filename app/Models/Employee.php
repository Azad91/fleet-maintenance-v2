<?php

namespace App\Models;

use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\Auditable;

class Employee extends Model
{
    use HasFactory, SoftDeletes, HasGarageScope, Auditable;

    protected $fillable = [
        'first_name', 'last_name', 'position', 'is_active', 'notes', 'garage_id', 'company_id'
        // əvvəl: ad, soyad, vezifesi, aktiv, qeyd
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ==================== ACCESSORS ====================
    public function getFullNameAttribute()
    {
        if (empty($this->last_name)) {
            return $this->first_name;
        }
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getFullNameWithPositionAttribute()
    {
        $name = $this->first_name;
        if (!empty($this->last_name)) {
            $name .= ' ' . $this->last_name;
        }
        return $name . ' (' . $this->position . ')';
    }

    // ==================== RELATIONSHIPS ====================
    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    // ==================== SCOPES ====================
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

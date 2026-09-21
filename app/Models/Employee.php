<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use Auditable, HasFactory, HasGarageScope, SoftDeletes;

    protected $fillable = [
        'code',
        'first_name',
        'last_name',
        'position',
        'is_active',
        'notes',
        'garage_id',
        'company_id',
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

        return $this->first_name.' '.$this->last_name;
    }

    public function getFullNameWithPositionAttribute()
    {
        $name = $this->first_name;

        if (! empty($this->last_name)) {
            $name .= ' '.$this->last_name;
        }

        // Skip the parenthesis entirely when position is empty or
        // whitespace — avoids "Elshad Mammadov ()" leaking into
        // dropdowns, PDFs and reports.
        $position = trim((string) $this->position);

        if ($position === '') {
            return $name;
        }

        return $name.' ('.$position.')';
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

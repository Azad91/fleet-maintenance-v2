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

    /**
     * Human-readable display name with position label.
     *
     * The `position` column stores a config key (e.g. "master",
     * "electrician"), not the label shown to users. The label lives
     * in config('settings.employee_positions') so that admins can
     * update the wording without a migration.
     *
     * Resolving the label here means every consumer — the complaint
     * form's employee dropdown, the show page detail rows, the PDF
     * act, and every report — displays the same friendly label
     * ("🔧 Master") instead of the raw key ("master").
     *
     * Unknown keys (custom positions typed directly into the DB)
     * fall through unchanged, preserving the previous behavior.
     *
     * @return string e.g. "Əli Məmmədov (🔧 Master)"
     */
    public function getFullNameWithPositionAttribute(): string
    {
        $name = $this->first_name;

        if (! empty($this->last_name)) {
            $name .= ' '.$this->last_name;
        }

        $positionKey = trim((string) $this->position);

        if ($positionKey === '') {
            return $name;
        }

        $labels = config('settings.employee_positions', []);
        $positionLabel = $labels[$positionKey] ?? $positionKey;

        return $name.' ('.$positionLabel.')';
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

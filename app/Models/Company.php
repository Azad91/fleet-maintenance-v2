<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\HasCreatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use Auditable, HasCreatedBy, HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'logo',
        'is_active',
        // 'created_by' is intentionally NOT fillable — it is set
        // automatically by the HasCreatedBy trait, and leaving it out
        // prevents mass-assignment spoofing.
    ];

    public function garages()
    {
        return $this->hasMany(Garage::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'company_user')
            ->withPivot('role', 'is_active')
            ->withTimestamps();
    }

    public function directors()
    {
        return $this->users()
            ->wherePivot('role', 'director')
            ->wherePivot('is_active', true);
    }

    /**
     * A Company IS a company, so its audit logs are attached to
     * its own id (not a related one). The trait's default resolver
     * would return null for `company_id` since the Company model
     * has no such attribute.
     */
    protected function resolveAuditCompanyId(): ?int
    {
        return $this->id ? (int) $this->id : null;
    }
}
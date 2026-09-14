<?php

namespace App\Models;

use App\Enums\RoleEnum;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Auditable, HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * Mass-assignable attributes.
     *
     * NOTE: 'role' is intentionally NOT fillable. It must be set explicitly
     * via promoteToSuperAdmin() / demoteToRegularUser() helpers, or via
     * forceFill(['role' => ...]) in trusted contexts (seeders, factories).
     *
     * This prevents privilege escalation through mass assignment payloads
     * such as POST ['role' => 'super_admin'].
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_code',
        'pin',
        'pin_is_default',
        'is_active',
        'current_garage_id',
        'current_company_id',
        'last_selected_garage_at',
    ];

    /**
     * Additional fields excluded from audit logs (in addition to the
     * base list in the Auditable trait: password, remember_token,
     * created_at, updated_at, deleted_at).
     *
     * - pin:                      bcrypt hash, never log
     * - email_verified_at:        framework noise
     * - current_garage_id:        changes on every garage switch
     * - current_company_id:       changes on every garage switch
     * - last_selected_garage_at:  changes on every garage switch
     *
     * NOTE: `pin_is_default` is intentionally NOT excluded — it flips
     * from true to false when the user sets their own PIN, which is a
     * meaningful security event worth auditing.
     */
    protected static array $auditExcluded = [
        'pin',
        'email_verified_at',
        'current_garage_id',
        'current_company_id',
        'last_selected_garage_at',
    ];

    /**
     * Default attribute values for new model instances.
     */
    protected $attributes = [
        'role' => RoleEnum::USER->value,
        'is_active' => true,
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ==================== GLOBAL ROLE CHECKS ====================

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isRegularUser(): bool
    {
        return $this->role === 'user';
    }

    // ==================== GARAGE-LEVEL ROLE CHECKS ====================

    public function hasGarageRole(string|array $roles, ?int $garageId = null): bool
    {
        $roles = (array) $roles;
        $garageId ??= Garage::getCurrentId();

        if (! $garageId) {
            return false;
        }

        return $this->garages()
            ->whereKey($garageId)
            ->wherePivot('is_active', true)
            ->wherePivotIn('role', $roles)
            ->exists();
    }

    public function isGarageAdmin(?int $garageId = null): bool
    {
        return $this->hasGarageRole(RoleEnum::ADMIN->value, $garageId);
    }

    public function isAnyManager(?int $garageId = null): bool
    {
        return $this->hasGarageRole(RoleEnum::managerRoles(), $garageId);
    }

    public function isAnyWorker(?int $garageId = null): bool
    {
        return $this->hasGarageRole(RoleEnum::workerRoles(), $garageId);
    }

    public function hasDomainRole(string $domain, ?int $garageId = null): bool
    {
        $domainRoles = match ($domain) {
            'complaint' => RoleEnum::complaintRoles(),
            'warehouse' => RoleEnum::warehouseRoles(),
            'daily_km' => RoleEnum::dailyKmRoles(),
            'daily_status' => RoleEnum::dailyStatusRoles(),
            default => [],
        };

        if (empty($domainRoles)) {
            return false;
        }

        return $this->hasGarageRole($domainRoles, $garageId);
    }

    // ==================== COMPANY-LEVEL ROLE CHECKS ====================

    public function isDirector(): bool
    {
        return $this->companies()
            ->wherePivot('role', 'director')
            ->wherePivot('is_active', true)
            ->exists();
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_user')
            ->withPivot('role', 'is_active')
            ->withTimestamps();
    }

    public function activeDirectorCompany(): ?Company
    {
        return $this->companies()
            ->wherePivot('role', 'director')
            ->wherePivot('is_active', true)
            ->first();
    }

    // ==================== GARAGE MEMBERSHIP ====================

    public function garages()
    {
        return $this->belongsToMany(Garage::class, 'garage_user')
            ->withPivot('role', 'is_active')
            ->withTimestamps();
    }

    public function currentGarage()
    {
        return $this->belongsTo(Garage::class, 'current_garage_id');
    }

    public function currentCompany()
    {
        return $this->belongsTo(Company::class, 'current_company_id');
    }

    public function setCurrentGarage(Garage $garage): void
    {
        $this->update([
            'current_garage_id' => $garage->id,
            'current_company_id' => $garage->company_id,
            'last_selected_garage_at' => now(),
        ]);

        session([
            'current_garage_id' => $garage->id,
            'current_garage_name' => $garage->name,
            'current_company_id' => $garage->company_id,
            'current_company_name' => $garage->company->name,
        ]);
    }

    // ==================== HELPERS ====================

    public function hasGarageAccess(int $garageId): bool
    {
        if ($this->isSuperAdmin()) {
            return Garage::whereKey($garageId)->exists();
        }

        return $this->garages()
            ->whereKey($garageId)
            ->wherePivot('is_active', true)
            ->exists();
    }

    public function getCurrentGarageRole(): ?string
    {
        $garageId = Garage::getCurrentId();
        if (! $garageId) {
            return null;
        }

        $membership = $this->garages()
            ->whereKey($garageId)
            ->first();

        return $membership?->pivot->role;
    }

    public function getAllGarageRoles(): array
    {
        return $this->garages()
            ->wherePivot('is_active', true)
            ->get()
            ->map(fn ($garage) => [
                'garage_id' => $garage->id,
                'garage_name' => $garage->name,
                'role' => $garage->pivot->role,
            ])
            ->toArray();
    }

    // ==================== ROLE MANAGEMENT ====================

    public function promoteToSuperAdmin(): static
    {
        $this->forceFill(['role' => RoleEnum::SUPER_ADMIN->value]);

        return $this;
    }

    public function demoteToRegularUser(): static
    {
        $this->forceFill(['role' => RoleEnum::USER->value]);

        return $this;
    }
}

<?php

namespace App\Models;

use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_code',
        'pin',
        'pin_is_default',
        'role',              // 'super_admin' | 'user'
        'is_active',
        'current_garage_id',
        'current_company_id',
        'last_selected_garage_at',
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

    /**
     * Super Admin check – based only on users.role column.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Returns true if the user's primary role is 'user'.
     */
    public function isRegularUser(): bool
    {
        return $this->role === 'user';
    }

    // ==================== GARAGE-LEVEL ROLE CHECKS ====================

    /**
     * Garage-level role check – this is the CORE authorization mechanism.
     * All business roles are checked against the garage_user pivot table.
     *
     * NOTE: Super Admins bypass policies at the Gate level (see AuthServiceProvider).
     * This method checks ACTUAL membership — it does not auto-approve for super admins.
     * If you need a "does this user have access to this garage?" check that also
     * respects super admin, use `hasGarageAccess()` instead.
     */
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

    /**
     * True if the user has ADMIN role in the given (or current) garage.
     */
    public function isGarageAdmin(?int $garageId = null): bool
    {
        return $this->hasGarageRole(RoleEnum::ADMIN->value, $garageId);
    }

    /**
     * True if the user is a MANAGER in any domain of the current garage.
     */
    public function isAnyManager(?int $garageId = null): bool
    {
        return $this->hasGarageRole(RoleEnum::managerRoles(), $garageId);
    }

    /**
     * True if the user is a WORKER in any domain of the current garage.
     */
    public function isAnyWorker(?int $garageId = null): bool
    {
        return $this->hasGarageRole(RoleEnum::workerRoles(), $garageId);
    }

    /**
     * True if the user has ANY role (manager or worker) in the given domain.
     */
    public function hasDomainRole(string $domain, ?int $garageId = null): bool
    {
        $domainRoles = match ($domain) {
            'complaint'    => RoleEnum::complaintRoles(),
            'warehouse'    => RoleEnum::warehouseRoles(),
            'daily_km'     => RoleEnum::dailyKmRoles(),
            'daily_status' => RoleEnum::dailyStatusRoles(),
            default        => [],
        };

        if (empty($domainRoles)) {
            return false;
        }

        return $this->hasGarageRole($domainRoles, $garageId);
    }

    // ==================== COMPANY-LEVEL ROLE CHECKS ====================

    /**
     * True if the user is an active Director of any company.
     *
     * Directors do not have garage memberships — they operate at the
     * company level and get a dedicated read-only dashboard.
     */
    public function isDirector(): bool
    {
        return $this->companies()
            ->wherePivot('role', 'director')
            ->wherePivot('is_active', true)
            ->exists();
    }

    /**
     * Companies this user belongs to (via company_user pivot).
     */
    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_user')
            ->withPivot('role', 'is_active')
            ->withTimestamps();
    }

    /**
     * Return the first active Director company, or null.
     */
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
            'current_garage_id'       => $garage->id,
            'current_company_id'      => $garage->company_id,
            'last_selected_garage_at' => now(),
        ]);

        session([
            'current_garage_id'    => $garage->id,
            'current_garage_name'  => $garage->name,
            'current_company_id'   => $garage->company_id,
            'current_company_name' => $garage->company->name,
        ]);
    }

    // ==================== HELPERS ====================

    /**
     * True if the user has ANY access to the given garage —
     * either as a member OR as a super admin.
     */
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

    /**
     * Return the user's role in the current garage, or null if not a member.
     */
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

    /**
     * Return all active garage roles as an array of
     * ['garage_id' => int, 'garage_name' => string, 'role' => string].
     */
    public function getAllGarageRoles(): array
    {
        return $this->garages()
            ->wherePivot('is_active', true)
            ->get()
            ->map(fn ($garage) => [
                'garage_id'   => $garage->id,
                'garage_name' => $garage->name,
                'role'        => $garage->pivot->role,
            ])
            ->toArray();
    }
}

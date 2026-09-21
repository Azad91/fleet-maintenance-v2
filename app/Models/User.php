<?php

namespace App\Models;

use App\Enums\RoleEnum;
use App\Models\Traits\Auditable;
use App\Services\GarageContext;
use App\Support\TwoFactor\TwoFactorManager;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use Auditable, HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * Per-instance cache for isDirector().
     *
     * The layout calls isDirector() on every request to decide the
     * sidebar/topbar rendering. Without cache, that's one DB query
     * per call. The cache resets when the model is rehydrated.
     *
     * null  = not yet computed
     * true  = user is an active director
     * false = user is not a director
     */
    private ?bool $cachedIsDirector = null;

    /**
     * Invalidate the per-instance isDirector() cache.
     *
     * Call this immediately after any pivot change on the
     * `company_user` table when the same User instance will be
     * inspected in the same request. Onboarding services and the
     * AssignmentController already call this; new call sites that
     * mutate the pivot should do the same.
     */
    public function forgetDirectorCache(): static
    {
        $this->cachedIsDirector = null;

        return $this;
    }
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
        // 2FA columns are NOT fillable — set them only through
        // twoFactorManager operations in the setup controller.
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
        'pin',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'two_factor_secret' => 'encrypted',
        'two_factor_recovery_codes' => 'encrypted:array',
        'two_factor_confirmed_at' => 'datetime',
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
        $garageId ??= GarageContext::resolveGarageId();

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

    /**
     * True when the user is an active Company Director.
     *
     * Result is cached per instance because the sidebar layout and
     * PostLoginRedirector both call this on every request. The cache
     * is invalidated by forgetDirectorCache(), which callers must
     * invoke after mutating the `company_user` pivot on this same
     * instance.
     */
    public function isDirector(): bool
    {
        return $this->cachedIsDirector ??= $this->companies()
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
        $garageId = GarageContext::resolveGarageId();
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

    // ==================== TWO-FACTOR AUTHENTICATION ====================

    /**
     * True when MFA is fully set up and confirmed for this user.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null
            && $this->two_factor_confirmed_at !== null;
    }

    /**
     * True when a secret exists but hasn't been confirmed yet
     * (user is in the middle of setup).
     */
    public function hasPendingTwoFactorSetup(): bool
    {
        return $this->two_factor_secret !== null
            && $this->two_factor_confirmed_at === null;
    }

    /**
     * True when the user needs to be forced into MFA setup.
     *
     * Current rule (see audit & security plan): only SuperAdmin is
     * required to use MFA. All other roles are unaffected.
     */
    public function requiresTwoFactorSetup(): bool
    {
        return $this->isSuperAdmin() && ! $this->hasTwoFactorEnabled();
    }

    /**
     * Verify a TOTP code against this user's secret.
     */
    public function verifyTwoFactorCode(string $code): bool
    {
        if (! $this->two_factor_secret) {
            return false;
        }

        return app(TwoFactorManager::class)->verifyCode(
            $this->two_factor_secret,
            $code,
        );
    }

    /**
     * Verify a recovery code. On success, the used code is removed
     * from the stored list (one-time use).
     *
     * Defensive: if the value came back as a JSON string (this can happen
     * if the `encrypted:array` cast was not applied for any reason — a
     * stale model instance, a missing cast, or data written before the
     * cast existed), decode it inline so the operation still works.
     */
    public function useRecoveryCode(string $code): bool
    {
        $codes = $this->two_factor_recovery_codes ?? [];

        // Defensive: allow JSON-encoded string fallback.
        if (is_string($codes)) {
            $codes = json_decode($codes, true) ?? [];
        }

        if (! is_array($codes)) {
            return false;
        }

        foreach ($codes as $index => $hashedCode) {
            if (Hash::check($code, $hashedCode)) {
                unset($codes[$index]);

                $this->forceFill([
                    'two_factor_recovery_codes' => array_values($codes),
                ])->save();

                return true;
            }
        }

        return false;
    }
}

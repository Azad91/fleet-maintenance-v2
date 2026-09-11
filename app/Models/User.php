<?php

namespace App\Models;

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
     * Super Admin yoxlanışı – yalnız users.role ilə
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * İstifadəçinin əsas rolu 'user'-dursa true
     */
    public function isRegularUser(): bool
    {
        return $this->role === 'user';
    }

    // ==================== GARAGE-LEVEL ROLE CHECKS ====================

    /**
     * Qaraj səviyyəsində rol yoxlanışı – BURASI ƏSAS ROL MEXANİZMİDİR
     * Bütün business rollar buradan yoxlanılır
     */
    public function hasGarageRole(string|array $roles, ?int $garageId = null): bool
    {
        // Super Admin hər zaman true qaytarır
        if ($this->isSuperAdmin()) {
            return true;
        }

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
     * İstifadəçinin cari qarajda ADMIN roluna sahib olub-olmaması
     */
    public function isGarageAdmin(?int $garageId = null): bool
    {
        return $this->hasGarageRole('admin', $garageId);
    }

    /**
     * İstifadəçinin cari qarajda MANAGER roluna sahib olub-olmaması
     */
    public function isGarageManager(?int $garageId = null): bool
    {
        return $this->hasGarageRole('manager', $garageId);
    }

    /**
     * İstifadəçinin cari qarajda yalnız baxış (viewer) roluna sahib olub-olmaması
     */
    public function isViewer(?int $garageId = null): bool
    {
        return $this->hasGarageRole('viewer', $garageId);
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

    // ==================== HELPER ====================

    /**
     * İstifadəçinin müəyyən bir qaraja üzv olub-olmaması
     */
    public function hasGarageAccess(int $garageId): bool
    {
        return $this->garages()
            ->whereKey($garageId)
            ->wherePivot('is_active', true)
            ->exists();
    }

    /**
     * İstifadəçinin cari qarajdakı rolunu qaytarır
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
     * İstifadəçinin bütün qaraj rollarını array olaraq qaytarır
     */
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
}

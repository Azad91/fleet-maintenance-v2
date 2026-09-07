<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
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

    // ==================== ROLE CHECKS ====================

    /**
     * Super Admin yoxlanışı (bütün sistemə tam nəzarət)
     * ✅ DƏYİŞDİRİLDİ: Yalnız 'super_admin' rolu super admindir.
     * 'admin' rolu yalnız qaraj səviyyəsində istifadə olunur.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * İstifadəçinin rolu var?
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * İstifadəçi hər hansı bir rola sahibdir?
     */
    public function hasAnyRole(string|array $roles): bool
    {
        return $this->hasGarageRole($roles);
    }

    /**
     * Qaraj səviyyəsində rol yoxlanışı
     */
    public function hasGarageRole(string|array $roles, ?int $garageId = null): bool
    {
        // Super Admin hər zaman true qaytarır
        if ($this->isSuperAdmin()) {
            return true;
        }

        $roles = (array) $roles;
        $garageId ??= \App\Models\Garage::getCurrentId();

        if (! $garageId) {
            return false;
        }

        return $this->garages()
            ->whereKey($garageId)
            ->wherePivot('is_active', true)
            ->wherePivotIn('role', $roles)
            ->exists();
    }


    // ==================== RELATIONSHIPS ====================

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
}

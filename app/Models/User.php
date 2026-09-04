<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isBus()
    {
        return $this->role === 'bus';
    }

    public function isComplaint()
    {
        return $this->role === 'complaint';
    }

    public function isWarehouse()
    {
        return $this->role === 'warehouse';
    }

    public function isDirectorate()
    {
        return $this->role === 'directorate';
    }

    public function hasRole($role)
    {
        return $this->role === $role;
    }

public function hasAnyRole($roles)
{
        return $this->hasGarageRole($roles);
    }

    public function hasGarageRole(string|array $roles, ?int $garageId = null): bool
    {
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
    // app/Models/User.php - class User daxilinə əlavə et:

public function garages()
{
    return $this->belongsToMany(Garage::class, 'garage_user')->withPivot('role', 'is_active')->withTimestamps();
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

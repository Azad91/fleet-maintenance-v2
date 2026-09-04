<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Driver;
use App\Enums\RoleEnum;

class DriverPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function view(User $user, Driver $driver): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function update(User $user, Driver $driver): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function delete(User $user, Driver $driver): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function import(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function export(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }
}

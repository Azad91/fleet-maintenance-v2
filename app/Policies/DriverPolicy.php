<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Driver;
use App\Models\User;
use App\Services\GarageContext;

class DriverPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function view(User $user, ?Driver $driver = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($driver && $driver->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function update(User $user, ?Driver $driver = null): bool
    {
        return $this->view($user, $driver);
    }

    public function delete(User $user, ?Driver $driver = null): bool
    {
        return $this->view($user, $driver);
    }

    public function import(User $user): bool
    {
        return $this->create($user);
    }

    public function export(User $user): bool
    {
        return $this->create($user);
    }
}

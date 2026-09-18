<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\BusBrand;
use App\Models\User;
use App\Services\GarageContext;

class BusBrandPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function view(User $user, ?BusBrand $brand = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($brand && $brand->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function update(User $user, ?BusBrand $brand = null): bool
    {
        return $this->view($user, $brand);
    }

    public function delete(User $user, ?BusBrand $brand = null): bool
    {
        return $this->view($user, $brand);
    }
}

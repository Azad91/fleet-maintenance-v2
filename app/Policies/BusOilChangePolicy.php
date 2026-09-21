<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\BusOilChange;
use App\Models\User;
use App\Services\GarageContext;

class BusOilChangePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function view(User $user, ?BusOilChange $change = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($change && $change->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, ?BusOilChange $change = null): bool
    {
        return $this->view($user, $change);
    }

    public function delete(User $user, ?BusOilChange $change = null): bool
    {
        return $this->view($user, $change);
    }

    public function import(User $user): bool
    {
        return $this->viewAny($user);
    }
}

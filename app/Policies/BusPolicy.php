<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Bus;
use App\Enums\RoleEnum;

class BusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::BUS->value,
            RoleEnum::DIRECTORATE->value,
        ]);
    }

    public function view(User $user, Bus $bus): bool
    {
        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::BUS->value,
            RoleEnum::DIRECTORATE->value,
        ]);
    }

    public function create(User $user): bool
    {
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function update(User $user, Bus $bus): bool
    {
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function delete(User $user, Bus $bus): bool
    {
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function import(User $user): bool
    {
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }
}

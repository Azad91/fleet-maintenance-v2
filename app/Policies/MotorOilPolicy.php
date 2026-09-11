<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\MotorOilDetail;
use App\Models\User;

class MotorOilPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function view(User $user, MotorOilDetail $detail): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, MotorOilDetail $detail): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, MotorOilDetail $detail): bool
    {
        return $this->viewAny($user);
    }

    public function import(User $user): bool
    {
        return $this->viewAny($user);
    }
}

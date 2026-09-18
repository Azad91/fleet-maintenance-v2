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

    public function view(User $user, ?MotorOilDetail $detail = null): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, ?MotorOilDetail $detail = null): bool
    {
        return $this->viewAny($user);
    }

    /**
     * The nullable second parameter allows the blade check
     * `@can('delete', MotorOilDetail::class)` to work — Laravel then
     * calls this method with only the $user argument.
     */
    public function delete(User $user, ?MotorOilDetail $detail = null): bool
    {
        return $this->viewAny($user);
    }

    public function import(User $user): bool
    {
        return $this->viewAny($user);
    }
}

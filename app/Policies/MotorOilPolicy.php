<?php

namespace App\Policies;

use App\Models\User;
use App\Models\MotorOilDetail;
use App\Enums\RoleEnum;

class MotorOilPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
    return $user->hasGarageRole([
        RoleEnum::ADMIN->value,
        RoleEnum->DIRECTORATE->value,
        RoleEnum::WAREHOUSE->value, // ✅ Ehtiyacınıza görə bura əlavə edə bilərsiniz
    ]);
    }

    public function view(User $user, MotorOilDetail $detail): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::DIRECTORATE->value,
        ]);
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function update(User $user, MotorOilDetail $detail): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function delete(User $user, MotorOilDetail $detail): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function import(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }
}

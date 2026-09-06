<?php

namespace App\Policies;

use App\Models\User;
use App\Models\BusDailyStatus;
use App\Enums\RoleEnum;

class BusDailyStatusPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::DAILY_STATUS->value,
            RoleEnum::DIRECTORATE->value,
        ]);
    }

    public function view(User $user, BusDailyStatus $status): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::DAILY_STATUS->value,
            RoleEnum::DIRECTORATE->value,
        ]);
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::DAILY_STATUS->value,
        ]);
    }

    public function update(User $user, BusDailyStatus $status): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::DAILY_STATUS->value,
        ]);
    }

    public function delete(User $user, BusDailyStatus $status): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::DAILY_STATUS->value,
        ]);
    }

    public function import(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::DAILY_STATUS->value,
        ]);
    }
}

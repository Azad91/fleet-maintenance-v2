<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DailyKmRecord;
use App\Enums\RoleEnum;

class DailyKmRecordPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::DAILY_KM->value,
            RoleEnum::DIRECTORATE->value,
        ]);
    }

    public function view(User $user, DailyKmRecord $record): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::DAILY_KM->value,
            RoleEnum::DIRECTORATE->value,
        ]);
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::DAILY_KM->value,
        ]);
    }

    public function update(User $user, DailyKmRecord $record): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::DAILY_KM->value,
        ]);
    }

    public function delete(User $user, DailyKmRecord $record): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::DAILY_KM->value,
        ]);
    }

    public function import(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::DAILY_KM->value,
        ]);
    }
}

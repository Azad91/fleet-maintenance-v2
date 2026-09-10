<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\User;

class DashboardPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::COMPLAINT->value,
            RoleEnum::WAREHOUSE->value,
            RoleEnum::DAILY_KM->value,
            RoleEnum::DAILY_STATUS->value,
            RoleEnum::DIRECTORATE->value,
            RoleEnum::MANAGER->value,
            RoleEnum::VIEWER->value,
        ]);
    }
}
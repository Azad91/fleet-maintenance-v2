<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\BusDailyStatus;
use App\Models\User;
use App\Services\GarageContext;

class BusDailyStatusPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::DAILY_STATUS->value,
            RoleEnum::DIRECTORATE->value,
        ]);
    }

    public function view(User $user, ?BusDailyStatus $status = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($status && $status->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::DAILY_STATUS->value,
            RoleEnum::DIRECTORATE->value,
        ]);
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::DAILY_STATUS->value,
        ]);
    }

    public function update(User $user, ?BusDailyStatus $status = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($status && $status->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::DAILY_STATUS->value,
        ]);
    }

    public function delete(User $user, ?BusDailyStatus $status = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($status && $status->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::DAILY_STATUS->value,
        ]);
    }

    public function import(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::DAILY_STATUS->value,
        ]);
    }
}
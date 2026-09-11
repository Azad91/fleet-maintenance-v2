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

        return $user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            RoleEnum::dailyStatusRoles()
        ));
    }

    public function view(User $user, ?BusDailyStatus $status = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($status && $status->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            RoleEnum::dailyStatusRoles()
        ));
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            RoleEnum::dailyStatusRoles()
        ));
    }

    public function update(User $user, ?BusDailyStatus $status = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($status && $status->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        // Admin + Manager: any record
        if ($user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            [RoleEnum::DAILY_STATUS_MANAGER->value]
        ))) {
            return true;
        }

        // Worker: only own records
        if ($user->hasGarageRole(RoleEnum::DAILY_STATUS_WORKER->value)) {
            return $status && $status->created_by === $user->id;
        }

        return false;
    }

    public function delete(User $user, ?BusDailyStatus $status = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($status && $status->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            [RoleEnum::DAILY_STATUS_MANAGER->value]
        ));
    }

    public function import(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            [RoleEnum::DAILY_STATUS_MANAGER->value]
        ));
    }
}

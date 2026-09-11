<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\DailyKmRecord;
use App\Models\User;
use App\Services\GarageContext;

class DailyKmRecordPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            RoleEnum::dailyKmRoles()
        ));
    }

    public function view(User $user, ?DailyKmRecord $record = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($record && $record->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            RoleEnum::dailyKmRoles()
        ));
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            RoleEnum::dailyKmRoles()
        ));
    }

    public function update(User $user, ?DailyKmRecord $record = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($record && $record->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        // Admin + Manager: any record
        if ($user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            [RoleEnum::DAILY_KM_MANAGER->value]
        ))) {
            return true;
        }

        // Worker: only own records
        if ($user->hasGarageRole(RoleEnum::DAILY_KM_WORKER->value)) {
            return $record && $record->created_by === $user->id;
        }

        return false;
    }

    public function delete(User $user, ?DailyKmRecord $record = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($record && $record->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            [RoleEnum::DAILY_KM_MANAGER->value]
        ));
    }

    public function import(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            [RoleEnum::DAILY_KM_MANAGER->value]
        ));
    }
}

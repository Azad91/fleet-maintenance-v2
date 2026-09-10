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

        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::DAILY_KM->value,
            RoleEnum::DIRECTORATE->value,
        ]);
    }

    public function view(User $user, ?DailyKmRecord $record = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($record && $record->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::DAILY_KM->value,
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
            RoleEnum::DAILY_KM->value,
        ]);
    }

    public function update(User $user, ?DailyKmRecord $record = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($record && $record->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::DAILY_KM->value,
        ]);
    }

    public function delete(User $user, ?DailyKmRecord $record = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($record && $record->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::DAILY_KM->value,
        ]);
    }

    public function import(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::DAILY_KM->value,
        ]);
    }
}
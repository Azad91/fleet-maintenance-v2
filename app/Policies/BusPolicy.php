<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Bus;
use App\Models\User;
use App\Services\GarageContext;

class BusPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function view(User $user, ?Bus $bus = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($bus && $bus->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function update(User $user, ?Bus $bus = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($bus && $bus->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function delete(User $user, ?Bus $bus = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($bus && $bus->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function import(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasGarageRole(RoleEnum::ADMIN->value);
    }
}

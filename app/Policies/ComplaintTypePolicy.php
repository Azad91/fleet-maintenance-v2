<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\ComplaintType;
use App\Models\User;
use App\Services\GarageContext;

class ComplaintTypePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            RoleEnum::complaintRoles()
        ));
    }

    public function view(User $user, ComplaintType $type): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Cross-garage protection: the type must belong to the current garage.
        if ($type->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function update(User $user, ComplaintType $type): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($type->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function delete(User $user, ComplaintType $type): bool
    {
        return $this->update($user, $type);
    }

    public function import(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasGarageRole(RoleEnum::ADMIN->value);
    }
}

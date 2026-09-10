<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Garage;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function view(User $user, User $targetUser): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->hasGarageRole(RoleEnum::ADMIN->value)) {
            return false;
        }

        return $this->targetSharesCurrentGarage($targetUser);
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function update(User $user, ?User $targetUser = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->hasGarageRole(RoleEnum::ADMIN->value)) {
            return false;
        }

        // Target not specified — this is a create context, admin check is enough
        if ($targetUser === null) {
            return true;
        }

        // Target user must belong to the current garage
        return $this->targetSharesCurrentGarage($targetUser);
    }

    public function delete(User $user, User $targetUser): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->hasGarageRole(RoleEnum::ADMIN->value)) {
            return false;
        }

        return $this->targetSharesCurrentGarage($targetUser);
    }

    /**
     * Verify that the target user belongs to the current garage.
     */
    private function targetSharesCurrentGarage(User $targetUser): bool
    {
        $garageId = Garage::getCurrentId();

        if (! $garageId) {
            return false;
        }

        return $targetUser->garages()
            ->whereKey($garageId)
            ->exists();
    }
}
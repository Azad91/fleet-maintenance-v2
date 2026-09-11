<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Garage;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasGarageRole(RoleEnum::ADMIN->value);
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
        return $user->isSuperAdmin()
            || $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function update(User $user, ?User $targetUser = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->hasGarageRole(RoleEnum::ADMIN->value)) {
            return false;
        }

        if ($targetUser === null) {
            return true;
        }

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

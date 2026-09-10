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

        // Target user yoxdursa, yalnız admin yoxlaması kifayət edir (create halı)
        if ($targetUser === null) {
            return true;
        }

        // ✅ KRİTİK: target user cari qaraja aid olmalıdır
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
     * İstifadəçinin cari qaraja üzv olub-olmadığını yoxlayır.
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

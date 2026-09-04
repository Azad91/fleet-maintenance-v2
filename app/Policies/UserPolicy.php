<?php

namespace App\Policies;

use App\Models\User;
use App\Enums\RoleEnum;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function view(User $user, User $targetUser): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function update(User $user, User $targetUser): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function delete(User $user, User $targetUser): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }
}

<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Complaint;
use App\Enums\RoleEnum;

class ComplaintPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::COMPLAINT->value,
            RoleEnum::DIRECTORATE->value,
        ]);
    }

    public function view(User $user, Complaint $complaint): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::COMPLAINT->value,
            RoleEnum::DIRECTORATE->value,
        ]);
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::COMPLAINT->value,
        ]);
    }

    public function update(User $user, Complaint $complaint): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::COMPLAINT->value,
        ]);
    }

    public function delete(User $user, Complaint $complaint): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function close(User $user, Complaint $complaint): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::COMPLAINT->value,
        ]);
    }

    public function import(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasGarageRole([
            RoleEnum::ADMIN->value,
            RoleEnum::COMPLAINT->value,
        ]);
    }
}

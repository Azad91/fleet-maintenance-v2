<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\ComplaintType;
use App\Models\User;

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
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function update(User $user, ComplaintType $type): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, ComplaintType $type): bool
    {
        return $this->create($user);
    }

    public function import(User $user): bool
    {
        return $this->create($user);
    }
}

<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Employee;
use App\Enums\RoleEnum;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function create(User $user): bool
    {
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function import(User $user): bool
    {
        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }
}

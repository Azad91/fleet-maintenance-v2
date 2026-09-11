<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Employee;
use App\Models\User;
use App\Services\GarageContext;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function view(User $user, ?Employee $employee = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($employee && $employee->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function update(User $user, ?Employee $employee = null): bool
    {
        return $this->view($user, $employee);
    }

    public function delete(User $user, ?Employee $employee = null): bool
    {
        return $this->view($user, $employee);
    }

    public function import(User $user): bool
    {
        return $this->create($user);
    }
}

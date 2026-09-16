<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\ServiceVehicle;
use App\Models\User;
use App\Services\GarageContext;

class ServiceVehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function view(User $user, ?ServiceVehicle $vehicle = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($vehicle && $vehicle->garage_id !== GarageContext::getGarageId()) {
            return false;
        }

        return $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function update(User $user, ?ServiceVehicle $vehicle = null): bool
    {
        return $this->view($user, $vehicle);
    }

    public function delete(User $user, ?ServiceVehicle $vehicle = null): bool
    {
        return $this->view($user, $vehicle);
    }
}

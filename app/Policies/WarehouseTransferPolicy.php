<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Models\WarehouseTransfer;
use App\Services\GarageContext;

class WarehouseTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function view(User $user, WarehouseTransfer $transfer): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->hasGarageRole(RoleEnum::ADMIN->value)) {
            return false;
        }

        $garageId = GarageContext::getGarageId();

        return $transfer->isSource($garageId) || $transfer->isDestination($garageId);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasGarageRole(RoleEnum::ADMIN->value);
    }

    public function dispatch(User $user, WarehouseTransfer $transfer): bool
    {
        // Status check runs for EVERY user, including SuperAdmin.
        if (! $transfer->status->isDraft()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole(RoleEnum::ADMIN->value)
            && $transfer->isSource(GarageContext::getGarageId());
    }

    public function receive(User $user, WarehouseTransfer $transfer): bool
    {
        if (! $transfer->status->isDispatched()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole(RoleEnum::ADMIN->value)
            && $transfer->isDestination(GarageContext::getGarageId());
    }

    public function reject(User $user, WarehouseTransfer $transfer): bool
    {
        return $this->receive($user, $transfer);
    }

    public function resolve(User $user, WarehouseTransfer $transfer): bool
    {
        if (! $transfer->status->isDisputed()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole(RoleEnum::ADMIN->value)
            && $transfer->isSource(GarageContext::getGarageId());
    }

    public function cancel(User $user, WarehouseTransfer $transfer): bool
    {
        if (! $transfer->status->isDraft()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasGarageRole(RoleEnum::ADMIN->value)
            && $transfer->isSource(GarageContext::getGarageId());
    }

    public function delete(User $user, WarehouseTransfer $transfer): bool
    {
        return $this->cancel($user, $transfer);
    }
}

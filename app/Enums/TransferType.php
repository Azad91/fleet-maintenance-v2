<?php

namespace App\Enums;

enum TransferType: string
{
    /**
     * Transfer between two garages within the same company.
     * Requires the full dispatch → receive/reject → resolve flow
     * because two different admins are involved.
     */
    case GarageToGarage = 'garage_to_garage';

    /**
     * Transfer from a garage warehouse to one of its own service
     * vehicles. Completes immediately (no discrepancy workflow)
     * because both sides belong to the same garage admin.
     */
    case ToServiceVehicle = 'to_service_vehicle';

    /**
     * Transfer of broken/unusable parts to the garage's quarantine
     * bucket. Completes immediately.
     */
    case ReturnToQuarantine = 'return_to_quarantine';

    public function label(): string
    {
        return __('enums.transfer_type.'.$this->value);
    }

    public function isGarageToGarage(): bool
    {
        return $this === self::GarageToGarage;
    }

    public function isToServiceVehicle(): bool
    {
        return $this === self::ToServiceVehicle;
    }

    public function isReturnToQuarantine(): bool
    {
        return $this === self::ReturnToQuarantine;
    }

    /**
     * Only garage-to-garage transfers go through the full
     * dispatch/receive/dispute workflow. The other two types are
     * intra-garage and complete immediately.
     */
    public function requiresWorkflow(): bool
    {
        return $this === self::GarageToGarage;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}

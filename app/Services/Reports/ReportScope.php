<?php

namespace App\Services\Reports;

use App\Models\Garage;
use App\Models\User;

/**
 * Describes the visibility scope of a report for the current user.
 *
 *   Container scope  — which garages are visible.
 *     • Super Admin    → all garages on the platform
 *     • Director       → own company's garages only
 *     • Garage users   → current garage only
 *
 *   Data scope       — who inside the container can be seen.
 *     • Admin/Manager  → every record in the container
 *     • Worker         → only their own records
 *
 *   Read-only        — Super Admin + Director see aggregates only.
 */
class ReportScope
{
    /**
     * @param  array<int>  $garageIds
     */
    public function __construct(
        public readonly array $garageIds,
        public readonly ?int $userId,
        public readonly bool $readOnly,
        public readonly bool $aggregateOnly = false,
    ) {}

    public static function for(User $user, string $domain): self
    {
        // Super Admin: full platform, read-only aggregates
        if ($user->isSuperAdmin()) {
            return new self(
                garageIds: Garage::pluck('id')->all(),
                userId: null,
                readOnly: true,
                aggregateOnly: true,
            );
        }

        // Director: own company only, read-only
        if ($user->isDirector()) {
            $company = $user->activeDirectorCompany();

            return new self(
                garageIds: $company ? $company->garages()->pluck('id')->all() : [],
                userId: null,
                readOnly: true,
                aggregateOnly: true,
            );
        }

        // Garage users: current garage
        $garageId = Garage::getCurrentId();
        $garageIds = $garageId ? [(int) $garageId] : [];

        // Worker within the domain → only own records
        $isWorker = $user->hasGarageRole($domain . '_worker');
        $userId   = $isWorker ? $user->id : null;

        return new self(
            garageIds: $garageIds,
            userId: $userId,
            readOnly: false,
            aggregateOnly: false,
        );
    }

    public function hasAccess(): bool
    {
        return ! empty($this->garageIds);
    }
}

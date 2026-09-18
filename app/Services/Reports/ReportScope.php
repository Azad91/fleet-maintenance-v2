<?php

namespace App\Services\Reports;

use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;

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
 *
 *   Brand filter     — optional manufacturer filter. When set, only
 *                      records belonging to buses of that brand are
 *                      counted. Supported domains: complaint,
 *                      daily_km, daily_status.
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
        public readonly ?int $brandId = null,
    ) {}

    public static function for(User $user, string $domain, ?int $brandId = null): self
    {
        // Super Admin: full platform, read-only aggregates
        if ($user->isSuperAdmin()) {
            return new self(
                garageIds: Garage::pluck('id')->all(),
                userId: null,
                readOnly: true,
                aggregateOnly: true,
                brandId: $brandId,
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
                brandId: $brandId,
            );
        }

        // Garage users: current garage
        $garageId = GarageContext::resolveGarageId();
        $garageIds = $garageId ? [(int) $garageId] : [];

        // Worker within the domain → only own records
        $isWorker = $user->hasGarageRole($domain.'_worker');
        $userId = $isWorker ? $user->id : null;

        return new self(
            garageIds: $garageIds,
            userId: $userId,
            readOnly: false,
            aggregateOnly: false,
            brandId: $brandId,
        );
    }

    public function hasAccess(): bool
    {
        return ! empty($this->garageIds);
    }

    /**
     * True when a brand filter is currently active.
     */
    public function hasBrandFilter(): bool
    {
        return $this->brandId !== null;
    }
}

<?php

namespace App\Services\OilChange;

use App\Enums\OilType;
use App\Models\Bus;
use App\Models\BusOilChange;

/**
 * Immutable value object describing the oil-change status of one
 * (bus, oil_type) pair at a given moment.
 */
class OilChangeStatus
{
    public function __construct(
        public readonly Bus $bus,
        public readonly OilType $type,
        public readonly ?BusOilChange $lastChange,
        public readonly int $currentKm,
        public readonly ?int $nextDueKm,
        public readonly ?int $remainingKm,
        public readonly string $status,
        public readonly ?int $nextCatalogKm = null,
    ) {}

    /** True when a change is due or overdue. */
    public function needsAttention(): bool
    {
        return in_array($this->status, ['overdue', 'critical', 'due-soon'], true);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'overdue';
    }

    /** How many km past the due point (0 when not overdue). */
    public function overdueByKm(): int
    {
        return $this->isOverdue() ? abs((int) $this->remainingKm) : 0;
    }

    public function bootstrapColor(): string
    {
        return match ($this->status) {
            'overdue'    => 'danger',
            'critical'   => 'danger',
            'due-soon'   => 'warning',
            'ok'         => 'success',
            'no-history' => 'secondary',
            default      => 'secondary',
        };
    }

    public function statusLabel(): string
    {
        return __('messages.oil_change.status.' . $this->status);
    }
}

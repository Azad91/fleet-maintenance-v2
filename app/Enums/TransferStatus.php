<?php

namespace App\Enums;

enum TransferStatus: string
{
    /**
     * Created by the source garage but not yet dispatched.
     * Warehouse stock has not been touched yet.
     */
    case Draft = 'draft';

    /**
     * Dispatched. Source stock was reduced immediately.
     * Destination has not accepted or rejected yet.
     */
    case Dispatched = 'dispatched';

    /**
     * Accepted by the destination. Quantities match the declared
     * totals and destination stock was increased.
     */
    case Received = 'received';

    /**
     * Destination counted fewer (or different) quantities than the
     * declared totals. The difference is "in limbo" until the source
     * garage chooses a resolution.
     */
    case Disputed = 'disputed';

    /**
     * Destination refused the transfer entirely. All quantities were
     * returned to the source garage and the source stock was restored.
     */
    case Rejected = 'rejected';

    /**
     * Cancelled by the source garage while still in draft state.
     * Stock was never touched.
     */
    case Cancelled = 'cancelled';

    /**
     * A disputed transfer has been resolved — either by re-sending
     * the missing quantities or by writing them off as lost.
     */
    case Resolved = 'resolved';

    public function label(): string
    {
        return __('enums.transfer_status.'.$this->value);
    }

    public function bootstrapColor(): string
    {
        return match ($this) {
            self::Draft      => 'secondary',
            self::Dispatched => 'info',
            self::Received   => 'success',
            self::Disputed   => 'warning',
            self::Rejected   => 'danger',
            self::Cancelled  => 'secondary',
            self::Resolved   => 'success',
        };
    }

    public function isDraft(): bool
    {
        return $this === self::Draft;
    }

    public function isDispatched(): bool
    {
        return $this === self::Dispatched;
    }

    public function isReceived(): bool
    {
        return $this === self::Received;
    }

    public function isDisputed(): bool
    {
        return $this === self::Disputed;
    }

    public function isRejected(): bool
    {
        return $this === self::Rejected;
    }

    public function isCancelled(): bool
    {
        return $this === self::Cancelled;
    }

    public function isResolved(): bool
    {
        return $this === self::Resolved;
    }

    /**
     * True while the transfer still needs action from one of the
     * two garages. Used by the dashboard to highlight pending items.
     */
    public function isPending(): bool
    {
        return in_array($this, [self::Draft, self::Dispatched, self::Disputed], true);
    }

    /**
     * True once no further state changes are possible.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Received, self::Rejected, self::Cancelled, self::Resolved], true);
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}

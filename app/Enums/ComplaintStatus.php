<?php

namespace App\Enums;

/**
 * All valid values for `complaints.status`.
 *
 * NOTE: The application currently stores status as a plain string in
 * the DB and does NOT cast the model attribute to this enum. This is
 * intentional — the cast was deferred to a later refactor step so
 * views (which use `'enums.complaint_status.' . $complaint->status`)
 * could be updated in the same pass.
 *
 * For now, this enum serves as the single source of truth for the
 * value strings and their transition rules. Code in services, scopes,
 * and policies should reference `ComplaintStatus::Case->value` rather
 * than writing 'pending' / 'in_progress' / 'completed' / 'cancelled'
 * as literals.
 */
enum ComplaintStatus: string
{
    case Pending    = 'pending';
    case InProgress = 'in_progress';
    case Completed  = 'completed';
    case Cancelled  = 'cancelled';

    /**
     * Human-readable label, translated via lang/{locale}/enums.php.
     */
    public function label(): string
    {
        return __('enums.complaint_status.' . $this->value);
    }

    /**
     * True when the complaint is still active (pending or in progress).
     */
    public function isOpen(): bool
    {
        return match ($this) {
            self::Pending, self::InProgress => true,
            self::Completed, self::Cancelled => false,
        };
    }

    /**
     * True when the complaint has been fully resolved.
     */
    public function isCompleted(): bool
    {
        return $this === self::Completed;
    }

    /**
     * True when the complaint was abandoned.
     */
    public function isCancelled(): bool
    {
        return $this === self::Cancelled;
    }

    /**
     * Statuses that can be reached from this one.
     *
     * A status is always allowed to transition to itself (a no-op),
     * which keeps the transition service simple.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [
                self::Pending,
                self::InProgress,
                self::Completed,
                self::Cancelled,
            ],
            self::InProgress => [
                self::InProgress,
                self::Pending,
                self::Completed,
                self::Cancelled,
            ],
            // Terminal states — no further transitions permitted.
            self::Completed => [self::Completed],
            self::Cancelled => [self::Cancelled],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /**
     * All status values as plain strings.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $case) => $case->value,
            self::cases()
        );
    }

    /**
     * Statuses that a complaint may be created with.
     *
     * `completed` and `cancelled` are terminal states and can only be
     * reached through a subsequent update or the close flow.
     *
     * @return array<int, self>
     */
    public static function creatableCases(): array
    {
        return [self::Pending, self::InProgress];
    }

    /**
     * @return array<int, string>
     */
    public static function creatableValues(): array
    {
        return array_map(
            static fn (self $case) => $case->value,
            self::creatableCases()
        );
    }
}

<?php

namespace App\Enums;

enum ComplaintStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Human-readable label, translated via lang/{locale}/enums.php.
     */
    public function label(): string
    {
        return __('enums.complaint_status.'.$this->value);
    }

    /**
     * Bootstrap 5 color name — used for `badge bg-{color}`.
     */
    public function bootstrapColor(): string
    {
        return match ($this) {
            self::Pending, self::Cancelled => 'secondary',
            self::InProgress => 'warning',
            self::Completed => 'success',
        };
    }

    /**
     * CSS modifier for the project's custom `.badge-status.{modifier}`
     * component — underscores become dashes.
     */
    public function cssModifier(): string
    {
        return str_replace('_', '-', $this->value);
    }

    public function isOpen(): bool
    {
        return match ($this) {
            self::Pending, self::InProgress => true,
            self::Completed, self::Cancelled => false,
        };
    }

    public function isCompleted(): bool
    {
        return $this === self::Completed;
    }

    public function isCancelled(): bool
    {
        return $this === self::Cancelled;
    }

    /**
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
            self::Completed => [self::Completed],
            self::Cancelled => [self::Cancelled],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }

    /**
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
        return array_map(static fn (self $case) => $case->value, self::creatableCases());
    }
}

<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Immutable value object describing the date range of a report.
 *
 * Supported presets: daily, weekly, monthly, custom.
 * Custom requires `from` and `to` query parameters (Y-m-d).
 */
class ReportPeriod
{
    public const PRESETS = ['daily', 'weekly', 'monthly', 'custom'];

    public function __construct(
        public readonly Carbon $from,
        public readonly Carbon $to,
        public readonly string $preset,
    ) {}

    /**
     * Build a period from request query parameters.
     */
    public static function fromRequest(Request $request): self
    {
        $preset = (string) $request->input('period', 'monthly');

        if (! in_array($preset, self::PRESETS, true)) {
            $preset = 'monthly';
        }

        return match ($preset) {
            'daily'   => new self(now()->startOfDay(), now()->endOfDay(), 'daily'),
            'weekly'  => new self(now()->startOfWeek(), now()->endOfWeek(), 'weekly'),
            'custom'  => self::fromCustom($request),
            default   => new self(now()->startOfMonth(), now()->endOfMonth(), 'monthly'),
        };
    }

    /**
     * Build from explicit from/to parameters (falls back to monthly if invalid).
     */
    private static function fromCustom(Request $request): self
    {
        try {
            $from = Carbon::parse($request->input('from'))->startOfDay();
            $to   = Carbon::parse($request->input('to'))->endOfDay();

            // Swap if reversed
            if ($from->greaterThan($to)) {
                [$from, $to] = [$to, $from];
            }

            return new self($from, $to, 'custom');
        } catch (\Throwable $e) {
            return new self(now()->startOfMonth(), now()->endOfMonth(), 'monthly');
        }
    }

    /**
     * Human-readable label, e.g. "01.09.2026 — 30.09.2026".
     */
    public function label(): string
    {
        return $this->from->format('d.m.Y') . ' — ' . $this->to->format('d.m.Y');
    }

    /**
     * Number of days in the period (inclusive).
     */
    public function days(): int
    {
        return $this->from->diffInDays($this->to) + 1;
    }

    /**
     * Check if a given date falls inside this period.
     */
    public function contains(Carbon|string $date): bool
    {
        $d = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $d->betweenIncluded($this->from, $this->to);
    }

    /**
     * Serialize back into query string parameters for the URL.
     */
    public function toQueryString(): array
    {
        $query = ['period' => $this->preset];

        if ($this->preset === 'custom') {
            $query['from'] = $this->from->format('Y-m-d');
            $query['to']   = $this->to->format('Y-m-d');
        }

        return $query;
    }
}

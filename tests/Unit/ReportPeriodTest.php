<?php

namespace Tests\Unit;

use App\Services\Reports\ReportPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class ReportPeriodTest extends TestCase
{
    public function test_defaults_to_monthly_when_no_period_given(): void
    {
        $request = Request::create('/reports', 'GET');
        $period  = ReportPeriod::fromRequest($request);

        $this->assertSame('monthly', $period->preset);
        $this->assertSame(now()->startOfMonth()->toDateString(), $period->from->toDateString());
        $this->assertSame(now()->endOfMonth()->toDateString(), $period->to->toDateString());
    }

    public function test_daily_preset(): void
    {
        $request = Request::create('/reports', 'GET', ['period' => 'daily']);
        $period  = ReportPeriod::fromRequest($request);

        $this->assertSame('daily', $period->preset);
        $this->assertSame(now()->startOfDay()->toDateString(), $period->from->toDateString());
        $this->assertSame(now()->endOfDay()->toDateString(), $period->to->toDateString());
    }

    public function test_weekly_preset(): void
    {
        $request = Request::create('/reports', 'GET', ['period' => 'weekly']);
        $period  = ReportPeriod::fromRequest($request);

        $this->assertSame('weekly', $period->preset);
        $this->assertTrue($period->from->isStartOfWeek());
        $this->assertTrue($period->to->isEndOfWeek());
    }

    public function test_custom_preset_with_valid_dates(): void
    {
        $request = Request::create('/reports', 'GET', [
            'period' => 'custom',
            'from'   => '2026-01-15',
            'to'     => '2026-02-20',
        ]);

        $period = ReportPeriod::fromRequest($request);

        $this->assertSame('custom', $period->preset);
        $this->assertSame('2026-01-15', $period->from->toDateString());
        $this->assertSame('2026-02-20', $period->to->toDateString());
    }

    public function test_custom_preset_swaps_reversed_dates(): void
    {
        $request = Request::create('/reports', 'GET', [
            'period' => 'custom',
            'from'   => '2026-03-20',
            'to'     => '2026-03-10',
        ]);

        $period = ReportPeriod::fromRequest($request);

        $this->assertSame('2026-03-10', $period->from->toDateString());
        $this->assertSame('2026-03-20', $period->to->toDateString());
    }

    public function test_custom_preset_with_invalid_dates_falls_back_to_monthly(): void
    {
        $request = Request::create('/reports', 'GET', [
            'period' => 'custom',
            'from'   => 'not-a-date',
            'to'     => 'garbage',
        ]);

        $period = ReportPeriod::fromRequest($request);

        $this->assertSame('monthly', $period->preset);
    }

    public function test_unknown_preset_falls_back_to_monthly(): void
    {
        $request = Request::create('/reports', 'GET', ['period' => 'yearly']);
        $period  = ReportPeriod::fromRequest($request);

        $this->assertSame('monthly', $period->preset);
    }

    public function test_label_format(): void
    {
        $period = new ReportPeriod(
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-01-31'),
            'custom'
        );

        $this->assertSame('01.01.2026 — 31.01.2026', $period->label());
    }

    public function test_days_count(): void
    {
        $period = new ReportPeriod(
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-01-31'),
            'custom'
        );

        $this->assertSame(31, $period->days());
    }

    public function test_contains(): void
    {
        $period = new ReportPeriod(
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-01-31'),
            'custom'
        );

        $this->assertTrue($period->contains('2026-01-15'));
        $this->assertTrue($period->contains('2026-01-01'));
        $this->assertTrue($period->contains('2026-01-31'));
        $this->assertFalse($period->contains('2025-12-31'));
        $this->assertFalse($period->contains('2026-02-01'));
    }

    public function test_to_query_string(): void
    {
        $monthly = new ReportPeriod(now()->startOfMonth(), now()->endOfMonth(), 'monthly');
        $this->assertSame(['period' => 'monthly'], $monthly->toQueryString());

        $custom = new ReportPeriod(
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-01-31'),
            'custom'
        );

        $this->assertSame([
            'period' => 'custom',
            'from'   => '2026-01-01',
            'to'     => '2026-01-31',
        ], $custom->toQueryString());
    }
}

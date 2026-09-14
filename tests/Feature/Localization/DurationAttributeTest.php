<?php

namespace Tests\Feature\Localization;

use App\Models\Bus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\Garage;
use App\Services\GarageContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DurationAttributeTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected Bus $bus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage = Garage::factory()->create(['company_id' => $this->company->id]);

        GarageContext::set($this->garage->id, $this->company->id);

        $this->bus = Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
        ]);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    private function makeComplaint(int $days): Complaint
    {
        $start = Carbon::parse('2026-01-01');
        $end = $start->copy()->addDays($days);

        return Complaint::create([
            'bus_id' => $this->bus->id,
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'yer' => 'garage',
            'status' => 'completed',
            'complaint_type' => 'breakdown',
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ]);
    }

    // ==================================================================
    // 1. DURATION IN ENGLISH
    // ==================================================================

    public function test_duration_renders_in_english(): void
    {
        app()->setLocale('en');

        $complaint = $this->makeComplaint(10);

        $this->assertSame('10 days', $complaint->duration);
    }

    // ==================================================================
    // 2. DURATION IN AZERBAIJANI
    // ==================================================================

    public function test_duration_renders_in_azerbaijani(): void
    {
        app()->setLocale('az');

        $complaint = $this->makeComplaint(10);

        $this->assertSame('10 gün', $complaint->duration);
    }

    // ==================================================================
    // 3. DURATION IN RUSSIAN
    // ==================================================================

    public function test_duration_renders_in_russian(): void
    {
        app()->setLocale('ru');

        $complaint = $this->makeComplaint(10);

        $this->assertSame('10 дней', $complaint->duration);
    }

    // ==================================================================
    // 4. DURATION IN TURKISH
    // ==================================================================

    public function test_duration_renders_in_turkish(): void
    {
        app()->setLocale('tr');

        $complaint = $this->makeComplaint(10);

        $this->assertSame('10 gün', $complaint->duration);
    }

    // ==================================================================
    // 5. NO HARDCODED STRING LEAKAGE
    // ==================================================================

    public function test_duration_does_not_contain_hardcoded_azerbaijani(): void
    {
        // Even with an English locale, the raw "gün" must not appear
        // — the translation must be used everywhere.
        app()->setLocale('en');

        $complaint = $this->makeComplaint(5);

        $this->assertStringNotContainsString('gün', $complaint->duration);
        $this->assertStringNotContainsString('дней', $complaint->duration);
    }

    // ==================================================================
    // 6. MISSING DATES FALLBACK
    // ==================================================================

    public function test_duration_returns_dash_when_dates_are_missing(): void
    {
        $complaint = Complaint::create([
            'bus_id' => $this->bus->id,
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'yer' => 'garage',
            'status' => 'pending',
            'complaint_type' => 'breakdown',
            // no start_date, no end_date
        ]);

        $this->assertSame('-', $complaint->duration);
    }
}

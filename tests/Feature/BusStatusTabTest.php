<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\BusDailyStatus;
use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusStatusTabTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected User $admin;

    protected Bus $bus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage = Garage::factory()->create(['company_id' => $this->company->id]);

        $this->admin = User::factory()->create(['role' => 'user']);
        $this->admin->garages()->attach($this->garage->id, [
            'role' => 'admin',
            'is_active' => true,
        ]);

        GarageContext::set($this->garage->id, $this->company->id);

        $this->bus = Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'dqn' => 'STATUS-001',
        ]);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    protected function garageSession(): array
    {
        return [
            'current_garage_id' => $this->garage->id,
            'current_company_id' => $this->company->id,
        ];
    }

    protected function addStatus(string $date, string $status, ?string $notes = null): BusDailyStatus
    {
        return BusDailyStatus::withoutGlobalScopes()->create([
            'bus_id' => $this->bus->id,
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'date' => $date,
            'status' => $status,
            'notes' => $notes,
        ]);
    }

    // ==================================================================
    // 1. Current status KPI
    // ==================================================================

    public function test_show_page_displays_current_status_kpi(): void
    {
        $this->addStatus('2026-09-20', 'READY FOR ROUTE');
        $this->addStatus('2026-09-24', 'IN MAINTENANCE');

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('buses.show', $this->bus));

        $response->assertOk();
        $response->assertSee(__('messages.buses.current_status'), false);
        $response->assertSee('IN MAINTENANCE');
    }

    public function test_kpi_shows_dash_when_no_statuses(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('buses.show', $this->bus));

        $response->assertOk();
        $response->assertSee(__('messages.buses.current_status'), false);
    }

    // ==================================================================
    // 2. Month filter
    // ==================================================================

    public function test_default_month_filter_shows_current_month_only(): void
    {
        $this->addStatus(now()->toDateString(), 'READY FOR ROUTE');
        $this->addStatus(now()->subMonths(2)->toDateString(), 'OLD STATUS');

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('buses.show', $this->bus));

        $response->assertOk();
        $response->assertSee('READY FOR ROUTE');
        $response->assertDontSee('OLD STATUS');
    }

    public function test_status_month_query_filters_records(): void
    {
        $this->addStatus('2026-08-15', 'AUGUST STATUS');
        $this->addStatus('2026-09-15', 'SEPTEMBER STATUS');

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('buses.show', $this->bus).'?status_month=2026-08');

        $response->assertOk();

        // The table must contain ONLY the August record. The KPI card
        // intentionally shows the latest status of ANY month, so we
        // assert against the paginator data rather than raw HTML.
        $response->assertViewHas('statusRecords', function ($paginator) {
            return $paginator->total() === 1
                && $paginator->items()[0]->status === 'AUGUST STATUS';
        });
    }

    public function test_invalid_status_month_falls_back_to_current_month(): void
    {
        $this->addStatus(now()->toDateString(), 'READY FOR ROUTE');

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('buses.show', $this->bus).'?status_month=not-a-month');

        $response->assertOk();
        $response->assertSee('READY FOR ROUTE');
    }

    // ==================================================================
    // 3. Monthly summary
    // ==================================================================

    public function test_monthly_summary_aggregates_statuses(): void
    {
        $this->addStatus('2026-09-01', 'READY FOR ROUTE');
        $this->addStatus('2026-09-02', 'READY FOR ROUTE');
        $this->addStatus('2026-09-03', 'READY FOR ROUTE');
        $this->addStatus('2026-09-04', 'IN MAINTENANCE');
        $this->addStatus('2026-09-05', 'IN MAINTENANCE');

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('buses.show', $this->bus).'?status_month=2026-09');

        $response->assertOk();
        $response->assertSee(__('messages.buses.status_summary'), false);

        $response->assertViewHas('monthlySummary', function ($summary) {
            $byStatus = $summary->pluck('days', 'status')->toArray();

            return $byStatus['READY FOR ROUTE'] === 3
                && $byStatus['IN MAINTENANCE'] === 2;
        });
    }

    public function test_summary_shows_only_selected_month(): void
    {
        $this->addStatus('2026-08-15', 'AUGUST STATUS');
        $this->addStatus('2026-09-15', 'SEPTEMBER STATUS');

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('buses.show', $this->bus).'?status_month=2026-09');

        $response->assertOk();

        $response->assertViewHas('monthlySummary', function ($summary) {
            return $summary->count() === 1
                && $summary->first()->status === 'SEPTEMBER STATUS';
        });
    }

    // ==================================================================
    // 4. Regression — pagination and hash preserved
    // ==================================================================

    public function test_status_pagination_preserves_month_filter(): void
    {
        $this->addStatus('2026-08-15', 'AUGUST');
        $this->addStatus('2026-09-15', 'SEPTEMBER');

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('buses.show', $this->bus).'?status_month=2026-08');

        $response->assertOk();

        // The month filter must survive pagination — its value must be
        // present in the pagination links rendered on the page.
        $response->assertSee('status_month=2026-08', false);
    }
}

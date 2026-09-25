<?php

namespace Tests\Feature\Reports;

use App\Models\Bus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\ComplaintItem;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetHealthReportTest extends TestCase
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
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    private function garageSession(): array
    {
        return [
            'current_garage_id' => $this->garage->id,
            'current_company_id' => $this->company->id,
        ];
    }

    // ==================================================================
    // All six report pages load without error
    // ==================================================================

    public function test_cost_per_km_page_loads(): void
    {
        $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.fleet-health.cost-per-km'))
            ->assertOk();
    }

    public function test_downtime_page_loads(): void
    {
        $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.fleet-health.downtime'))
            ->assertOk();
    }

    public function test_recurring_issues_page_loads(): void
    {
        $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.fleet-health.recurring-issues'))
            ->assertOk();
    }

    public function test_recurring_complaints_page_loads(): void
    {
        $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.fleet-health.recurring-complaints'))
            ->assertOk();
    }

    public function test_accidents_page_loads(): void
    {
        $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.fleet-health.accidents'))
            ->assertOk();
    }

    public function test_utilization_page_loads(): void
    {
        $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.fleet-health.utilization'))
            ->assertOk();
    }

    // ==================================================================
    // Accidents report only shows accident type
    // ==================================================================

    public function test_accidents_report_only_shows_accident_complaints(): void
    {
        $accident = Complaint::create([
            'bus_id' => $this->bus->id,
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'yer' => 'garage',
            'status' => 'pending',
            'complaint_type' => 'accident',
        ]);

        $breakdown = Complaint::create([
            'bus_id' => $this->bus->id,
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'yer' => 'garage',
            'status' => 'pending',
            'complaint_type' => 'breakdown',
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.fleet-health.accidents'));

        $response->assertOk();
        $response->assertViewHas('rows', fn ($rows) => $rows->count() === 1
            && $rows->first()->id === $accident->id);
    }

    // ==================================================================
    // Recurring complaints on same bus
    // ==================================================================

    public function test_recurring_complaints_requires_two_occurrences(): void
    {
        // Two complaints with the SAME description on the same bus
        foreach ([1, 2] as $i) {
            $c = Complaint::create([
                'bus_id' => $this->bus->id,
                'garage_id' => $this->garage->id,
                'company_id' => $this->company->id,
                'yer' => 'garage',
                'status' => 'pending',
                'complaint_type' => 'breakdown',
                'created_at' => now()->subDays($i),
            ]);

            ComplaintItem::create([
                'complaint_id' => $c->id,
                'description' => 'Engine noise',
                'garage_id' => $this->garage->id,
                'company_id' => $this->company->id,
            ]);
        }

        // One complaint with a DIFFERENT description
        $c3 = Complaint::create([
            'bus_id' => $this->bus->id,
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'yer' => 'garage',
            'status' => 'pending',
            'complaint_type' => 'breakdown',
        ]);

        ComplaintItem::create([
            'complaint_id' => $c3->id,
            'description' => 'Brake squeak',
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.fleet-health.recurring-complaints'));

        $response->assertOk();
        $response->assertViewHas('rows', function ($rows) {
            return $rows->count() === 1
                && $rows->first()->description === 'Engine noise'
                && (int) $rows->first()->occurrences === 2;
        });
    }

    // ==================================================================
    // Utilization
    // ==================================================================

    public function test_utilization_computes_percent(): void
    {
        // Add KM records for a few days in the current month
        foreach ([0, 1, 2] as $offset) {
            \App\Models\DailyKmRecord::withoutGlobalScopes()->create([
                'bus_id' => $this->bus->id,
                'garage_id' => $this->garage->id,
                'company_id' => $this->company->id,
                'date' => now()->subDays($offset)->toDateString(),
                'km' => 100000 + ($offset * 100),
            ]);
        }

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.fleet-health.utilization'));

        $response->assertOk();
        $response->assertViewHas('rows', function ($rows) {
            $r = $rows->first();
            return $r && $r->days_in_service === 3 && $r->utilization_percent > 0;
        });
    }

    // ==================================================================
    // Access control
    // ==================================================================

    public function test_guest_cannot_access_fleet_health_reports(): void
    {
        $this->get(route('reports.fleet-health.cost-per-km'))
            ->assertRedirect(route('login'));
    }

    public function test_warehouse_worker_cannot_access_fleet_health_reports(): void
    {
        $worker = User::factory()->create(['role' => 'user']);
        $worker->garages()->attach($this->garage->id, [
            'role' => 'warehouse_worker',
            'is_active' => true,
        ]);

        $this->actingAs($worker)
            ->withSession($this->garageSession())
            ->get(route('reports.fleet-health.cost-per-km'))
            ->assertForbidden();
    }

    public function test_complaint_manager_can_access_downtime_report(): void
    {
        $manager = User::factory()->create(['role' => 'user']);
        $manager->garages()->attach($this->garage->id, [
            'role' => 'complaint_manager',
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->withSession($this->garageSession())
            ->get(route('reports.fleet-health.downtime'))
            ->assertOk();
    }

    // ==================================================================
    // Export
    // ==================================================================

    public function test_cost_per_km_export_returns_xlsx(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.fleet-health.cost-per-km', ['export' => 'xlsx']));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }
}

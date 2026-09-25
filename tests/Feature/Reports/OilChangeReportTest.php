<?php

namespace Tests\Feature\Reports;

use App\Enums\OilType;
use App\Models\Bus;
use App\Models\BusOilChange;
use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OilChangeReportTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected User $admin;

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
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    private function session(): array
    {
        return [
            'current_garage_id' => $this->garage->id,
            'current_company_id' => $this->company->id,
        ];
    }

    // ==================================================================
    // 1. HISTORY
    // ==================================================================

    public function test_history_page_loads(): void
    {
        $bus = Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        BusOilChange::withoutGlobalScopes()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'bus_id' => $bus->id,
            'oil_type' => OilType::Motor->value,
            'oil_brand' => null,
            'actual_km' => 36000,
            'interval_km' => 36000,
            'scheduled_km' => 36000,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession($this->session())
            ->get(route('reports.oil-change.history'));

        $response->assertOk();
        $response->assertSee($bus->dqn);
    }

    public function test_history_filters_by_oil_type(): void
    {
        $bus = Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        BusOilChange::withoutGlobalScopes()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'bus_id' => $bus->id,
            'oil_type' => OilType::Motor->value,
            'actual_km' => 36000,
            'interval_km' => 36000,
        ]);

        BusOilChange::withoutGlobalScopes()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'bus_id' => $bus->id,
            'oil_type' => OilType::Gearbox->value,
            'oil_brand' => 'SHELL',
            'actual_km' => 180000,
            'interval_km' => 180000,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession($this->session())
            ->get(route('reports.oil-change.history', ['oil_type' => 'motor']));

        $response->assertOk();
        $response->assertViewHas('rows', fn ($rows) => $rows->count() === 1
            && $rows->first()->oil_type === OilType::Motor);
    }

    // ==================================================================
    // 2. UPCOMING
    // ==================================================================

    public function test_upcoming_page_loads(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->session())
            ->get(route('reports.oil-change.upcoming'));

        $response->assertOk();
    }

    // ==================================================================
    // 3. COUNTS
    // ==================================================================

    public function test_counts_page_loads(): void
    {
        $bus = Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        BusOilChange::withoutGlobalScopes()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'bus_id' => $bus->id,
            'oil_type' => OilType::Motor->value,
            'actual_km' => 36000,
            'interval_km' => 36000,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession($this->session())
            ->get(route('reports.oil-change.counts'));

        $response->assertOk();
        $response->assertSee('1'); // total count
    }

    // ==================================================================
    // 4. ADHERENCE
    // ==================================================================

    public function test_adherence_page_loads(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->session())
            ->get(route('reports.oil-change.adherence'));

        $response->assertOk();
        $response->assertSee(__('messages.reports.content.on_time_rate'), false);
    }

    public function test_adherence_classifies_early_on_time_late(): void
    {
        $bus = Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        // Early
        BusOilChange::withoutGlobalScopes()->create([
            'garage_id' => $this->garage->id, 'company_id' => $this->company->id,
            'bus_id' => $bus->id, 'oil_type' => OilType::Motor->value,
            'actual_km' => 35000, 'interval_km' => 36000, 'scheduled_km' => 36000,
        ]);
        // On time
        BusOilChange::withoutGlobalScopes()->create([
            'garage_id' => $this->garage->id, 'company_id' => $this->company->id,
            'bus_id' => $bus->id, 'oil_type' => OilType::Motor->value,
            'actual_km' => 72000, 'interval_km' => 36000, 'scheduled_km' => 72000,
        ]);
        // Late
        BusOilChange::withoutGlobalScopes()->create([
            'garage_id' => $this->garage->id, 'company_id' => $this->company->id,
            'bus_id' => $bus->id, 'oil_type' => OilType::Motor->value,
            'actual_km' => 110000, 'interval_km' => 36000, 'scheduled_km' => 108000,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession($this->session())
            ->get(route('reports.oil-change.adherence'));

        $response->assertOk();
        $response->assertViewHas('data', function ($data) {
            return $data['early'] === 1
                && $data['on_time'] === 1
                && $data['late'] === 1
                && $data['total'] === 3;
        });
    }

    // ==================================================================
    // 5. CATALOG USAGE
    // ==================================================================

    public function test_catalog_usage_page_loads(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->session())
            ->get(route('reports.oil-change.catalog-usage'));

        $response->assertOk();
    }

    // ==================================================================
    // 6. ACCESS CONTROL
    // ==================================================================

    public function test_guest_cannot_access_oil_change_reports(): void
    {
        $this->get(route('reports.oil-change.history'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_user_cannot_access_oil_change_reports(): void
    {
        $worker = User::factory()->create(['role' => 'user']);
        $worker->garages()->attach($this->garage->id, [
            'role' => 'complaint_worker',
            'is_active' => true,
        ]);

        $this->actingAs($worker)
            ->withSession($this->session())
            ->get(route('reports.oil-change.history'))
            ->assertForbidden();
    }

    // ==================================================================
    // 7. EXPORT
    // ==================================================================

    public function test_history_export_returns_xlsx(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->session())
            ->get(route('reports.oil-change.history', ['export' => 'xlsx']));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }
}

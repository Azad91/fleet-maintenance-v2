<?php

namespace Tests\Feature\Reports;

use App\Models\Bus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\Garage;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartsUsageReportTest extends TestCase
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

    private function makeComplaintWithDetail(string $code, int $qty, float $price): Complaint
    {
        $complaint = Complaint::create([
            'bus_id' => $this->bus->id,
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'yer' => 'garage',
            'status' => 'completed',
            'complaint_type' => 'breakdown',
        ]);

        $complaint->details()->create([
            'code' => $code,
            'name' => "Part {$code}",
            'used_quantity' => $qty,
            'stock_quantity' => 100,
            'price_at_use' => $price,
            'source_type' => 'warehouse',
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        return $complaint;
    }

    // ==================================================================
    // 1. PER BUS
    // ==================================================================

    public function test_per_bus_page_loads(): void
    {
        $this->makeComplaintWithDetail('P-001', 3, 25.00);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.parts-usage.per-bus'));

        $response->assertOk();
        $response->assertSee($this->bus->dqn);
    }

    // ==================================================================
    // 2. TOP CONSUMED
    // ==================================================================

    public function test_top_consumed_page_loads(): void
    {
        $this->makeComplaintWithDetail('P-002', 5, 10.00);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.parts-usage.top-consumed'));

        $response->assertOk();
        $response->assertSee('P-002');
    }

    // ==================================================================
    // 3. BUS COST
    // ==================================================================

    public function test_bus_cost_page_loads(): void
    {
        $this->makeComplaintWithDetail('P-003', 2, 50.00); // 100 ₼

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.parts-usage.bus-cost'));

        $response->assertOk();
        $response->assertViewHas('rows', function ($rows) {
            return $rows->count() === 1
                && (float) $rows->first()->total_cost === 100.0;
        });
    }

    // ==================================================================
    // 4. PER COMPLAINT
    // ==================================================================

    public function test_per_complaint_page_loads(): void
    {
        $this->makeComplaintWithDetail('P-004', 3, 10.00);
        $this->makeComplaintWithDetail('P-005', 2, 10.00);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.parts-usage.per-complaint'));

        $response->assertOk();
        $response->assertViewHas('data', function ($data) {
            return $data['total_complaints'] === 2
                && $data['total_parts'] === 2
                && $data['avg_parts_per_complaint'] === 1.0;
        });
    }

    // ==================================================================
    // 5. DEAD STOCK
    // ==================================================================

    public function test_dead_stock_shows_never_used_items(): void
    {
        // İstifadə olunmuş item
        $this->makeComplaintWithDetail('USED-001', 1, 10.00);

        // Heç vaxt istifadə olunmamış item
        Warehouse::withoutGlobalScopes()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'code' => 'DEAD-001',
            'name' => 'Dead Item',
            'quantity' => 10,
            'price' => 100.00,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.parts-usage.dead-stock'));

        $response->assertOk();
        $response->assertSee('DEAD-001');
        $response->assertDontSee('USED-001');
    }

    public function test_dead_stock_page_loads_when_no_dead_stock(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.parts-usage.dead-stock'));

        $response->assertOk();
        $response->assertSee(__('messages.reports.content.no_dead_stock'), false);
    }

    // ==================================================================
    // 6. ACCESS CONTROL
    // ==================================================================

    public function test_guest_cannot_access_parts_usage_reports(): void
    {
        $this->get(route('reports.parts-usage.per-bus'))
            ->assertRedirect(route('login'));
    }

    public function test_complaint_worker_cannot_access_bus_cost_report(): void
    {
        $worker = User::factory()->create(['role' => 'user']);
        $worker->garages()->attach($this->garage->id, [
            'role' => 'complaint_worker',
            'is_active' => true,
        ]);

        $this->actingAs($worker)
            ->withSession($this->garageSession())
            ->get(route('reports.parts-usage.bus-cost'))
            ->assertForbidden();
    }

    public function test_warehouse_manager_can_access_per_bus_report(): void
    {
        $manager = User::factory()->create(['role' => 'user']);
        $manager->garages()->attach($this->garage->id, [
            'role' => 'warehouse_manager',
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->withSession($this->garageSession())
            ->get(route('reports.parts-usage.per-bus'))
            ->assertOk();
    }
}

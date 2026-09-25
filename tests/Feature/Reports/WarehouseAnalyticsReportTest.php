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

class WarehouseAnalyticsReportTest extends TestCase
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

    private function makeWarehouse(string $code, int $qty, float $price, ?string $category = null, ?string $supplier = null): Warehouse
    {
        return Warehouse::withoutGlobalScopes()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'code' => $code,
            'name' => "Part {$code}",
            'quantity' => $qty,
            'minimum_quantity' => 10,
            'price' => $price,
            'category' => $category,
            'supplier' => $supplier,
            'is_quarantine' => false,
        ]);
    }

    private function consumePart(string $code, int $qty, float $price, ?\Carbon\Carbon $when = null): void
    {
        $complaint = Complaint::create([
            'bus_id' => $this->bus->id,
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'yer' => 'garage',
            'status' => 'completed',
            'complaint_type' => 'breakdown',
            'created_at' => $when ?? now(),
        ]);

        $complaint->details()->create([
            'code' => $code,
            'name' => "Part {$code}",
            'used_quantity' => $qty,
            'price_at_use' => $price,
            'stock_quantity' => 100,
            'source_type' => 'warehouse',
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
        ]);
    }

    // ==================================================================
    // 1. All 5 pages load
    // ==================================================================

    public function test_slow_moving_page_loads(): void
    {
        $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.warehouse-analytics.slow-moving'))
            ->assertOk();
    }

    public function test_valuation_page_loads(): void
    {
        $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.warehouse-analytics.valuation'))
            ->assertOk();
    }

    public function test_reorder_page_loads(): void
    {
        $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.warehouse-analytics.reorder'))
            ->assertOk();
    }

    public function test_supplier_page_loads(): void
    {
        $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.warehouse-analytics.supplier'))
            ->assertOk();
    }

    public function test_part_history_page_loads(): void
    {
        $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.warehouse-analytics.part-history'))
            ->assertOk();
    }

    // ==================================================================
    // 2. Valuation computes totals correctly
    // ==================================================================

    public function test_valuation_sums_correctly(): void
    {
        $this->makeWarehouse('V-001', 10, 5.00, 'Filters');
        $this->makeWarehouse('V-002', 4, 25.00, 'Filters');
        $this->makeWarehouse('V-003', 2, 100.00, 'Tools');

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.warehouse-analytics.valuation'));

        $response->assertOk();
        $response->assertViewHas('data', function ($data) {
            // Filters: 10*5 + 4*25 = 50 + 100 = 150
            // Tools:   2*100 = 200
            // Total:   350
            return $data['total_value'] === 350.0
                && $data['total_items'] === 3
                && $data['total_quantity'] === 16;
        });
    }

    // ==================================================================
    // 3. Reorder only shows items below minimum
    // ==================================================================

    public function test_reorder_shows_only_low_stock_items(): void
    {
        $this->makeWarehouse('LOW-001', 5, 10.00);   // below min (10) — should show
        $this->makeWarehouse('OK-001', 50, 10.00);   // above min — hidden

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.warehouse-analytics.reorder'));

        $response->assertOk();
        $response->assertViewHas('rows', function ($rows) {
            return $rows->count() === 1
                && $rows->first()->code === 'LOW-001'
                && $rows->first()->suggested_order > 0;
        });
    }

    // ==================================================================
    // 4. Part history filters by code
    // ==================================================================

    public function test_part_history_filters_by_code(): void
    {
        $this->consumePart('PH-001', 3, 20.00);
        $this->consumePart('PH-002', 5, 30.00);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.warehouse-analytics.part-history', ['code' => 'PH-001']));

        $response->assertOk();
        $response->assertViewHas('rows', function ($rows) {
            return $rows->count() === 1 && $rows->first()->code === 'PH-001';
        });
    }

    // ==================================================================
    // 5. Supplier performance aggregates
    // ==================================================================

    public function test_supplier_aggregates_usage(): void
    {
        $this->makeWarehouse('S-001', 100, 10.00, null, 'Acme');
        $this->makeWarehouse('S-002', 100, 10.00, null, 'Acme');
        $this->makeWarehouse('S-003', 100, 10.00, null, 'Bosch');

        $this->consumePart('S-001', 5, 10.00);
        $this->consumePart('S-002', 3, 10.00);
        $this->consumePart('S-003', 2, 10.00);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.warehouse-analytics.supplier'));

        $response->assertOk();
        $response->assertViewHas('rows', function ($rows) {
            $bySupplier = $rows->keyBy('supplier');
            return isset($bySupplier['Acme'])
                && (int) $bySupplier['Acme']->total_used === 8
                && (int) $bySupplier['Acme']->distinct_parts === 2;
        });
    }

    // ==================================================================
    // 6. Access control
    // ==================================================================

    public function test_guest_cannot_access(): void
    {
        $this->get(route('reports.warehouse-analytics.slow-moving'))
            ->assertRedirect(route('login'));
    }

    public function test_warehouse_manager_can_access(): void
    {
        $manager = User::factory()->create(['role' => 'user']);
        $manager->garages()->attach($this->garage->id, [
            'role' => 'warehouse_manager',
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->withSession($this->garageSession())
            ->get(route('reports.warehouse-analytics.valuation'))
            ->assertOk();
    }

    public function test_warehouse_worker_cannot_access(): void
    {
        $worker = User::factory()->create(['role' => 'user']);
        $worker->garages()->attach($this->garage->id, [
            'role' => 'warehouse_worker',
            'is_active' => true,
        ]);

        $this->actingAs($worker)
            ->withSession($this->garageSession())
            ->get(route('reports.warehouse-analytics.valuation'))
            ->assertForbidden();
    }
}
<?php

namespace Tests\Feature\Performance;

use App\Models\Bus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\ComplaintItem;
use App\Models\Garage;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * N+1 query guards for the highest-traffic pages.
 *
 * These tests assert that critical list pages issue a bounded number
 * of SQL queries regardless of how many records exist. A failure
 * signals a missing eager-load — the fix is almost always adding
 * `with()` to the query in the controller or service.
 *
 * Query limits are intentionally generous (10-20 headroom above the
 * ideal) so that minor framework overhead does not cause false
 * positives.
 */
class NPlusOneQueryTest extends TestCase
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

    protected function garageSession(): array
    {
        return [
            'current_garage_id' => $this->garage->id,
            'current_company_id' => $this->company->id,
        ];
    }

    /**
     * Count how many SQL queries a callback executes.
     */
    protected function countQueries(callable $callback): int
    {
        $count = 0;

        DB::listen(function () use (&$count) {
            $count++;
        });

        $callback();

        return $count;
    }

    // ==================================================================
    // 1. Bus index — must eager-load brand + latestKmRecord
    // ==================================================================

    public function test_bus_index_query_count_is_bounded(): void
    {
        // Create 25 buses with brands
        $brand = \App\Models\BusBrand::withoutGlobalScopes()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'name' => 'Test Brand',
            'code' => 'TEST',
        ]);

        Bus::factory()->count(25)->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'brand_id' => $brand->id,
        ]);

        $queryCount = $this->countQueries(function () {
            $this->actingAs($this->admin)
                ->withSession($this->garageSession())
                ->get(route('buses.index'))
                ->assertOk();
        });

        // Expected: auth (1) + user lookup (1) + garage session (1)
        //           + bus pagination (1) + brands (1) + latest km (1)
        //           + counts (1) + sidebar queries (~3)
        // With 25 buses, ideal is ~10 queries. If it's 30+, we have N+1.
        $this->assertLessThan(
            25,
            $queryCount,
            "Bus index executed {$queryCount} queries for 25 buses. "
            .'Expected < 25. Check for missing eager-loads on `brand`, '
            .'`latestKmRecord`, or the daily-km accessor fallback.'
        );
    }

    // ==================================================================
    // 2. Complaint index — must eager-load bus + items + details
    // ==================================================================

    public function test_complaint_index_query_count_is_bounded(): void
    {
        $bus = Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        // 20 complaints, each with 1 item
        for ($i = 0; $i < 20; $i++) {
            $complaint = Complaint::create([
                'bus_id' => $bus->id,
                'garage_id' => $this->garage->id,
                'company_id' => $this->company->id,
                'yer' => 'garage',
                'status' => 'pending',
                'complaint_type' => 'breakdown',
            ]);

            ComplaintItem::create([
                'complaint_id' => $complaint->id,
                'description' => "Test item {$i}",
                'type' => 'breakdown',
                'garage_id' => $this->garage->id,
                'company_id' => $this->company->id,
            ]);
        }

        $queryCount = $this->countQueries(function () {
            $this->actingAs($this->admin)
                ->withSession($this->garageSession())
                ->get(route('complaints.index'))
                ->assertOk();
        });

        // Ideal: ~10 queries. If >30, `items` or `bus` is not eager-loaded.
        $this->assertLessThan(
            25,
            $queryCount,
            "Complaint index executed {$queryCount} queries for 20 complaints. "
            .'Expected < 25. Check eager-loads on `bus`, `items`, `details`.'
        );
    }

    // ==================================================================
    // 3. Warehouse index
    // ==================================================================

    public function test_warehouse_index_query_count_is_bounded(): void
    {
        Warehouse::withoutGlobalScopes()->insert(
            collect(range(1, 25))->map(fn ($i) => [
                'garage_id' => $this->garage->id,
                'company_id' => $this->company->id,
                'code' => "W-{$i}",
                'name' => "Item {$i}",
                'quantity' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all()
        );

        $queryCount = $this->countQueries(function () {
            $this->actingAs($this->admin)
                ->withSession($this->garageSession())
                ->get(route('warehouses.index'))
                ->assertOk();
        });

        $this->assertLessThan(
            20,
            $queryCount,
            "Warehouse index executed {$queryCount} queries for 25 items."
        );
    }

    // ==================================================================
    // 4. Dashboard — highest risk for N+1
    // ==================================================================

    public function test_dashboard_query_count_is_bounded(): void
    {
        // Create 30 buses + KM records + oil changes
        Bus::factory()->count(30)->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $queryCount = $this->countQueries(function () {
            $this->actingAs($this->admin)
                ->withSession($this->garageSession())
                ->get(route('dashboard'))
                ->assertOk();
        });

        // Dashboard aggregates + recent items + oil stats.
        // Ideal: ~20 queries. If >50, oil change computation has N+1.
        $this->assertLessThan(
            50,
            $queryCount,
            "Dashboard executed {$queryCount} queries for 30 buses. "
            .'Expected < 50. Check `latestMotorOilChange`, '
            .'`latestGearboxOilChange`, `latestAxleOilChange` eager-loads.'
        );
    }
}

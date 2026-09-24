<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Company;
use App\Models\DailyKmRecord;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusDailyKmColumnTest extends TestCase
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

    protected function makeBus(string $dqn = 'TEST-001'): Bus
    {
        return Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'dqn' => $dqn,
            'is_active' => true,
        ]);
    }

    protected function addKm(Bus $bus, string $date, int $km): DailyKmRecord
    {
        return DailyKmRecord::withoutGlobalScopes()->create([
            'bus_id' => $bus->id,
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'date' => $date,
            'km' => $km,
        ]);
    }

    // ==================================================================
    // 1. Accessor behaviour
    // ==================================================================

    public function test_daily_km_returns_null_when_no_records(): void
    {
        $bus = $this->makeBus();
        $bus->load('dailyKmRecords');

        $this->assertNull($bus->daily_km);
    }

    public function test_daily_km_returns_null_with_only_one_record(): void
    {
        $bus = $this->makeBus();
        $this->addKm($bus, '2026-09-24', 105000);

        $bus->load('dailyKmRecords');
        $this->assertNull($bus->daily_km);
    }

    public function test_daily_km_returns_difference_with_two_records(): void
    {
        $bus = $this->makeBus();
        $this->addKm($bus, '2026-09-23', 104750);
        $this->addKm($bus, '2026-09-24', 105000);

        $bus->load('dailyKmRecords');
        $this->assertSame(250, $bus->daily_km);
    }

    public function test_daily_km_returns_zero_when_km_unchanged(): void
    {
        $bus = $this->makeBus();
        $this->addKm($bus, '2026-09-23', 104750);
        $this->addKm($bus, '2026-09-24', 104750);

        $bus->load('dailyKmRecords');
        $this->assertSame(0, $bus->daily_km);
    }

    public function test_daily_km_clamps_negative_to_zero(): void
    {
        $bus = $this->makeBus();
        $this->addKm($bus, '2026-09-23', 105000);
        $this->addKm($bus, '2026-09-24', 104750);

        $bus->load('dailyKmRecords');
        $this->assertSame(0, $bus->daily_km);
    }

    public function test_daily_km_uses_the_two_most_recent_records(): void
    {
        $bus = $this->makeBus();
        $this->addKm($bus, '2026-09-20', 100000);
        $this->addKm($bus, '2026-09-22', 103000);
        $this->addKm($bus, '2026-09-23', 104000);
        $this->addKm($bus, '2026-09-24', 104250);

        $bus->load('dailyKmRecords');
        $this->assertSame(250, $bus->daily_km);
    }

    public function test_eager_load_with_limit_returns_two_records_per_bus(): void
    {
        $bus = $this->makeBus();
        for ($i = 0; $i < 5; $i++) {
            $this->addKm($bus, now()->subDays(5 - $i)->toDateString(), 100000 + ($i * 100));
        }

        $bus->load(['dailyKmRecords' => fn ($q) => $q->limit(2)]);

        $this->assertCount(2, $bus->dailyKmRecords);
    }

    // ==================================================================
    // 2. Index page
    // ==================================================================

    public function test_index_page_shows_daily_km_header(): void
    {
        $this->makeBus();

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('buses.index'));

        $response->assertOk();
        $response->assertSee(__('messages.buses.col_daily_km'), false);
    }

    public function test_index_page_shows_daily_km_value(): void
    {
        $bus = $this->makeBus();
        $this->addKm($bus, '2026-09-23', 104750);
        $this->addKm($bus, '2026-09-24', 105000);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('buses.index'));

        $response->assertOk();
        $response->assertSee('250 km');
    }

    public function test_index_page_shows_dash_when_insufficient_records(): void
    {
        $bus = $this->makeBus();
        $this->addKm($bus, '2026-09-24', 105000);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('buses.index'));

        $response->assertOk();
        $response->assertSee('—');
    }

    // ==================================================================
    // 3. Show page
    // ==================================================================

    public function test_show_page_displays_current_km_kpi_card(): void
    {
        $bus = $this->makeBus();
        $this->addKm($bus, '2026-09-23', 104750);
        $this->addKm($bus, '2026-09-24', 105000);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('buses.show', $bus));

        $response->assertOk();
        $response->assertSee(__('messages.buses.current_km'), false);
        $response->assertSee('105.000 km');
    }

    public function test_show_page_computes_daily_km_correctly(): void
    {
        $bus = $this->makeBus();
        $this->addKm($bus, '2026-09-20', 100000);
        $this->addKm($bus, '2026-09-23', 104750);
        $this->addKm($bus, '2026-09-24', 105000);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('buses.show', $bus));

        $response->assertOk();

        $response->assertViewHas('kmRecords', function ($paginator) {
            $items = $paginator->items();

            return count($items) === 3
                && $items[0]->daily_km === 250
                && $items[1]->daily_km === 4750
                && $items[2]->daily_km === null;
        });
    }

    public function test_show_page_renders_daily_km_column_header(): void
    {
        $bus = $this->makeBus();
        $this->addKm($bus, '2026-09-23', 104750);
        $this->addKm($bus, '2026-09-24', 105000);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('buses.show', $bus));

        $response->assertOk();
        $response->assertSee(__('messages.buses.col_daily_km'), false);
    }

    // ==================================================================
    // 4. Pagination — diff must compute across page boundary
    // ==================================================================

    public function test_pagination_computes_diff_across_page_boundary(): void
    {
        $bus = $this->makeBus();

        // 35 records — 30 on page 1, 5 on page 2.
        // Dates ascend from 34 days ago to today; KM values increase
        // by 100 per day, so every daily diff must be exactly 100.
        for ($i = 0; $i < 35; $i++) {
            $date = now()->subDays(34 - $i)->toDateString();
            $this->addKm($bus, $date, 100000 + ($i * 100));
        }

        // Request page 1. The LAST item on this page has no sibling
        // record on the same page, so the controller must fetch its
        // previous record from page 2 to compute the daily diff.
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('buses.show', $bus));

        $response->assertOk();
        $response->assertViewHas('kmRecords', function ($paginator) {
            $items = $paginator->items();
            $last = end($items);

            // All records grow by 100 per day, so the page-boundary
            // item must also have a diff of exactly 100 — proving the
            // controller fetched the previous record from page 2.
            return $last->daily_km === 100;
        });
    }
}

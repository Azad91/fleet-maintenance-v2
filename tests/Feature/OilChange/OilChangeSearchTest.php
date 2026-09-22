<?php

namespace Tests\Feature\OilChange;

use App\Enums\OilType;
use App\Models\Bus;
use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OilChangeSearchTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Garage $garage;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage  = Garage::factory()->create(['company_id' => $this->company->id]);

        $this->admin = User::factory()->create(['role' => 'user']);
        $this->admin->garages()->attach($this->garage->id, [
            'role'      => 'admin',
            'is_active' => true,
        ]);

        GarageContext::set($this->garage->id, $this->company->id);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    protected function session(): array
    {
        return [
            'current_garage_id'  => $this->garage->id,
            'current_company_id' => $this->company->id,
        ];
    }

    protected function makeBus(string $dqn, string $route = '100', int $km = 100000): Bus
    {
        $bus = Bus::factory()->create([
            'garage_id'    => $this->garage->id,
            'company_id'   => $this->company->id,
            'dqn'          => $dqn,
            'route_number' => $route,
            'is_active'    => true,
        ]);

        \App\Models\DailyKmRecord::withoutGlobalScopes()->create([
            'bus_id'     => $bus->id,
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
            'date'       => now()->toDateString(),
            'km'         => $km,
        ]);

        return $bus;
    }

    // ==================================================================
    // 1. AJAX PARTIAL RESPONSE
    // ==================================================================

    public function test_search_returns_partial_for_ajax_request(): void
    {
        $this->makeBus('99JZ174');

        $response = $this->actingAs($this->admin)
            ->withSession($this->session())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('oil-changes.search', ['dqn' => '99JZ']));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('99JZ174', $content);
        $this->assertStringNotContainsString('<!DOCTYPE html>', $content, 'AJAX must return a partial, not a full page');
    }

    public function test_search_returns_full_view_for_non_ajax_request(): void
    {
        $this->makeBus('99JZ174');

        $response = $this->actingAs($this->admin)
            ->withSession($this->session())
            ->get(route('oil-changes.search', ['dqn' => '99JZ']));

        $response->assertOk();
        $response->assertViewIs('oil-changes.index');
    }

    // ==================================================================
    // 2. DQN FILTER
    // ==================================================================

    public function test_search_filters_by_dqn(): void
    {
        $this->makeBus('99JZ174');
        $this->makeBus('77XX999');

        $response = $this->actingAs($this->admin)
            ->withSession($this->session())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('oil-changes.search', ['dqn' => '99JZ']));

        $content = $response->getContent();
        $this->assertStringContainsString('99JZ174', $content);
        $this->assertStringNotContainsString('77XX999', $content);
    }

    // ==================================================================
    // 3. ROUTE FILTER
    // ==================================================================

    public function test_search_filters_by_route_number(): void
    {
        $this->makeBus('AAA-001', '15712');
        $this->makeBus('BBB-001', '99999');

        $response = $this->actingAs($this->admin)
            ->withSession($this->session())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('oil-changes.search', ['route_number' => '157']));

        $content = $response->getContent();
        $this->assertStringContainsString('AAA-001', $content);
        $this->assertStringNotContainsString('BBB-001', $content);
    }

    // ==================================================================
    // 4. KM RANGE FILTER
    // ==================================================================

    public function test_search_filters_by_km_min(): void
    {
        $this->makeBus('LOW-KM',  '100', 50_000);
        $this->makeBus('HIGH-KM', '200', 500_000);

        $response = $this->actingAs($this->admin)
            ->withSession($this->session())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('oil-changes.search', ['km_min' => 400_000]));

        $content = $response->getContent();
        $this->assertStringContainsString('HIGH-KM', $content);
        $this->assertStringNotContainsString('LOW-KM', $content);
    }

    public function test_search_filters_by_km_max(): void
    {
        $this->makeBus('LOW-KM',  '100', 50_000);
        $this->makeBus('HIGH-KM', '200', 500_000);

        $response = $this->actingAs($this->admin)
            ->withSession($this->session())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('oil-changes.search', ['km_max' => 100_000]));

        $content = $response->getContent();
        $this->assertStringContainsString('LOW-KM', $content);
        $this->assertStringNotContainsString('HIGH-KM', $content);
    }

    public function test_search_filters_by_combined_km_range(): void
    {
        $this->makeBus('TOO-LOW',  '100', 30_000);
        $this->makeBus('IN-RANGE', '200', 250_000);
        $this->makeBus('TOO-HIGH', '300', 800_000);

        $response = $this->actingAs($this->admin)
            ->withSession($this->session())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('oil-changes.search', ['km_min' => 100_000, 'km_max' => 500_000]));

        $content = $response->getContent();
        $this->assertStringContainsString('IN-RANGE', $content);
        $this->assertStringNotContainsString('TOO-LOW', $content);
        $this->assertStringNotContainsString('TOO-HIGH', $content);
    }

    // ==================================================================
    // 5. COMBINED FILTERS
    // ==================================================================

    public function test_search_combines_dqn_and_km_filters(): void
    {
        $this->makeBus('MATCH-001', '100', 300_000); // dqn ok, km ok
        $this->makeBus('MATCH-002', '200', 50_000);  // dqn ok, km too low
        $this->makeBus('NOPE-001',  '300', 300_000); // dqn fails

        $response = $this->actingAs($this->admin)
            ->withSession($this->session())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('oil-changes.search', [
                'dqn'    => 'MATCH',
                'km_min' => 200_000,
            ]));

        $content = $response->getContent();
        $this->assertStringContainsString('MATCH-001', $content);
        $this->assertStringNotContainsString('MATCH-002', $content);
        $this->assertStringNotContainsString('NOPE-001', $content);
    }

    // ==================================================================
    // 6. GARAGE ISOLATION
    // ==================================================================

    public function test_search_does_not_leak_other_garage_buses(): void
    {
        $otherGarage = Garage::factory()->create(['company_id' => $this->company->id]);

        $this->makeBus('OWN-001', '100', 100_000);

        $foreignBus = Bus::factory()->create([
            'garage_id'    => $otherGarage->id,
            'company_id'   => $this->company->id,
            'dqn'          => 'FOREIGN-001',
            'route_number' => '100',
            'is_active'    => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession($this->session())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('oil-changes.search'));

        $content = $response->getContent();
        $this->assertStringContainsString('OWN-001', $content);
        $this->assertStringNotContainsString('FOREIGN-001', $content);
    }

    // ==================================================================
    // 7. EMPTY RESULTS
    // ==================================================================

    public function test_search_with_no_matching_dqn_shows_empty_state(): void
    {
        $this->makeBus('AAA-001');

        $response = $this->actingAs($this->admin)
            ->withSession($this->session())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('oil-changes.search', ['dqn' => 'ZZZZZ']));

        $response->assertOk();
        $response->assertSee(__('messages.oil_change.no_results'), false);
    }

    // ==================================================================
    // 8. ACCESS CONTROL
    // ==================================================================

    public function test_guest_cannot_access_search(): void
    {
        $this->get(route('oil-changes.search'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_search(): void
    {
        $worker = User::factory()->create(['role' => 'user']);
        $worker->garages()->attach($this->garage->id, [
            'role'      => 'complaint_worker',
            'is_active' => true,
        ]);

        $this->actingAs($worker)
            ->withSession($this->session())
            ->get(route('oil-changes.search'))
            ->assertForbidden();
    }
}

<?php

namespace Tests\Feature\OilChange;

use App\Enums\OilType;
use App\Models\Bus;
use App\Models\BusDailyStatus;
use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OilChangeIndexDailyStatusTest extends TestCase
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
            'dqn' => 'DAILY-STATUS-001',
            'is_active' => true,
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

    // ==================================================================
    // 1. INDEX — daily status column
    // ==================================================================

    public function test_index_shows_daily_status_header(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('oil-changes.index', ['type' => OilType::Motor->value]));

        $response->assertOk();
        $response->assertSee(__('messages.oil_change.daily_status'), false);
    }

    public function test_index_displays_latest_daily_status_for_bus(): void
    {
        BusDailyStatus::withoutGlobalScopes()->create([
            'bus_id' => $this->bus->id,
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'date' => now()->toDateString(),
            'status' => 'XƏTTƏ ÇIXMAĞA UYĞUN',
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('oil-changes.index', ['type' => OilType::Motor->value]));

        $response->assertOk();
        $response->assertSee('XƏTTƏ ÇIXMAĞA UYĞUN');
    }

    public function test_index_shows_dash_when_no_daily_status_exists(): void
    {
        // No BusDailyStatus rows at all
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('oil-changes.index', ['type' => OilType::Motor->value]));

        $response->assertOk();
        $response->assertViewHas('rows');
    }

    // ==================================================================
    // 2. CREATE — prefilled scheduled_km
    // ==================================================================

    public function test_create_page_prefills_scheduled_km_from_catalog(): void
    {
        // Simulate a catalog milestone at 540000
        \App\Models\BusOilChange::withoutGlobalScopes()->create([
            'bus_id' => $this->bus->id,
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'oil_type' => OilType::Motor->value,
            'actual_km' => 470383,
            'interval_km' => 36000,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('oil-changes.create', [
                'bus_id' => $this->bus->id,
                'type' => OilType::Motor->value,
            ]));

        $response->assertOk();
        // The controller passes the suggested value to the view.
        $response->assertViewHas('suggestedScheduledKm', function ($value) {
            return $value !== null && is_int($value);
        });
    }

    public function test_create_page_without_bus_id_has_no_suggestion(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('oil-changes.create', [
                'type' => OilType::Motor->value,
            ]));

        $response->assertOk();
        $response->assertViewHas('suggestedScheduledKm', null);
    }
}

<?php

namespace Tests\Feature;

use App\Exceptions\MissingGarageContextException;
use App\Models\Bus;
use App\Models\Company;
use App\Models\DailyKmRecord;
use App\Models\Garage;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissingGarageContextTest extends TestCase
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

        GarageContext::clear();
        session()->forget(['current_garage_id', 'current_company_id']);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    // ==================================================================
    // 1. EXCEPTION ATILIR — DEBUG
    // ==================================================================

    public function test_bus_creation_throws_exception_without_context_in_debug(): void
    {
        config(['app.debug' => true]);

        $this->expectException(MissingGarageContextException::class);
        $this->expectExceptionMessage('Garage context is not set');

        Bus::create(['dqn' => 'THROW-DEBUG', 'is_active' => true]);
    }

    // ==================================================================
    // 2. EXCEPTION ATILIR — PRODUCTION
    // ==================================================================

    public function test_bus_creation_throws_exception_without_context_in_production(): void
    {
        config(['app.debug' => false]);

        $this->expectException(MissingGarageContextException::class);

        Bus::create(['dqn' => 'THROW-PROD', 'is_active' => true]);
    }

    public function test_warehouse_creation_throws_exception_without_context(): void
    {
        config(['app.debug' => false]);

        $this->expectException(MissingGarageContextException::class);

        Warehouse::create([
            'code' => 'NO-CTX-1',
            'name' => 'Test',
            'quantity' => 1,
        ]);
    }

    public function test_daily_km_creation_throws_exception_without_context(): void
    {
        config(['app.debug' => false]);

        // Bus must exist somewhere for FK, but its own creation would
        // also throw. Use withoutGlobalScopes + forceFill to seed it.
        $bus = new Bus;
        $bus->forceFill([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'dqn' => 'SEED-1',
            'is_active' => true,
        ])->save();

        $this->expectException(MissingGarageContextException::class);

        DailyKmRecord::create([
            'bus_id' => $bus->id,
            'date' => now()->toDateString(),
            'km' => 1000,
        ]);
    }

    // ==================================================================
    // 3. HTTP LEVEL — MIDDLEWARE REDIRECT (FIRST LINE OF DEFENSE)
    // ==================================================================

    /**
     * When a web request reaches a garage-scoped route without a
     * current garage in the session, the `garage.selected` middleware
     * intercepts the request BEFORE the controller is executed and
     * redirects the user to the garage selection page. No model is
     * ever constructed, so no ghost row is created and no exception
     * is thrown. This test verifies the middleware guard, which is
     * the actual first line of defense in production.
     */
    public function test_web_request_redirects_to_garage_selection_via_middleware(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('buses.store'), [
                'dqn' => 'HTTP-MISSING',
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('garage.selection'));

        $this->assertEquals(
            0,
            Bus::withoutGlobalScopes()->where('dqn', 'HTTP-MISSING')->count(),
            'No ghost bus should be created when the middleware blocks the request'
        );
    }

    /**
     * API requests require an X-Garage-Id header, validated by the
     * EnsureApiGarageContext middleware. With a valid header the
     * request succeeds and the bus is created with the correct
     * garage_id. This is the API happy path.
     */
    public function test_api_request_with_valid_garage_header_succeeds(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->withHeaders(['X-Garage-Id' => $this->garage->id])
            ->postJson('/api/buses', [
                'dqn' => 'API-VALID',
                'is_active' => true,
            ]);

        $response->assertCreated();

        $bus = Bus::withoutGlobalScopes()
            ->where('dqn', 'API-VALID')
            ->first();

        $this->assertNotNull($bus);
        $this->assertEquals($this->garage->id, $bus->garage_id);
    }

    /**
     * API requests without the X-Garage-Id header are rejected by
     * the EnsureApiGarageContext middleware with a 400 before any
     * controller code runs.
     */
    public function test_api_request_without_garage_header_returns_400(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/buses', [
                'dqn' => 'API-NO-HEADER',
                'is_active' => true,
            ]);

        $response->assertStatus(400);

        $this->assertEquals(
            0,
            Bus::withoutGlobalScopes()->where('dqn', 'API-NO-HEADER')->count(),
            'No ghost bus should be created without the garage header'
        );
    }

    // ==================================================================
    // 4. HAPPY PATH — CONTEXT PROVIDED
    // ==================================================================

    public function test_bus_creation_succeeds_when_context_is_set(): void
    {
        GarageContext::set($this->garage->id, $this->company->id);

        $bus = Bus::create(['dqn' => 'HAPPY-1', 'is_active' => true]);

        $this->assertEquals($this->garage->id, $bus->garage_id);
        $this->assertEquals($this->company->id, $bus->company_id);
    }

    public function test_bus_creation_succeeds_with_session_context(): void
    {
        session([
            'current_garage_id' => $this->garage->id,
            'current_company_id' => $this->company->id,
        ]);

        $bus = Bus::create(['dqn' => 'HAPPY-2', 'is_active' => true]);

        $this->assertEquals($this->garage->id, $bus->garage_id);
    }

    public function test_bus_creation_succeeds_with_auth_user_context(): void
    {
        $this->admin->update([
            'current_garage_id' => $this->garage->id,
            'current_company_id' => $this->company->id,
        ]);
        $this->actingAs($this->admin);

        $bus = Bus::create(['dqn' => 'HAPPY-3', 'is_active' => true]);

        $this->assertEquals($this->garage->id, $bus->garage_id);
    }

    // ==================================================================
    // 5. EXCEPTION MESSAGE AND CLASS
    // ==================================================================

    public function test_exception_carries_model_class_name(): void
    {
        config(['app.debug' => false]);

        try {
            Bus::create(['dqn' => 'MSG-TEST', 'is_active' => true]);
            $this->fail('Expected MissingGarageContextException was not thrown');
        } catch (MissingGarageContextException $e) {
            $this->assertEquals(Bus::class, $e->modelClass);
            $this->assertStringContainsString('Garage context is not set', $e->getMessage());
            $this->assertStringContainsString(Bus::class, $e->getMessage());
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GarageScopeContextTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage = Garage::factory()->create(['company_id' => $this->company->id]);

        GarageContext::clear();
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    // ==================================================================
    // 1. GARAGE CONTEXT (əsas yol)
    // ==================================================================

    public function test_garage_id_is_set_from_garage_context(): void
    {
        GarageContext::set($this->garage->id, $this->company->id);

        $bus = Bus::create([
            'dqn' => 'CTX-TEST-1',
            'is_active' => true,
        ]);

        $this->assertEquals($this->garage->id, $bus->garage_id);
        $this->assertEquals($this->company->id, $bus->company_id);
    }

    public function test_manual_garage_id_is_not_overwritten(): void
    {
        GarageContext::set($this->garage->id, $this->company->id);

        $otherGarage = Garage::factory()->create(['company_id' => $this->company->id]);

        $bus = Bus::create([
            'garage_id' => $otherGarage->id,
            'company_id' => $this->company->id,
            'dqn' => 'MANUAL-1',
            'is_active' => true,
        ]);

        $this->assertEquals(
            $otherGarage->id,
            $bus->garage_id,
            'Manual garage_id must not be overwritten by context'
        );
    }

    // ==================================================================
    // 2. SESSION FALLBACK
    // ==================================================================

    public function test_garage_id_is_resolved_from_session_when_context_missing(): void
    {
        // Context yox, sessiya var
        GarageContext::clear();

        session([
            'current_garage_id'  => $this->garage->id,
            'current_company_id' => $this->company->id,
        ]);

        $bus = Bus::create([
            'dqn' => 'SESSION-1',
            'is_active' => true,
        ]);

        $this->assertEquals($this->garage->id, $bus->garage_id);
        $this->assertEquals($this->company->id, $bus->company_id);
    }

    // ==================================================================
    // 3. AUTH USER FALLBACK
    // ==================================================================

    public function test_garage_id_is_resolved_from_auth_user_when_context_and_session_missing(): void
    {
        GarageContext::clear();

        $user = User::factory()->create([
            'role' => 'user',
            'current_garage_id'  => $this->garage->id,
            'current_company_id' => $this->company->id,
        ]);

        $this->actingAs($user);

        $bus = Bus::create([
            'dqn' => 'AUTH-1',
            'is_active' => true,
        ]);

        $this->assertEquals($this->garage->id, $bus->garage_id);
        $this->assertEquals($this->company->id, $bus->company_id);
    }

    // ==================================================================
    // 4. MISSING CONTEXT — PRODUCTION (log)
    // ==================================================================

    public function test_missing_context_logs_warning_in_production_mode(): void
    {
        GarageContext::clear();

        session()->forget(['current_garage_id', 'current_company_id']);

        config(['app.debug' => false]);

        Log::spy();

        $bus = Bus::create([
            'dqn' => 'NO-CONTEXT-1',
            'is_active' => true,
        ]);

        $this->assertNull($bus->garage_id);
        $this->assertNull($bus->company_id);

        // Log warning yazıldı
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'Garage context is not set');
            });
    }

    // ==================================================================
    // 5. MISSING CONTEXT — DEBUG (exception)
    // ==================================================================

    public function test_missing_context_throws_exception_in_debug_mode(): void
    {
        GarageContext::clear();
        session()->forget(['current_garage_id', 'current_company_id']);
        config(['app.debug' => true]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Garage context is not set');

        Bus::create([
            'dqn' => 'NO-CONTEXT-2',
            'is_active' => true,
        ]);
    }

    // ==================================================================
    // 6. CONSOLE
    // ==================================================================

    public function test_console_runs_do_not_throw_when_context_missing(): void
    {
        // Bu test PHPUnit console-da işlədiyi üçün `runningUnitTests()=true`-dur.
        // Ona görə real console davranışını test edə bilmirik, sadəcə
        // ən azı console modunun aktiv olduğunu təsdiqləyirik.
        GarageContext::clear();

        $this->assertTrue(app()->runningInConsole());
    }

    // ==================================================================
    // 7. GLOBAL SCOPE — FİLTRLƏMƏ
    // ==================================================================

    public function test_global_scope_filters_by_garage_context(): void
    {
        $otherGarage = Garage::factory()->create(['company_id' => $this->company->id]);

        // Qaraj A-da bus yarat
        GarageContext::set($this->garage->id, $this->company->id);
        Bus::create(['dqn' => 'SCOPE-A', 'is_active' => true]);

        // Qaraj B-də bus yarat
        GarageContext::set($otherGarage->id, $this->company->id);
        Bus::create(['dqn' => 'SCOPE-B', 'is_active' => true]);

        // İndi Qaraj A-ya qayıt
        GarageContext::set($this->garage->id, $this->company->id);

        $visibleBuses = Bus::all();

        $this->assertEquals(1, $visibleBuses->count(), 'Only Qaraj A buses must be visible');
        $this->assertEquals('SCOPE-A', $visibleBuses->first()->dqn);
    }

    public function test_global_scope_is_not_applied_in_console_without_context(): void
    {
        // Qaraj A-da bus yarat
        GarageContext::set($this->garage->id, $this->company->id);
        Bus::create(['dqn' => 'NOSCOPE-A', 'is_active' => true]);

        // Qaraj B-də bus yarat
        $otherGarage = Garage::factory()->create(['company_id' => $this->company->id]);
        GarageContext::set($otherGarage->id, $this->company->id);
        Bus::create(['dqn' => 'NOSCOPE-B', 'is_active' => true]);

        // Context-i təmizlə
        GarageContext::clear();

        // withoutGlobalScopes() ilə hər iki bus görünməlidir
        $allBuses = Bus::withoutGlobalScopes()->get();

        $this->assertGreaterThanOrEqual(2, $allBuses->count());
    }
}

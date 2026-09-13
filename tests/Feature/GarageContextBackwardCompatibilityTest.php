<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GarageContextBackwardCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage  = Garage::factory()->create(['company_id' => $this->company->id]);

        GarageContext::clear();
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    // ==================================================================
    // 1. LEGACY Garage::getCurrentId()
    // ==================================================================

    public function test_legacy_get_current_id_still_works_via_context(): void
    {
        GarageContext::set($this->garage->id, $this->company->id);

        $this->assertSame($this->garage->id, Garage::getCurrentId());
        $this->assertSame($this->company->id, Garage::getCurrentCompanyId());
    }

    public function test_legacy_get_current_id_resolves_via_session(): void
    {
        session([
            'current_garage_id'  => $this->garage->id,
            'current_company_id' => $this->company->id,
        ]);

        $this->assertSame($this->garage->id, Garage::getCurrentId());
    }

    public function test_legacy_get_current_id_resolves_via_auth(): void
    {
        $user = User::factory()->create([
            'role'               => 'user',
            'current_garage_id'  => $this->garage->id,
            'current_company_id' => $this->company->id,
        ]);
        $this->actingAs($user);

        $this->assertSame($this->garage->id, Garage::getCurrentId());
    }

    public function test_legacy_get_current_id_returns_null_without_context(): void
    {
        $this->assertNull(Garage::getCurrentId());
        $this->assertNull(Garage::getCurrentCompanyId());
    }

    // ==================================================================
    // 2. HasGarageScope — CREATING event still works
    // ==================================================================

    public function test_bus_created_from_context_gets_garage_id(): void
    {
        GarageContext::set($this->garage->id, $this->company->id);

        $bus = Bus::create(['dqn' => 'CTX-001', 'is_active' => true]);

        $this->assertSame($this->garage->id, $bus->garage_id);
        $this->assertSame($this->company->id, $bus->company_id);
    }

    public function test_bus_created_from_session_gets_garage_id(): void
    {
        session([
            'current_garage_id'  => $this->garage->id,
            'current_company_id' => $this->company->id,
        ]);

        $bus = Bus::create(['dqn' => 'SESS-001', 'is_active' => true]);

        $this->assertSame($this->garage->id, $bus->garage_id);
        $this->assertSame($this->company->id, $bus->company_id);
    }

    public function test_bus_created_from_auth_user_gets_garage_id(): void
    {
        $user = User::factory()->create([
            'role'               => 'user',
            'current_garage_id'  => $this->garage->id,
            'current_company_id' => $this->company->id,
        ]);
        $this->actingAs($user);

        $bus = Bus::create(['dqn' => 'AUTH-001', 'is_active' => true]);

        $this->assertSame($this->garage->id, $bus->garage_id);
        $this->assertSame($this->company->id, $bus->company_id);
    }

    public function test_manual_garage_id_is_not_overwritten(): void
    {
        GarageContext::set($this->garage->id, $this->company->id);

        $otherGarage = Garage::factory()->create(['company_id' => $this->company->id]);

        $bus = Bus::create([
            'garage_id'  => $otherGarage->id,
            'company_id' => $this->company->id,
            'dqn'        => 'MANUAL-001',
            'is_active'  => true,
        ]);

        $this->assertSame($otherGarage->id, $bus->garage_id);
    }
}

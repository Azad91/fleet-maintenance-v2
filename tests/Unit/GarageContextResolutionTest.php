<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class GarageContextResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garageA;

    protected Garage $garageB;

    protected function setUp(): void
    {
        parent::setUp();

        GarageContext::clear();

        $this->company = Company::factory()->create();
        $this->garageA = Garage::factory()->create(['company_id' => $this->company->id]);
        $this->garageB = Garage::factory()->create(['company_id' => $this->company->id]);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    // ==================================================================
    // 1. CONTEXT PRIORITY
    // ==================================================================

    public function test_context_takes_precedence_over_session_and_user(): void
    {
        GarageContext::set($this->garageA->id, $this->company->id);

        session([
            'current_garage_id' => $this->garageB->id,
            'current_company_id' => $this->company->id,
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'current_garage_id' => $this->garageB->id,
            'current_company_id' => $this->company->id,
        ]);
        Auth::login($user);

        $this->assertSame($this->garageA->id, GarageContext::resolveGarageId());
    }

    // ==================================================================
    // 2. SESSION FALLBACK
    // ==================================================================

    public function test_session_is_used_when_context_empty(): void
    {
        session([
            'current_garage_id' => $this->garageA->id,
            'current_company_id' => $this->company->id,
        ]);

        $this->assertSame($this->garageA->id, GarageContext::resolveGarageId());
        $this->assertSame($this->company->id, GarageContext::resolveCompanyId());
    }

    public function test_session_takes_precedence_over_auth_user(): void
    {
        session([
            'current_garage_id' => $this->garageA->id,
            'current_company_id' => $this->company->id,
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'current_garage_id' => $this->garageB->id,
            'current_company_id' => $this->company->id,
        ]);
        Auth::login($user);

        $this->assertSame($this->garageA->id, GarageContext::resolveGarageId());
    }

    // ==================================================================
    // 3. AUTH USER FALLBACK
    // ==================================================================

    public function test_auth_user_is_used_when_context_and_session_empty(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'current_garage_id' => $this->garageB->id,
            'current_company_id' => $this->company->id,
        ]);
        Auth::login($user);

        $this->assertSame($this->garageB->id, GarageContext::resolveGarageId());
        $this->assertSame($this->company->id, GarageContext::resolveCompanyId());
    }

    // ==================================================================
    // 4. NULL FALLBACK
    // ==================================================================

    public function test_returns_null_when_nothing_is_available(): void
    {
        $this->assertNull(GarageContext::resolveGarageId());
        $this->assertNull(GarageContext::resolveCompanyId());
    }

    public function test_returns_null_when_user_has_no_current_garage(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'current_garage_id' => null,
            'current_company_id' => null,
        ]);
        Auth::login($user);

        $this->assertNull(GarageContext::resolveGarageId());
    }

    // ==================================================================
    // 5. fromUser
    // ==================================================================

    public function test_from_user_populates_context(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'current_garage_id' => $this->garageA->id,
            'current_company_id' => $this->company->id,
        ]);

        $result = GarageContext::fromUser($user);

        $this->assertTrue($result);
        $this->assertSame($this->garageA->id, GarageContext::getGarageId());
        $this->assertSame($this->company->id, GarageContext::getCompanyId());
    }

    public function test_from_user_returns_false_when_no_current_garage(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'current_garage_id' => null,
            'current_company_id' => null,
        ]);

        $result = GarageContext::fromUser($user);

        $this->assertFalse($result);
        $this->assertNull(GarageContext::getGarageId());
    }

    // ==================================================================
    // 6. resolveGarage
    // ==================================================================

    public function test_resolve_garage_returns_model(): void
    {
        GarageContext::set($this->garageA->id, $this->company->id);

        $garage = GarageContext::resolveGarage();

        $this->assertNotNull($garage);
        $this->assertSame($this->garageA->id, $garage->id);
    }

    public function test_resolve_garage_returns_null_when_no_context(): void
    {
        $this->assertNull(GarageContext::resolveGarage());
    }
}

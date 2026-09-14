<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tests\Traits\MakesSuperAdminWithMfa;

class PostLoginRedirectorTest extends TestCase
{
    use MakesSuperAdminWithMfa;
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garageA;

    protected Garage $garageB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();

        $this->garageA = Garage::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->garageB = Garage::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
    }

    // ==================================================================
    // 1. DIRECTOR — EMAIL LOGIN
    // ==================================================================

    public function test_director_email_login_redirects_to_director_dashboard(): void
    {
        $director = User::factory()->create([
            'email' => 'director@test.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'employee_code' => 'DIR-TEST-1',
            'is_active' => true,
        ]);

        // Attach Director role via company_user pivot — NOT users.role.
        $this->company->users()->attach($director->id, [
            'role' => 'director',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'director@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('director.dashboard'));
        $this->assertAuthenticatedAs($director);
    }

    // ==================================================================
    // 2. DIRECTOR — PIN LOGIN
    // ==================================================================

    public function test_director_pin_login_redirects_to_director_dashboard(): void
    {
        $director = User::factory()->create([
            'email' => 'director@test.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'employee_code' => 'DIR-TEST-2',
            'pin' => Hash::make('1234'),
            'pin_is_default' => false, // Skip force-change flow
            'is_active' => true,
        ]);

        $this->company->users()->attach($director->id, [
            'role' => 'director',
            'is_active' => true,
        ]);

        $response = $this->post('/login/pin', [
            'employee_code' => 'DIR-TEST-2',
            'pin' => '1234',
        ]);

        $response->assertRedirect(route('director.dashboard'));
        $this->assertAuthenticatedAs($director);
    }

    // ==================================================================
    // 3. REGULAR USER — 1 GARAGE → AUTO-SELECT
    // ==================================================================

    public function test_user_with_single_garage_is_auto_selected_and_redirected_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'single@test.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'is_active' => true,
        ]);

        $user->garages()->attach($this->garageA->id, [
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'single@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));

        // Session was populated with the auto-selected garage
        $this->assertEquals($this->garageA->id, session('current_garage_id'));
        $this->assertEquals($this->company->id, session('current_company_id'));
    }

    // ==================================================================
    // 4. REGULAR USER — 2 GARAGES → SELECTION
    // ==================================================================

    public function test_user_with_multiple_garages_is_redirected_to_selection(): void
    {
        $user = User::factory()->create([
            'email' => 'multi@test.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'is_active' => true,
        ]);

        $user->garages()->attach($this->garageA->id, ['role' => 'admin', 'is_active' => true]);
        $user->garages()->attach($this->garageB->id, ['role' => 'admin', 'is_active' => true]);

        $response = $this->post('/login', [
            'email' => 'multi@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('garage.selection'));
    }

    // ==================================================================
    // 5. REGULAR USER — 0 GARAGES → SELECTION WITH ERROR
    // ==================================================================

    public function test_user_without_any_garage_is_redirected_to_selection_with_error(): void
    {
        $user = User::factory()->create([
            'email' => 'none@test.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'none@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('garage.selection'));
        $response->assertSessionHas('error');
    }

    // ==================================================================
    // 6. SUPER ADMIN — NO GARAGE MEMBERSHIP
    // ==================================================================

    public function test_super_admin_with_mfa_login_redirects_to_two_factor_challenge(): void
    {
        $superAdmin = $this->makeSuperAdminWithMfa();

        $response = $this->post('/login', [
            'email' => $superAdmin->email,
            'password' => 'password',
        ]);

        // SuperAdmin with MFA configured: login is deferred until the
        // TOTP code is verified. The user must be a guest at this point.
        $response->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
        $this->assertEquals($superAdmin->id, session('two_factor.user_id'));
    }

    public function test_super_admin_without_mfa_login_redirects_to_setup(): void
    {
        $superAdmin = User::factory()->create([
            'email' => 'sa@test.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'sa@test.com',
            'password' => 'password',
        ]);

        // A freshly-seeded SuperAdmin has no confirmed MFA secret. The
        // challenge screen would deadlock (it needs a secret to verify),
        // so the flow logs the user in and sends them to the setup wizard.
        $response->assertRedirect(route('super-admin.security.2fa.setup'));
        $this->assertAuthenticatedAs($superAdmin);
    }

    // ==================================================================
    // 7. INACTIVE GARAGE MEMBERSHIP IS IGNORED
    // ==================================================================

    public function test_inactive_garage_membership_is_not_auto_selected(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@test.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'is_active' => true,
        ]);

        $user->garages()->attach($this->garageA->id, [
            'role' => 'admin',
            'is_active' => false, // ← INACTIVE
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@test.com',
            'password' => 'password',
        ]);

        // No active garage → treated as 0 garages → selection with error
        $response->assertRedirect(route('garage.selection'));
        $response->assertSessionHas('error');
    }

    // ==================================================================
    // 8. PIN LOGIN AND EMAIL LOGIN PRODUCE IDENTICAL REDIRECTS
    // ==================================================================

    public function test_pin_login_and_email_login_use_same_redirect_logic(): void
    {
        // Two different users, two different garages — avoids violating
        // the "one active admin per garage" unique index.
        // The point of this test is to verify that both login flows
        // produce identical redirect targets for the same role setup.
        $emailUser = User::factory()->create([
            'email' => 'email-user@test.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'is_active' => true,
        ]);
        $emailUser->garages()->attach($this->garageA->id, [
            'role' => 'admin',
            'is_active' => true,
        ]);

        $pinUser = User::factory()->create([
            'email' => 'pin-user@test.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'employee_code' => 'PIN-TEST-1',
            'pin' => Hash::make('1234'),
            'pin_is_default' => false,
            'is_active' => true,
        ]);
        // Second user belongs to garage B — no conflict with the
        // single-admin-per-garage index because they hold a worker role.
        $pinUser->garages()->attach($this->garageB->id, [
            'role' => 'complaint_worker',
            'is_active' => true,
        ]);

        // Email login — user has 1 garage (A) → auto-select + dashboard
        $emailResponse = $this->post('/login', [
            'email' => 'email-user@test.com',
            'password' => 'password',
        ]);

        // Reset session between logins
        $this->post('/logout');

        // PIN login — user has 1 garage (B) → auto-select + dashboard
        $pinResponse = $this->post('/login/pin', [
            'employee_code' => 'PIN-TEST-1',
            'pin' => '1234',
        ]);

        // Both redirect to the same target (dashboard), even though
        // they are two different users in two different garages.
        // This proves that both login flows share the same redirect logic.
        $emailResponse->assertRedirect(route('dashboard'));
        $pinResponse->assertRedirect(route('dashboard'));
    }
}

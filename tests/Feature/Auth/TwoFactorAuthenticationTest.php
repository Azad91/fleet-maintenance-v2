<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\TwoFactor\TwoFactorManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    // ==================================================================
    // 1. LOGIN FLOW — SuperAdmin redirected to challenge
    // ==================================================================

    public function test_super_admin_with_mfa_login_redirects_to_two_factor_challenge(): void
    {
        $sa = $this->makeSuperAdminWithMfa();

        $response = $this->post('/login', [
            'email' => $sa->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
        $this->assertEquals($sa->id, session('two_factor.user_id'));
    }

    public function test_super_admin_without_mfa_login_redirects_to_setup(): void
    {
        $sa = User::factory()->create([
            'email' => 'sa@test.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'sa@test.com',
            'password' => 'password',
        ]);

        // Setup wizard is reached authenticated; the challenge cannot be
        // shown because there is no secret to verify yet.
        $response->assertRedirect(route('super-admin.security.2fa.setup'));
        $this->assertAuthenticatedAs($sa);
    }

    public function test_regular_user_login_still_completes_normally(): void
    {
        $user = User::factory()->create([
            'email' => 'user@test.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'user@test.com',
            'password' => 'password',
        ]);

        // Should NOT redirect to 2FA
        $response->assertRedirect();
        $this->assertStringNotContainsString('two-factor-challenge', $response->headers->get('Location') ?? '');
    }

    // ==================================================================
    // 2. CHALLENGE — valid TOTP code logs the user in
    // ==================================================================

    public function test_valid_totp_code_completes_login(): void
    {
        $sa = $this->makeSuperAdminWithMfa();

        $code = app(Google2FA::class)->getCurrentOtp($sa->two_factor_secret);

        $response = $this->withSession(['two_factor.user_id' => $sa->id])
            ->post(route('two-factor.challenge.store'), ['code' => $code]);

        $response->assertRedirect(route('super-admin.dashboard'));
        $this->assertAuthenticatedAs($sa);
        $this->assertNull(session('two_factor.user_id'));
    }

    public function test_invalid_totp_code_does_not_log_in(): void
    {
        $sa = $this->makeSuperAdminWithMfa();

        $response = $this->withSession(['two_factor.user_id' => $sa->id])
            ->post(route('two-factor.challenge.store'), ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    // ==================================================================
    // 3. CHALLENGE — recovery code path
    // ==================================================================

    public function test_valid_recovery_code_completes_login(): void
    {
        $sa = $this->makeSuperAdminWithMfa();

        $plainCode = 'ABCDE-FGHIJ';
        $sa->forceFill([
            'two_factor_recovery_codes' => [Hash::make($plainCode)],
        ])->save();

        $response = $this->withSession(['two_factor.user_id' => $sa->id])
            ->post(route('two-factor.challenge.store'), ['code' => $plainCode]);

        $response->assertRedirect(route('super-admin.dashboard'));
        $this->assertAuthenticatedAs($sa);
    }

    public function test_used_recovery_code_cannot_be_reused(): void
    {
        $sa = $this->makeSuperAdminWithMfa();

        $plainCode = 'ABCDE-FGHIJ';
        $sa->forceFill([
            'two_factor_recovery_codes' => [Hash::make($plainCode)],
        ])->save();

        // First use: success
        $this->withSession(['two_factor.user_id' => $sa->id])
            ->post(route('two-factor.challenge.store'), ['code' => $plainCode])
            ->assertRedirect(route('super-admin.dashboard'));

        // Log out
        $this->post('/logout');

        // Second use: must fail
        $response = $this->withSession(['two_factor.user_id' => $sa->id])
            ->post(route('two-factor.challenge.store'), ['code' => $plainCode]);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    // ==================================================================
    // 3b. CHALLENGE — TOTP replay defense
    // ==================================================================

    public function test_totp_code_cannot_be_reused_within_its_validity_window(): void
    {
        $sa = $this->makeSuperAdminWithMfa();

        $code = app(Google2FA::class)->getCurrentOtp($sa->two_factor_secret);

        // First use: succeeds.
        $this->withSession(['two_factor.user_id' => $sa->id])
            ->post(route('two-factor.challenge.store'), ['code' => $code])
            ->assertRedirect(route('super-admin.dashboard'));

        $this->assertAuthenticatedAs($sa);

        // Log out so we can re-attempt.
        $this->post('/logout');
        $this->assertGuest();

        // Second use of the SAME code (still inside the 90-second
        // acceptance window): must be rejected by the replay guard.
        $response = $this->withSession(['two_factor.user_id' => $sa->id])
            ->post(route('two-factor.challenge.store'), ['code' => $code]);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    // ==================================================================
    // 4. SETUP MIDDLEWARE — unverified SA forced into setup
    // ==================================================================

    public function test_super_admin_without_mfa_is_redirected_to_setup(): void
    {
        $sa = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($sa)
            ->get('/super-admin/dashboard')
            ->assertRedirect(route('super-admin.security.2fa.setup'));
    }

    public function test_super_admin_with_mfa_can_access_dashboard(): void
    {
        $sa = $this->makeSuperAdminWithMfa();

        $this->actingAs($sa)
            ->get('/super-admin/dashboard')
            ->assertOk();
    }

    // ==================================================================
    // 5. SETUP FLOW
    // ==================================================================

    public function test_setup_page_shows_qr_code(): void
    {
        $sa = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($sa)
            ->get(route('super-admin.security.2fa.setup'));

        $response->assertOk();
        $response->assertSee('data:image/svg+xml', false);
    }

    public function test_setup_confirm_requires_valid_code(): void
    {
        $sa = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        // Start setup — this generates a secret
        $this->actingAs($sa)->get(route('super-admin.security.2fa.setup'));

        $sa->refresh();

        // Submit a wrong code
        $response = $this->actingAs($sa)
            ->post(route('super-admin.security.2fa.confirm'), ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertNull($sa->fresh()->two_factor_confirmed_at);
    }

    public function test_setup_confirm_with_valid_code_enables_mfa(): void
    {
        $sa = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        // Start setup
        $this->actingAs($sa)->get(route('super-admin.security.2fa.setup'));

        $sa->refresh();
        $secret = $sa->two_factor_secret;
        $this->assertNotNull($secret);

        $code = app(Google2FA::class)->getCurrentOtp($secret);

        $response = $this->actingAs($sa)
            ->post(route('super-admin.security.2fa.confirm'), ['code' => $code]);

        $response->assertRedirect(route('super-admin.security.2fa.recovery-codes'));

        $sa->refresh();
        $this->assertNotNull($sa->two_factor_confirmed_at);
        $this->assertNotNull($sa->two_factor_recovery_codes);
        $this->assertCount(8, $sa->two_factor_recovery_codes);
    }

    // ==================================================================
    // 6. RECOVERY CODES SHOWN ONCE
    // ==================================================================

    public function test_recovery_codes_shown_once_from_session(): void
    {
        $sa = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        // Manually put codes in session (simulating end of setup)
        session(['two_factor_recovery_codes_plain' => ['AAAAA-BBBBB', 'CCCCC-DDDDD']]);

        $response = $this->actingAs($sa)
            ->get(route('super-admin.security.2fa.recovery-codes'));

        $response->assertOk();
        $response->assertSee('AAAAA-BBBBB');

        // Second visit — codes should be gone
        $response2 = $this->actingAs($sa)
            ->get(route('super-admin.security.2fa.recovery-codes'));

        $response2->assertRedirect(route('super-admin.settings.index'));
    }

    // ==================================================================
    // HELPERS
    // ==================================================================

    private function makeSuperAdminWithMfa(): User
    {
        $manager = app(TwoFactorManager::class);

        $sa = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $sa->forceFill([
            'two_factor_secret' => $manager->generateSecret(),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [],
        ])->save();

        return $sa->fresh();
    }

    public function test_soft_deleted_user_cannot_use_recovery_code(): void
    {
        $sa = $this->makeSuperAdminWithMfa();

        $plainCode = 'ABCDE-FGHIJ';
        $sa->forceFill([
            'two_factor_recovery_codes' => [Hash::make($plainCode)],
        ])->save();

        $sa->delete(); // soft delete

        // Attempting to consume a recovery code on a soft-deleted
        // user must fail — the query respects the SoftDeletes scope.
        $result = $sa->useRecoveryCode($plainCode);

        $this->assertFalse($result);

        // The code must still be present in the (soft-deleted) row.
        //
        // We read through Eloquent with withTrashed() so the
        // `encrypted:array` cast decrypts the value for us — a raw
        // DB read returns the ciphertext and json_decode() would
        // simply return null.
        $softDeleted = User::withTrashed()->find($sa->id);

        $this->assertNotNull($softDeleted);
        $this->assertCount(1, $softDeleted->two_factor_recovery_codes);
    }
}

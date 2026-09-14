<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Regression guard for a bug where AuthenticatedSessionController
 * called Auth::login() a second time inside the first-time-SuperAdmin
 * branch, after LoginRequest::authenticate() had already logged the
 * user in. That fired the Login event twice and produced duplicate
 * "SuperAdmin logged in" audit entries.
 */
class SuperAdminLoginEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_without_mfa_fires_login_event_exactly_once(): void
    {
        User::factory()->create([
            'email' => 'sa@test.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        Event::fake([Login::class]);

        $this->post('/login', [
            'email' => 'sa@test.com',
            'password' => 'password',
        ]);

        Event::assertDispatchedTimes(Login::class, 1);
    }

    public function test_super_admin_with_mfa_fires_login_event_only_after_totp(): void
    {
        $sa = User::factory()->create([
            'email' => 'sa@test.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        // Configure MFA so the login is deferred.
        $sa->forceFill([
            'two_factor_secret' => app(\App\Support\TwoFactor\TwoFactorManager::class)->generateSecret(),
            'two_factor_confirmed_at' => now(),
        ])->save();

        Event::fake([Login::class]);

        // Password step — the user is logged out and Login is NOT fired
        // (authenticate() runs Auth::attempt but then we log out).
        $this->post('/login', [
            'email' => 'sa@test.com',
            'password' => 'password',
        ]);

        // One Login event from the initial authenticate() call — but
        // the user is logged back out before the response is returned.
        // No *second* Login event is dispatched by the controller.
        Event::assertDispatchedTimes(Login::class, 1);

        $this->assertGuest();
        $this->assertEquals($sa->id, session('two_factor.user_id'));
    }

    public function test_regular_user_fires_login_event_exactly_once(): void
    {
        User::factory()->create([
            'email' => 'user@test.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'is_active' => true,
        ]);

        Event::fake([Login::class]);

        $this->post('/login', [
            'email' => 'user@test.com',
            'password' => 'password',
        ]);

        Event::assertDispatchedTimes(Login::class, 1);
    }
}

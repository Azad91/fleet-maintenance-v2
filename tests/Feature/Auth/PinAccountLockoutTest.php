<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PinAccountLockoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset all relevant rate limiters between tests.
        RateLimiter::clear('emp-lock|127.0.0.1');
        RateLimiter::clear('emp-lock|10.0.0.1');
        RateLimiter::clear('emp-lock|10.0.0.2');
        RateLimiter::clear('pin_account:emp-lock');
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('emp-lock|127.0.0.1');
        RateLimiter::clear('emp-lock|10.0.0.1');
        RateLimiter::clear('emp-lock|10.0.0.2');
        RateLimiter::clear('pin_account:emp-lock');

        parent::tearDown();
    }

    // ==================================================================
    // 1. HAPPY PATH
    // ==================================================================

    public function test_pin_login_succeeds_with_valid_credentials(): void
    {
        $this->makeUser('EMP-LOCK', '1234');

        $response = $this->post('/login/pin', [
            'employee_code' => 'EMP-LOCK',
            'pin' => '1234',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated();
    }

    // ==================================================================
    // 2. IP-ROTATION BYPASS IS BLOCKED
    // ==================================================================

    public function test_ip_rotation_does_not_bypass_account_lockout(): void
    {
        $this->makeUser('EMP-LOCK', '1234');

        // 15 wrong attempts from 15 different IPs.
        // The per-IP limiter is configured for 5 attempts, but since
        // each request comes from a different IP, that limiter never
        // fires — only the account-level limiter does.
        for ($i = 1; $i <= 15; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$i}"])
                ->post('/login/pin', [
                    'employee_code' => 'EMP-LOCK',
                    'pin' => '0000',
                ]);
        }

        // 16th attempt with the CORRECT PIN from yet another IP
        // must still be blocked.
        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
            ->post('/login/pin', [
                'employee_code' => 'EMP-LOCK',
                'pin' => '1234',
            ]);

        $response->assertSessionHasErrors('employee_code');
        $this->assertGuest();
    }

    // ==================================================================
    // 3. IP-BASED LIMIT STILL WORKS
    // ==================================================================

    public function test_per_ip_rate_limit_blocks_after_five_attempts(): void
    {
        $this->makeUser('EMP-LOCK', '1234');

        // 5 wrong attempts from the SAME IP exhaust the per-IP limiter.
        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
                ->post('/login/pin', [
                    'employee_code' => 'EMP-LOCK',
                    'pin' => '0000',
                ]);
        }

        // 6th attempt, correct PIN, same IP — must be blocked.
        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->post('/login/pin', [
                'employee_code' => 'EMP-LOCK',
                'pin' => '1234',
            ]);

        $response->assertSessionHasErrors('employee_code');
        $this->assertGuest();
    }

    // ==================================================================
    // 4. SUCCESS CLEARS BOTH LIMITERS
    // ==================================================================

    public function test_successful_login_clears_all_limiters(): void
    {
        $this->makeUser('EMP-LOCK', '1234');

        // A few wrong attempts.
        for ($i = 0; $i < 3; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
                ->post('/login/pin', [
                    'employee_code' => 'EMP-LOCK',
                    'pin' => '0000',
                ]);
        }

        // Successful login.
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->post('/login/pin', [
                'employee_code' => 'EMP-LOCK',
                'pin' => '1234',
            ]);

        // Log out so the limiter state is what matters.
        $this->post('/logout');

        // The next 4 attempts must not be pre-blocked by the previous
        // failures. Verify by making 4 more wrong attempts from the
        // same IP — the error should be "auth.failed" (rate limit not
        // yet hit), not the "Too many" throttle message.
        //
        // NOTE: a wrong PIN produces the error under the `pin` key,
        // not `employee_code` — see PinLoginRequest::failAndRateLimit().
        for ($i = 0; $i < 4; $i++) {
            $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
                ->post('/login/pin', [
                    'employee_code' => 'EMP-LOCK',
                    'pin' => '0000',
                ]);

            $response->assertSessionHasErrors('pin');

            $messages = session('errors')->getBag('default')->get('pin');
            $text = implode(' ', $messages);

            $this->assertStringNotContainsString(
                'Too many',
                $text,
                'Limiter should have been cleared by the successful login'
            );
        }
    }

    // ==================================================================
    // HELPERS
    // ==================================================================

    private function makeUser(string $code, string $pin): User
    {
        return User::factory()->create([
            'role' => 'user',
            'employee_code' => $code,
            'pin' => Hash::make($pin),
            'pin_is_default' => false,
            'is_active' => true,
        ]);
    }
}

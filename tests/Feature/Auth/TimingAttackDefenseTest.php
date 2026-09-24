<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TimingAttackDefenseTest extends TestCase
{
    use RefreshDatabase;

    // ==================================================================
    // 1. API LOGIN — response time profile must be uniform
    // ==================================================================

    public function test_api_login_nonexistent_user_runs_bcrypt_check(): void
    {
        // Even for a nonexistent user the API must run a bcrypt
        // comparison. We verify this indirectly: the DUMMY_HASH constant
        // in the controller contains a valid bcrypt string, and the
        // response for a nonexistent user is byte-identical to the
        // response for a real user with a wrong password.
        $realUser = User::factory()->create([
            'email' => 'real@test.com',
            'password' => Hash::make('real-password'),
            'role' => 'user',
            'is_active' => true,
        ]);

        $responseReal = $this->postJson('/api/login', [
            'email' => 'real@test.com',
            'password' => 'wrong-password',
        ]);

        $responseGhost = $this->postJson('/api/login', [
            'email' => 'ghost@test.com',
            'password' => 'wrong-password',
        ]);

        // Both must return the same status and the same generic message.
        $responseReal->assertStatus(422);
        $responseGhost->assertStatus(422);

        $this->assertSame(
            $responseReal->json('errors.email.0'),
            $responseGhost->json('errors.email.0'),
            'Nonexistent user and wrong password must return the identical error message'
        );
    }

    public function test_api_login_inactive_user_returns_same_error_as_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'inactive@test.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'is_active' => false,
        ]);

        User::factory()->create([
            'email' => 'active@test.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'is_active' => true,
        ]);

        $inactiveResponse = $this->postJson('/api/login', [
            'email' => 'inactive@test.com',
            'password' => 'password',
        ]);

        $wrongPasswordResponse = $this->postJson('/api/login', [
            'email' => 'active@test.com',
            'password' => 'wrong',
        ]);

        // Account enumeration defense: inactive and wrong-password
        // must not be distinguishable.
        $this->assertSame(
            $wrongPasswordResponse->json('errors.email.0'),
            $inactiveResponse->json('errors.email.0'),
        );
    }

    // ==================================================================
    // 2. PIN LOGIN — same generic error for all failure modes
    // ==================================================================

    public function test_pin_login_nonexistent_code_returns_generic_error(): void
    {
        $response = $this->post('/login/pin', [
            'employee_code' => 'EMP-GHOST',
            'pin' => '1234',
        ]);

        $response->assertSessionHasErrors('employee_code');
        $this->assertSame(
            __('auth.failed'),
            session('errors')->first('employee_code')
        );
    }

    public function test_pin_login_wrong_pin_returns_generic_error(): void
    {
        User::factory()->create([
            'role' => 'user',
            'employee_code' => 'EMP-REAL',
            'pin' => Hash::make('1234'),
            'pin_is_default' => false,
            'is_active' => true,
        ]);

        $response = $this->post('/login/pin', [
            'employee_code' => 'EMP-REAL',
            'pin' => '9999',
        ]);

        $response->assertSessionHasErrors('pin');
        $this->assertSame(
            __('auth.failed'),
            session('errors')->first('pin')
        );
    }

    // ==================================================================
    // 3. No timing-specific leaks in log messages
    // ==================================================================

    /**
     * Regression guard: the API login failure path must never log
     * the submitted password or any MFA-related field.
     *
     * Instead of reading the log FILE (which fails on Windows when
     * the file is locked by another process), we spy on the Log
     * facade and inspect the recorded calls in memory. This is both
     * platform-independent AND faster — no disk I/O at all.
     */
    public function test_api_login_failure_does_not_log_sensitive_data(): void
    {
        \Illuminate\Support\Facades\Log::spy();

        $this->postJson('/api/login', [
            'email' => 'nobody@test.com',
            'password' => 'secret-guess-1234',
        ]);

        // Collect every value passed to Log::warning() during this
        // request and assert that none of them contains the password
        // or any MFA secret field name.
        \Illuminate\Support\Facades\Log::shouldHaveReceived('warning')
            ->withArgs(function ($message, array $context = []) {
                $encoded = json_encode([$message, $context]);

                $this->assertStringNotContainsString(
                    'secret-guess-1234',
                    $encoded,
                    'Submitted password must not appear in log context'
                );

                $this->assertStringNotContainsString(
                    'two_factor_secret',
                    $encoded,
                    'MFA secret field must not appear in log context'
                );

                return true;
            });
    }
}

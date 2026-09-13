<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ApiLoginRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('api_login:api-test@example.com|127.0.0.1');
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('api_login:api-test@example.com|127.0.0.1');
        RateLimiter::clear('api_login:inactive@example.com|127.0.0.1');
        parent::tearDown();
    }

    // ==================================================================
    // 1. ƏSAS RATE LIMIT
    // ==================================================================

    public function test_login_succeeds_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email'     => 'api-test@example.com',
            'password'  => Hash::make('secret123'),
            'role'      => 'user',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'api-test@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'user']);

        $this->assertEquals('Bearer', $response->json('token_type'));
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        User::factory()->create([
            'email'     => 'api-test@example.com',
            'password'  => Hash::make('correct-password'),
            'role'      => 'user',
            'is_active' => true,
        ]);

        // 5 səhv cəhd
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/login', [
                'email'    => 'api-test@example.com',
                'password' => 'wrong-password',
            ]);

            $response->assertStatus(422);
        }

        // 6-cı cəhd → rate limit mesajı
        $response = $this->postJson('/api/login', [
            'email'    => 'api-test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $errorMessage = $response->json('errors.email.0');
        $this->assertStringContainsString('Too many', $errorMessage);
    }

    public function test_rate_limit_blocks_correct_password_too(): void
    {
        User::factory()->create([
            'email'     => 'api-test@example.com',
            'password'  => Hash::make('correct-password'),
            'role'      => 'user',
            'is_active' => true,
        ]);

        // 5 səhv cəhd → lockout
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email'    => 'api-test@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // İndi düzgün şifrə ilə cəhd → hələ də bloklanır
        $response = $this->postJson('/api/login', [
            'email'    => 'api-test@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('Too many', $response->json('errors.email.0'));
    }

    public function test_successful_login_clears_rate_limit(): void
    {
        User::factory()->create([
            'email'     => 'api-test@example.com',
            'password'  => Hash::make('correct-password'),
            'role'      => 'user',
            'is_active' => true,
        ]);

        // 3 səhv cəhd
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/login', [
                'email'    => 'api-test@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // Düzgün şifrə → uğurlu (rate limit sıfırlanır)
        $response = $this->postJson('/api/login', [
            'email'    => 'api-test@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertOk();

        // Rate limit sıfırlandı → yenidən 5 cəhd mümkündür.
        // İlk 5 cəhd "credentials do not match" qaytarır — "Too many" DEYİL.
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/login', [
                'email'    => 'api-test@example.com',
                'password' => 'wrong-password',
            ]);

            $errorMessage = $response->json('errors.email.0') ?? '';

            $this->assertStringNotContainsString(
                'Too many',
                $errorMessage,
                'Rate limit should have been cleared after successful login'
            );
        }
    }

    public function test_different_emails_have_independent_rate_limits(): void
    {
        User::factory()->create([
            'email'     => 'api-test@example.com',
            'password'  => Hash::make('secret'),
            'role'      => 'user',
            'is_active' => true,
        ]);

        User::factory()->create([
            'email'     => 'other@example.com',
            'password'  => Hash::make('secret'),
            'role'      => 'user',
            'is_active' => true,
        ]);

        // First email — 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email'    => 'api-test@example.com',
                'password' => 'wrong',
            ]);
        }

        // Second email still works fine
        $response = $this->postJson('/api/login', [
            'email'    => 'other@example.com',
            'password' => 'secret',
        ]);

        $response->assertOk();
    }

    // ==================================================================
    // 2. İNAKTİV HESAB
    // ==================================================================

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email'     => 'inactive@example.com',
            'password'  => Hash::make('secret123'),
            'role'      => 'user',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'inactive@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_inactive_user_login_returns_generic_error(): void
    {
        User::factory()->create([
            'email'     => 'inactive@example.com',
            'password'  => Hash::make('secret123'),
            'role'      => 'user',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'inactive@example.com',
            'password' => 'secret123',
        ]);

        // Should return generic "credentials" error, not "account disabled"
        // to prevent account status enumeration.
        $message = $response->json('errors.email.0');
        $this->assertStringContainsString('credentials', $message);
    }

    // ==================================================================
    // 3. GENERIC ERROR MESSAGES
    // ==================================================================

    public function test_nonexistent_email_returns_generic_error(): void
    {
        $response = $this->postJson('/api/login', [
            'email'    => 'nobody@example.com',
            'password' => 'anything',
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('credentials', $response->json('errors.email.0'));
    }

    public function test_wrong_password_returns_generic_error(): void
    {
        User::factory()->create([
            'email'     => 'api-test@example.com',
            'password'  => Hash::make('secret'),
            'role'      => 'user',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'api-test@example.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('credentials', $response->json('errors.email.0'));
    }

    // ==================================================================
    // 4. VALIDATION
    // ==================================================================

    public function test_email_is_required(): void
    {
        $response = $this->postJson('/api/login', [
            'password' => 'anything',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_invalid_email_format_is_rejected(): void
    {
        $response = $this->postJson('/api/login', [
            'email'    => 'not-an-email',
            'password' => 'anything',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_password_is_required(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'api-test@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class TokenExpirationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sanctum_expiration_is_configured(): void
    {
        $expiration = config('sanctum.expiration');

        $this->assertNotNull($expiration, 'Sanctum expiration must not be null');
        $this->assertIsInt($expiration);
        $this->assertGreaterThan(0, $expiration);
    }

    public function test_sanctum_expiration_defaults_to_30_days(): void
    {
        // 60 min * 24 hours * 30 days = 43200
        $this->assertEquals(43200, config('sanctum.expiration'));
    }

    public function test_login_still_works_with_expiration_configured(): void
    {
        $user = User::factory()->create([
            'email' => 'api@test.com',
            'password' => Hash::make('secret123'),
            'role' => 'user',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'api@test.com',
            'password' => 'secret123',
            'device_name' => 'phpunit',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'user']);

        $this->assertNotEmpty($response->json('access_token'));
    }

    public function test_fresh_token_is_accepted(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('fresh-test');
        $plainText = $token->plainTextToken;

        $response = $this->withToken($plainText)->getJson('/api/user');

        // Fresh token is NOT rejected as expired.
        // It gets 400 because X-Garage-Id header is missing.
        // Getting 401 would mean the token was rejected.
        $this->assertNotEquals(
            401,
            $response->status(),
            'Fresh token must not be rejected as expired'
        );
    }

    public function test_token_expires_after_configured_duration(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('expiry-test');
        $plainText = $token->plainTextToken;

        // `created_at` is not in PersonalAccessToken::$fillable, so `update()`
        // silently ignores it. Use DB directly to bypass mass assignment.
        DB::table('personal_access_tokens')
            ->where('id', $token->accessToken->id)
            ->update(['created_at' => now()->subDays(31)]);

        // Sanity check that the timestamp was actually changed
        $fresh = PersonalAccessToken::find($token->accessToken->id);
        $this->assertTrue(
            $fresh->created_at->isBefore(now()->subDays(30)),
            'Test setup failed: created_at was not updated in database'
        );

        $response = $this->withToken($plainText)->getJson('/api/user');

        $response->assertStatus(401, 'Expired token must be rejected with 401');
    }

    public function test_token_within_expiration_window_is_accepted(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('within-window');
        $plainText = $token->plainTextToken;

        // Simulate token created 29 days ago (within 30-day limit)
        DB::table('personal_access_tokens')
            ->where('id', $token->accessToken->id)
            ->update(['created_at' => now()->subDays(29)]);

        $response = $this->withToken($plainText)->getJson('/api/user');

        // Still valid → 400 (missing header), not 401
        $this->assertNotEquals(
            401,
            $response->status(),
            'Token within expiration window must not be rejected'
        );
    }

    public function test_manually_expired_token_is_rejected(): void
    {
        // Sanctum allows setting expires_at explicitly — validate that path too.
        $user = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('explicit-expiry');
        $token->accessToken->update(['expires_at' => now()->subDay()]);
        $plainText = $token->plainTextToken;

        $response = $this->withToken($plainText)->getJson('/api/user');

        $response->assertStatus(401);
    }
}

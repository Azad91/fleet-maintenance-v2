<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_endpoint_is_reachable_without_garage_header(): void
    {
        $user = User::factory()->create([
            'email'    => 'api@test.com',
            'password' => Hash::make('secret123'),
            'role'     => 'user',
        ]);

        $response = $this->postJson('/api/login', [
            'email'       => 'api@test.com',
            'password'    => 'secret123',
            'device_name' => 'phpunit',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'user']);

        $this->assertEquals('Bearer', $response->json('token_type'));
        $this->assertNotEmpty($response->json('access_token'));
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email'    => 'api@test.com',
            'password' => Hash::make('secret123'),
            'role'     => 'user',
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'api@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_protected_route_still_requires_garage_header(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/buses');

        // X-Garage-Id yoxdur → 400 (EnsureApiGarageContext işləyir)
        $response->assertStatus(400)
            ->assertJson(['error' => __('messages.api.garage_header_required')]);
    }
}

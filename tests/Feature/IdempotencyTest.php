<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Garage;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_request_with_same_idempotency_key_returns_cached_response()
    {
        $company = Company::factory()->create();
        $garage = Garage::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['role' => 'super_admin']);

        $sessionData = [
            'current_garage_id' => $garage->id,
            'current_company_id' => $company->id,
        ];

        $payload = [
            'dqn' => '99-XX-999',
        ];

        // 1. İlk sorğu
        $response1 = $this->actingAs($user)
            ->withSession($sessionData)
            ->withHeaders(['X-Idempotency-Key' => 'test-key-123'])
            ->post(route('buses.store'), $payload);

        $response1->assertRedirect(route('buses.index'));

        // 2. İkinci (Təkrar) sorğu - Keşdən qayıtmalıdır
        $response2 = $this->actingAs($user)
            ->withSession($sessionData)
            ->withHeaders(['X-Idempotency-Key' => 'test-key-123'])
            ->post(route('buses.store'), $payload);

        $response2->assertRedirect(route('buses.index'));
        $this->assertEquals($response1->headers->get('Location'), $response2->headers->get('Location'));
    }
}

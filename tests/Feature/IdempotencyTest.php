<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    public function test_duplicate_request_with_same_idempotency_key_returns_cached_response(): void
    {
        $company = Company::factory()->create();
        $garage = Garage::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['role' => 'admin']);
        $user->garages()->attach($garage->id, ['role' => 'admin', 'is_active' => true]);

        GarageContext::set($garage->id, $company->id);

        $idempotencyKey = 'unique-key-12345';

        $payload = [
            'dqn' => '99-ZZ-999',
            'route_number' => '100',
            '_idempotency_key' => $idempotencyKey,
        ];

        $sessionData = [
            'current_garage_id' => $garage->id,
            'current_company_id' => $company->id,
        ];

        // 1-ci sorğu
        $response1 = $this->actingAs($user)
            ->withSession($sessionData)
            ->post('/buses', $payload, [
                'X-Idempotency-Key' => $idempotencyKey,
            ]);

        $response1->assertRedirect('/api/buses');

        // 2-ci eyni açarla göndərilən sorğu (eyni cavab keşdən qayıtmalıdır)
        $response2 = $this->actingAs($user)
            ->withSession($sessionData)
            ->post('/buses', $payload, [
                'X-Idempotency-Key' => $idempotencyKey,
            ]);

        $response2->assertRedirect('/api/buses');
        $this->assertSame($response1->getStatusCode(), $response2->getStatusCode());
    }
}

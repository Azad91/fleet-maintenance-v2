<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_request_with_same_idempotency_key_returns_cached_response()
    {
        $company = Company::factory()->create();
        $garage = Garage::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['role' => 'admin']);
        $user->garages()->attach($garage, ['role' => 'admin', 'is_active' => true]);

        $key = 'test-uuid-' . uniqid();

        // 1-ci sorğu
        $response1 = $this->actingAs($user)
            ->withSession([
                'current_garage_id' => $garage->id,
                'current_company_id' => $company->id,
            ])
            ->withHeaders(['X-Idempotency-Key' => $key])
            ->post(route('buses.store'), [
                'dqn' => '99-ZZ-999',
                'route_number' => '100',
            ]);

        $response1->assertRedirect(route('buses.index'));

        // 2-ci eyni açarla göndərilən sorğu (təkrar icra olunmamalıdır)
        $response2 = $this->actingAs($user)
            ->withSession([
                'current_garage_id' => $garage->id,
                'current_company_id' => $company->id,
            ])
            ->withHeaders(['X-Idempotency-Key' => $key])
            ->post(route('buses.store'), [
                'dqn' => '99-ZZ-999',
                'route_number' => '100',
            ]);

        $response2->assertRedirect(route('buses.index'));
        $this->assertEquals('HIT-IDEMPOTENT', $response2->headers->get('X-Cache-Lookup'));
    }
}

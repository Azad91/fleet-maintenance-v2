<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GarageDataAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage = Garage::factory()->create(['company_id' => $this->company->id]);
        GarageContext::set($this->garage->id, $this->company->id);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['role' => 'user']);
        $user->garages()->attach($this->garage->id, ['role' => $role, 'is_active' => true]);

        return $user;
    }

    public function test_daily_status_worker_cannot_access_garage_data_endpoints(): void
    {
        $user = $this->userWithRole('daily_status_worker');

        $response = $this->actingAs($user)
            ->withSession([
                'current_garage_id'  => $this->garage->id,
                'current_company_id' => $this->company->id,
            ])
            ->get('/get-detal-by-kod/D-001');

        $response->assertStatus(403);
    }

    public function test_daily_km_worker_cannot_access_garage_data_endpoints(): void
    {
        $user = $this->userWithRole('daily_km_worker');

        $response = $this->actingAs($user)
            ->withSession([
                'current_garage_id'  => $this->garage->id,
                'current_company_id' => $this->company->id,
            ])
            ->get('/get-driver-by-kod/DRV-001');

        $response->assertStatus(403);
    }

    public function test_warehouse_manager_can_access_detal_by_kod(): void
    {
        $user = $this->userWithRole('warehouse_manager');

        $response = $this->actingAs($user)
            ->withSession([
                'current_garage_id'  => $this->garage->id,
                'current_company_id' => $this->company->id,
            ])
            ->get('/get-detal-by-kod/D-001');

        $this->assertContains($response->status(), [200, 404]);
    }

    public function test_warehouse_worker_can_access_detal_by_kod(): void
    {
        $user = $this->userWithRole('warehouse_worker');

        $response = $this->actingAs($user)
            ->withSession([
                'current_garage_id'  => $this->garage->id,
                'current_company_id' => $this->company->id,
            ])
            ->get('/get-detal-by-kod/D-001');

        $this->assertContains($response->status(), [200, 404]);
    }

    public function test_complaint_manager_can_access_all_garage_data_endpoints(): void
    {
        $user = $this->userWithRole('complaint_manager');

        foreach ([
            '/get-bus-id-by-xett/999',
            '/get-bus-km-by-id/1',
            '/get-detal-by-kod/D-001',
            '/get-driver-by-kod/DRV-001',
            '/get-motor-oil-services/1',
        ] as $url) {
            $response = $this->actingAs($user)
                ->withSession([
                    'current_garage_id'  => $this->garage->id,
                    'current_company_id' => $this->company->id,
                ])
                ->get($url);

            $this->assertContains(
                $response->status(),
                [200, 404],
                "Failed for URL: {$url} (got status {$response->status()})"
            );
        }
    }

    public function test_complaint_worker_can_access_all_garage_data_endpoints(): void
    {
        $user = $this->userWithRole('complaint_worker');

        foreach ([
            '/get-bus-id-by-xett/999',
            '/get-bus-km-by-id/1',
            '/get-detal-by-kod/D-001',
            '/get-driver-by-kod/DRV-001',
            '/get-motor-oil-services/1',
        ] as $url) {
            $response = $this->actingAs($user)
                ->withSession([
                    'current_garage_id'  => $this->garage->id,
                    'current_company_id' => $this->company->id,
                ])
                ->get($url);

            $this->assertContains(
                $response->status(),
                [200, 404],
                "Failed for URL: {$url} (got status {$response->status()})"
            );
        }
    }
}

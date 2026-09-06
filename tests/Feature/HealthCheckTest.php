<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_endpoint_returns_healthy_status()
    {
        $response = $this->get('/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'services' => [
                'database',
                'cache',
                'storage',
            ],
        ]);
        $response->assertJson([
            'status' => 'healthy',
            'services' => [
                'database' => ['status' => 'up'],
                'cache' => ['status' => 'up'],
                'storage' => ['status' => 'up'],
            ],
        ]);
    }
}


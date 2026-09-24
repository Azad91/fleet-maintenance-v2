<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_endpoint_returns_healthy_status(): void
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
                'queue',
                'disk',
            ],
        ]);
        $response->assertJson([
            'status' => 'healthy',
            'services' => [
                'database' => ['status' => 'up'],
                'cache' => ['status' => 'up'],
                'storage' => ['status' => 'up'],
                'queue' => ['status' => 'up'],
            ],
        ]);
    }

    public function test_queue_service_reports_configured_driver(): void
    {
        $response = $this->get('/health');

        $response->assertOk();
        $this->assertSame(
            config('queue.default'),
            $response->json('services.queue.driver')
        );
    }

    public function test_disk_service_reports_status(): void
    {
        $response = $this->get('/health');

        $response->assertOk();

        $diskStatus = $response->json('services.disk.status');

        // In CI/test environments, disk_free_space() is normally
        // available, so we expect `up`. On hardened builds it may
        // be `skipped`, which is also acceptable.
        $this->assertContains($diskStatus, ['up', 'skipped']);
    }

    public function test_health_endpoint_is_public(): void
    {
        // Health checks are typically consumed by external monitoring
        // tools that do not authenticate. This guard ensures that a
        // future refactor does not accidentally put /health behind
        // the auth middleware.
        $this->get('/health')->assertOk();
    }
}

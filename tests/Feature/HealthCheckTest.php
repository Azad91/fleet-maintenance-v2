<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Each test starts with no backup marker so the default
        // "skipped" branch is exercised unless a test overrides it.
        $marker = config('health.backup_marker_path');

        if ($marker && file_exists($marker)) {
            @unlink($marker);
        }
    }

    protected function tearDown(): void
    {
        $marker = config('health.backup_marker_path');

        if ($marker && file_exists($marker)) {
            @unlink($marker);
        }

        parent::tearDown();
    }

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
                'backup',
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

        $this->assertContains($diskStatus, ['up', 'skipped']);
    }

    public function test_health_endpoint_is_public(): void
    {
        $this->get('/health')->assertOk();
    }

    // ==================================================================
    // Backup freshness
    // ==================================================================

    public function test_backup_check_is_skipped_when_marker_missing(): void
    {
        $response = $this->get('/health');

        $response->assertOk();
        $this->assertSame('skipped', $response->json('services.backup.status'));
        $this->assertSame('marker not found', $response->json('services.backup.reason'));
    }

    public function test_backup_check_is_up_when_marker_is_fresh(): void
    {
        $marker = config('health.backup_marker_path');
        @mkdir(dirname($marker), 0755, true);
        touch($marker);

        $response = $this->get('/health');

        $response->assertOk();
        $this->assertSame('up', $response->json('services.backup.status'));
        $this->assertLessThan(1, $response->json('services.backup.age_hours'));
    }

    public function test_backup_check_is_down_when_marker_is_stale(): void
    {
        $marker = config('health.backup_marker_path');
        @mkdir(dirname($marker), 0755, true);
        touch($marker);

        // Rewind the mtime by 30 hours — beyond the default 25h window.
        touch($marker, time() - (30 * 3600));

        $response = $this->get('/health');

        $response->assertStatus(503);
        $response->assertJson([
            'status' => 'unhealthy',
            'services' => [
                'backup' => ['status' => 'down'],
            ],
        ]);
    }

    public function test_backup_check_is_skipped_when_disabled(): void
    {
        config(['health.backup_max_age_hours' => 0]);

        $marker = config('health.backup_marker_path');
        @mkdir(dirname($marker), 0755, true);
        touch($marker, time() - (100 * 3600)); // very stale

        $response = $this->get('/health');

        $response->assertOk();
        $this->assertSame('skipped', $response->json('services.backup.status'));
        $this->assertSame('check disabled', $response->json('services.backup.reason'));
    }
}

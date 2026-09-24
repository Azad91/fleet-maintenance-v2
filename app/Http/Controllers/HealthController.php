<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $status = [
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'services' => [],
        ];

        $isHealthy = true;

        // 1. Database
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $status['services']['database'] = ['status' => 'up'];
        } catch (Throwable $e) {
            $isHealthy = false;
            Log::error('HealthCheck DB Error: '.$e->getMessage());
            $status['services']['database'] = ['status' => 'down', 'error' => 'Database connection failed.'];
        }

        // 2. Cache
        try {
            Cache::put('_health_check', 'ok', 10);
            $cacheVal = Cache::get('_health_check');
            Cache::forget('_health_check');

            if ($cacheVal === 'ok') {
                $status['services']['cache'] = ['status' => 'up'];
            } else {
                throw new \Exception('Cache read verification failed');
            }
        } catch (Throwable $e) {
            $isHealthy = false;
            Log::error('HealthCheck Cache Error: '.$e->getMessage());
            $status['services']['cache'] = ['status' => 'down', 'error' => 'Cache service unavailable.'];
        }

        // 3. Storage
        try {
            Storage::disk('local')->put('_health_check.txt', 'ok');
            $storageVal = Storage::disk('local')->get('_health_check.txt');
            Storage::disk('local')->delete('_health_check.txt');

            if (trim($storageVal) === 'ok') {
                $status['services']['storage'] = ['status' => 'up'];
            } else {
                throw new \Exception('Storage read verification failed');
            }
        } catch (Throwable $e) {
            $isHealthy = false;
            Log::error('HealthCheck Storage Error: '.$e->getMessage());
            $status['services']['storage'] = ['status' => 'down', 'error' => 'Storage service unavailable.'];
        }

        // 4. Queue
        //
        // Reports the configured driver and — for database-backed
        // queues — verifies the `jobs` table is actually reachable.
        // A reachable DB (step 1) does not imply the jobs table
        // exists: a fresh deployment that skipped migrations would
        // pass the DB check but fail every queued job silently.
        try {
            $queueDriver = (string) config('queue.default', 'sync');

            if ($queueDriver === 'database') {
                DB::table('jobs')->limit(1)->count();
            }

            $status['services']['queue'] = [
                'status' => 'up',
                'driver' => $queueDriver,
            ];
        } catch (Throwable $e) {
            $isHealthy = false;
            Log::error('HealthCheck Queue Error: '.$e->getMessage());
            $status['services']['queue'] = [
                'status' => 'down',
                'error' => 'Queue service unavailable.',
            ];
        }

        // 5. Disk space
        //
        // disk_free_space() and disk_total_space() are disabled on
        // some hardened PHP builds (open_basedir, disable_functions).
        // When that is the case we report `skipped` rather than
        // `down` — the check is informational, not a hard signal.
        try {
            if (! function_exists('disk_free_space') || ! function_exists('disk_total_space')) {
                $status['services']['disk'] = [
                    'status' => 'skipped',
                    'reason' => 'disk_free_space() disabled',
                ];
            } else {
                $path = storage_path();
                $freeBytes = @disk_free_space($path);
                $totalBytes = @disk_total_space($path);

                if ($freeBytes === false || $totalBytes === false || $totalBytes <= 0) {
                    $status['services']['disk'] = [
                        'status' => 'skipped',
                        'reason' => 'Unable to read disk stats',
                    ];
                } else {
                    $freePercent = ($freeBytes / $totalBytes) * 100;
                    $criticalPercent = (float) config('health.disk_critical_percent', 5);

                    if ($freePercent < $criticalPercent) {
                        $isHealthy = false;

                        $status['services']['disk'] = [
                            'status' => 'down',
                            'free_percent' => round($freePercent, 1),
                            'error' => 'Low disk space.',
                        ];

                        Log::error('HealthCheck Disk Error: low disk space', [
                            'free_percent' => round($freePercent, 1),
                            'threshold' => $criticalPercent,
                            'path' => $path,
                        ]);
                    } else {
                        $status['services']['disk'] = [
                            'status' => 'up',
                            'free_percent' => round($freePercent, 1),
                        ];
                    }
                }
            }
        } catch (Throwable $e) {
            $isHealthy = false;
            Log::error('HealthCheck Disk Error: '.$e->getMessage());
            $status['services']['disk'] = ['status' => 'down', 'error' => 'Disk check failed.'];
        }
        // 6. Backup freshness
        //
        // A stale backup is a data-loss risk that no other probe
        // catches — the app can be perfectly healthy while its last
        // successful backup is three weeks old. The backup sidecar
        // touches a marker file after every successful dump; we alert
        // when that marker is older than the configured window.
        //
        // Skipped gracefully when the marker does not exist: local
        // development has no backup sidecar, and a fresh production
        // deployment has not yet taken its first backup.
        try {
            $maxAgeHours = (int) config('health.backup_max_age_hours', 25);
            $markerPath = (string) config('health.backup_marker_path');

            if ($maxAgeHours <= 0) {
                $status['services']['backup'] = [
                    'status' => 'skipped',
                    'reason' => 'check disabled',
                ];
            } elseif ($markerPath === '' || ! file_exists($markerPath)) {
                $status['services']['backup'] = [
                    'status' => 'skipped',
                    'reason' => 'marker not found',
                ];
            } else {
                $ageHours = (time() - filemtime($markerPath)) / 3600;

                if ($ageHours > $maxAgeHours) {
                    $isHealthy = false;

                    $status['services']['backup'] = [
                        'status' => 'down',
                        'age_hours' => round($ageHours, 1),
                        'error' => 'Backup is stale.',
                    ];

                    Log::error('HealthCheck Backup Error: stale backup', [
                        'age_hours' => round($ageHours, 1),
                        'threshold_hours' => $maxAgeHours,
                        'marker' => $markerPath,
                    ]);
                } else {
                    $status['services']['backup'] = [
                        'status' => 'up',
                        'age_hours' => round($ageHours, 1),
                    ];
                }
            }
        } catch (Throwable $e) {
            // Never fail health on a backup-probe bug. Log and move on.
            Log::warning('HealthCheck Backup Error: '.$e->getMessage());
            $status['services']['backup'] = [
                'status' => 'skipped',
                'reason' => 'probe error',
            ];
        }

        $statusCode = $isHealthy ? 200 : 503;
        $status['status'] = $isHealthy ? 'healthy' : 'unhealthy';

        return response()->json($status, $statusCode);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

        // 1. Database Check
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $status['services']['database'] = ['status' => 'up'];
        } catch (Throwable $e) {
            $isHealthy = false;
            $status['services']['database'] = ['status' => 'down', 'error' => $e->getMessage()];
        }

        // 2. Cache Check
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
            $status['services']['cache'] = ['status' => 'down', 'error' => $e->getMessage()];
        }

        // 3. Storage Check
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
            $status['services']['storage'] = ['status' => 'down', 'error' => $e->getMessage()];
        }

        $statusCode = $isHealthy ? 200 : 503;
        $status['status'] = $isHealthy ? 'healthy' : 'unhealthy';

        return response()->json($status, $statusCode);
    }
}

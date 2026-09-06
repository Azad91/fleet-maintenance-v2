<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Yalnız state dəyişdirən sorğular üçün (POST, PUT, PATCH, DELETE)
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $idempotencyKey = $request->header('X-Idempotency-Key')
            ?? $request->header('Idempotency-Key')
            ?? $request->input('_idempotency_key');

        if (!$idempotencyKey) {
            return $next($request);
        }

        $userId = $request->user()?->id ?? 'guest';
        $cacheKey = "idempotency:{$userId}:" . md5((string) $idempotencyKey);

        $cachedResponse = Cache::get($cacheKey);
        if ($cachedResponse) {
            return response(
                $cachedResponse['content'],
                $cachedResponse['status'],
                array_merge($cachedResponse['headers'], ['X-Cache-Lookup' => 'HIT-IDEMPOTENT'])
            );
        }

        $response = $next($request);

        // Uğurlu cavabları (2xx, 3xx) keşləyirik
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 400) {
            Cache::put($cacheKey, [
                'status' => $response->getStatusCode(),
                'headers' => $response->headers->all(),
                'content' => $response->getContent(),
            ], now()->addHours(12));
        }

        return $response;
    }
}


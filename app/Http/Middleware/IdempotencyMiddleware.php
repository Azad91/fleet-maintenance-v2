<?php

namespace App\Http\Middleware;

use App\Services\GarageContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    private const CACHE_TTL_HOURS = 12;
    private const LOCK_SECONDS = 30;
    private const MAX_KEY_LENGTH = 255;

    private const STRIPPED_HEADERS = [
        'set-cookie',
        'cookie',
        'date',
        'content-length',
        'transfer-encoding',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $idempotencyKey = $this->extractIdempotencyKey($request);

        if ($idempotencyKey === null) {
            return $next($request);
        }

        $cacheKey = $this->buildCacheKey($request, $idempotencyKey);
        $lockKey  = $cacheKey . ':lock';

        // 1. CACHE HIT
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && isset($cached['status'])) {
            return $this->buildCachedResponse($cached);
        }

        // 2. ATOMİK LOCK
        $lock = Cache::lock($lockKey, self::LOCK_SECONDS);

        try {
            $lock->block(self::LOCK_SECONDS);

            $cached = Cache::get($cacheKey);
            if (is_array($cached) && isset($cached['status'])) {
                return $this->buildCachedResponse($cached);
            }

            // 3. REAL SORĞU
            $response = $next($request);

            // 4. UĞURLU CAVABI KEŞLƏ
            $status = $response->getStatusCode();
            $hasValidationErrors = $this->responseHasValidationErrors($response);

            if ($status >= 200 && $status < 400 && ! $hasValidationErrors) {
                Cache::put($cacheKey, [
                    'status'  => $status,
                    'headers' => $this->filterHeaders($response->headers->all()),
                    'content' => $response->getContent(),
                ], now()->addHours(self::CACHE_TTL_HOURS));
            }

            return $response;
        } finally {
            optional($lock)->release();
        }
    }

    private function extractIdempotencyKey(Request $request): ?string
    {
        $key = $request->header('X-Idempotency-Key')
            ?? $request->header('Idempotency-Key')
            ?? $request->input('_idempotency_key');

        if (! is_string($key) || $key === '') {
            return null;
        }

        $key = trim($key);

        if (mb_strlen($key) > self::MAX_KEY_LENGTH) {
            return null;
        }

        return $key;
    }

    private function buildCacheKey(Request $request, string $idempotencyKey): string
    {
        $userId   = $request->user()?->getKey() ?? 'guest';
        $garageId = GarageContext::getGarageId() ?? 'none';
        $method   = $request->method();
        $path     = $request->path();
        $query    = $request->getQueryString() ?? '';
        $keyHash  = hash('sha256', $idempotencyKey);

        return "idempotency:{$userId}:{$garageId}:{$method}:{$path}:{$query}:{$keyHash}";
    }

    private function buildCachedResponse(array $cached): Response
    {
        return response(
            $cached['content'] ?? '',
            $cached['status'] ?? 200,
            array_merge(
                $cached['headers'] ?? [],
                ['X-Cache-Lookup' => 'HIT-IDEMPOTENT']
            )
        );
    }

    /**
     * @param  array<string, array<int, string>>  $headers
     * @return array<string, array<int, string>>
     */
    private function filterHeaders(array $headers): array
    {
        $filtered = [];

        foreach ($headers as $name => $values) {
            if (in_array(strtolower($name), self::STRIPPED_HEADERS, true)) {
                continue;
            }
            $filtered[$name] = $values;
        }

        return $filtered;
    }

    /**
     * Response-da validation error olub-olmadığını yoxlayır.
     *
     * `RedirectResponse::getSession()` — bu, controller-in `withErrors()`
     * ilə yazdığı session instansiyasının EYNİSİDİR. Request-dən oxumaq
     * əvəzinə response-dan oxumaq 100% etibarlıdır.
     */
    private function responseHasValidationErrors(Response $response): bool
    {
        if (! method_exists($response, 'getSession')) {
            return false;
        }

        try {
            $session = $response->getSession();

            if (! $session) {
                return false;
            }

            $errors = $session->get('errors');

            return $this->errorsBagHasContent($errors);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Error bag-in boş olub-olmadığını universal şəkildə yoxlayır.
     */
    private function errorsBagHasContent(mixed $errors): bool
    {
        if ($errors === null) {
            return false;
        }

        if (is_object($errors)) {
            if (method_exists($errors, 'any')) {
                return (bool) $errors->any();
            }
            if (method_exists($errors, 'isNotEmpty')) {
                return (bool) $errors->isNotEmpty();
            }
            if (method_exists($errors, 'isEmpty')) {
                return ! $errors->isEmpty();
            }
            if (method_exists($errors, 'count')) {
                return $errors->count() > 0;
            }
        }

        if (is_array($errors)) {
            return count($errors) > 0;
        }

        return false;
    }
}

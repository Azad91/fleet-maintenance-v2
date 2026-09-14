<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures every request carries a valid, unique X-Request-ID that is
 * propagated to logs (via Context) and back to the client.
 *
 * The incoming header is accepted ONLY if it is a valid UUID. This
 * prevents log injection and correlation poisoning — an attacker
 * cannot send "X-Request-ID: anything" and end up with that value
 * showing up in every log line for the request.
 */
class RequestIdMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Client-dən gələn header-i yalnız UUID formatında qəbul et.
        // Əks halda öz UUID-mizi generasiya et.
        $incoming = $request->header('X-Request-ID');

        $requestId = (is_string($incoming) && Str::isUuid($incoming))
            ? $incoming
            : Str::uuid()->toString();

        // Laravel Context — bütün log qeydlərinə avtomatik düşür.
        Context::add('request_id', $requestId);

        // Sorğunun header-inə yaz — controller-lər istifadə edə bilsin.
        $request->headers->set('X-Request-ID', $requestId);

        $response = $next($request);

        // Cavabın header-inə də əlavə et — frontend/monitoring üçün.
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}

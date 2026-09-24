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
        // Only accept an incoming header if it is a valid UUID.
        // Otherwise generate our own UUID.
        $incoming = $request->header('X-Request-ID');

        $requestId = (is_string($incoming) && Str::isUuid($incoming))
            ? $incoming
            : Str::uuid()->toString();

        // Laravel Context — this value is automatically attached to
        // every log entry produced during the request.
        Context::add('request_id', $requestId);

        // Write it back onto the request headers so controllers can
        // access it if needed.
        $request->headers->set('X-Request-ID', $requestId);

        $response = $next($request);

        // Add it to the response headers too — for frontend and
        // monitoring tools.
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}

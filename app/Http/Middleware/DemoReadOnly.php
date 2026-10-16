<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoReadOnly
{
    /**
     * Handle an incoming request.
     *
     * In demo mode:
     * - Check if the demo account has expired.
     * - Block any mutating HTTP request (POST, PUT, PATCH, DELETE) except logout.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isDemo()) {
            if ($user->isDemoExpired()) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => __('messages.demo.expired'),
                ]);
            }

            if (! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
                if ($request->routeIs('logout')) {
                    return $next($request);
                }

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => __('messages.demo.read_only_mode'),
                    ], 403);
                }

                return back()->with('error', __('messages.demo.read_only_mode'));
            }
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures that an authenticated user has an active account.
 *
 * Two scenarios are covered:
 *
 *   1. WEB — the user was deactivated by an admin while their session
 *      was still alive. The session cookie keeps working even after
 *      `users.is_active = false`, because Laravel's session guard does
 *      not re-validate the user on every request. This middleware
 *      closes that gap: on the next request the user is logged out,
 *      the session is invalidated, and they are redirected to /login.
 *
 *   2. API — the user holds a valid Sanctum token but the account has
 *      since been deactivated. The token is revoked and the response
 *      is a 401 JSON error, matching what a fresh (rejected) login
 *      would return.
 *
 * Runs as part of the `web` middleware group AFTER StartSession, so
 * `auth()->check()` is available. On API routes it must be applied
 * after `auth:sanctum`.
 */
class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user() ?? Auth::user();

        // No authenticated user → nothing to check.
        if (! $user) {
            return $next($request);
        }

        // Active users pass through unchanged.
        if ($user->is_active) {
            return $next($request);
        }

        // ─────────────────────────────────────────────────────────
        // API: revoke the current token and return JSON 401.
        // ─────────────────────────────────────────────────────────
        if ($request->expectsJson()) {
            try {
                $user->currentAccessToken()?->delete();
            } catch (\Throwable $e) {
                // Never let a token-revoke failure mask the 401.
            }

            Log::warning('Inactive user blocked (API)', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'request_id' => \Illuminate\Support\Facades\Context::get('request_id'),
            ]);

            return response()->json([
                'message' => __('auth.inactive'),
            ], 401);
        }

        // ─────────────────────────────────────────────────────────
        // Web: log out, invalidate the session, redirect to login.
        // ─────────────────────────────────────────────────────────
        Log::warning('Inactive user session invalidated (web)', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'request_id' => \Illuminate\Support\Facades\Context::get('request_id'),
        ]);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('error', __('auth.inactive'));
    }
}

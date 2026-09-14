<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures that a SuperAdmin who has not yet completed MFA setup is
 * redirected to the setup wizard before reaching any /super-admin/*
 * screen (except the setup wizard itself and logout).
 *
 * This is a hard gate: a SuperAdmin account with MFA not configured
 * cannot use the platform until it completes the flow.
 */
class EnsureTwoFactorVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isSuperAdmin()) {
            return $next($request);
        }

        // Allow setup flow itself and logout.
        if ($request->routeIs('super-admin.security.2fa.*')
            || $request->routeIs('logout')) {
            return $next($request);
        }

        if ($user->requiresTwoFactorSetup()) {
            return redirect()
                ->route('super-admin.security.2fa.setup')
                ->with('warning', __('messages.two_factor.setup_required'));
        }

        return $next($request);
    }
}

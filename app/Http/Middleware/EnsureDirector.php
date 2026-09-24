<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts access to /director/* routes to users holding an active
 * Company Director role (via the company_user pivot).
 *
 * Super Admins are allowed through so they can inspect director
 * dashboards while debugging. Every other authenticated user gets a
 * 403 — matching the fail-closed behaviour used elsewhere.
 *
 * Why middleware instead of a controller check:
 *   - The role check runs BEFORE the controller is instantiated,
 *     so no page-specific query is wasted on an unauthorized user.
 *   - Reuses the cached isDirector() result on the User model,
 *     so the per-request cost is one EXISTS query at most.
 */
class EnsureDirector
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Super Admin bypasses — matches the pattern used by
        // RoleMiddleware and every policy.
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (! $user->isDirector()) {
            abort(403, __('messages.flash.permission_denied'));
        }

        return $next($request);
    }
}

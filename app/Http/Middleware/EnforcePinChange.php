<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces users to change their default PIN before accessing
 * any other page.
 *
 * Rules:
 *   - Skip for guests, super admins, and users without a PIN.
 *   - Allow access to PIN change routes and logout.
 */
class EnforcePinChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user
            || $user->isSuperAdmin()
            || ! $user->pin
            || ! $user->pin_is_default) {
            return $next($request);
        }

        // Allow the PIN change flow itself and logout.
        if ($request->routeIs('pin.change.*') || $request->routeIs('logout')) {
            return $next($request);
        }

        return redirect()->route('pin.change.show');
    }
}

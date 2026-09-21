<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces users to change their default PIN before accessing any
 * other page.
 *
 * Rules:
 *   - Skip for guests, super admins, and users without a PIN.
 *   - Allow access to a small whitelist of routes so the forced
 *     PIN-change flow cannot trap the user:
 *       * the change flow itself
 *       * logout
 *       * email verification screens
 *       * password re-confirmation
 *       * 2FA challenge screens
 *
 * Note: the PIN change requirement is on the user, not the session,
 * so it survives across requests until the user sets a new PIN.
 */
class EnforcePinChange
{
    /**
     * Route name patterns that must remain reachable while a PIN
     * change is pending. Trailing ".*" is treated as a prefix match
     * by Laravel's routeIs() helper.
     *
     * @var array<int, string>
     */
    private const ALLOWED_ROUTE_PATTERNS = [
        'pin.change.*',        // The change flow itself
        'logout',              // Always available
        'password.confirm',    // Password re-entry for sensitive actions
        'password.confirm.store',
        'verification.notice', // Email verification screens
        'verification.verify',
        'verification.send',
        'two-factor.*',        // TOTP challenge screens
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user
            || $user->isSuperAdmin()
            || ! $user->pin
            || ! $user->pin_is_default) {
            return $next($request);
        }

        foreach (self::ALLOWED_ROUTE_PATTERNS as $pattern) {
            if ($request->routeIs($pattern)) {
                return $next($request);
            }
        }

        return redirect()->route('pin.change.show');
    }
}

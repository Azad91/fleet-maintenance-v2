<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\GarageContext;
use App\Services\PostLoginRedirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming email/password authentication request.
     *
     * SuperAdmin handling is a two-step flow:
     *
     *   1. If MFA is already configured, the password step stores the
     *      user id in the session and redirects to the TOTP challenge.
     *      The user is only logged in after the code is verified.
     *
     *   2. If MFA is NOT yet configured (a freshly seeded account, or
     *      one whose secret was reset), the user is logged in immediately
     *      and sent to the setup wizard. Without this branch the flow
     *      deadlocks: the challenge needs a secret, the setup screen
     *      needs an authenticated session.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        // ─── SuperAdmin branch ───
        if ($user->isSuperAdmin()) {
            $remember = (bool) $request->boolean('remember');

            // First-time SuperAdmin: no confirmed MFA secret yet.
            // The user is already authenticated by LoginRequest::authenticate()
            // (Auth::attempt + remember flag), so we only need to redirect.
            if (! $user->hasTwoFactorEnabled()) {
                return redirect()
                    ->route('super-admin.security.2fa.setup')
                    ->with('warning', __('messages.two_factor.setup_required'));
            }

            // MFA is configured — defer login until the code is verified.
            // Log out the session started by authenticate() so the user is
            // NOT authenticated while we wait for the TOTP code.
            Auth::guard('web')->logout();

            $request->session()->put('two_factor.user_id', $user->id);
            $request->session()->put('two_factor.remember', $remember);

            return redirect()->route('two-factor.challenge');
        }

        return PostLoginRedirector::redirect($user);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        GarageContext::clear();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', __('messages.flash.auth_logged_out'));
    }
}

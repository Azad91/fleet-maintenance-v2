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
     * If the authenticated user is a SuperAdmin, the login is not
     * completed here — instead the user id is stored in the session
     * and the request is redirected to the two-factor challenge.
     * The user is only logged in after the TOTP code is verified.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        // SuperAdmin: defer login until MFA is verified.
        if ($user->isSuperAdmin()) {
            $remember = (bool) $request->boolean('remember');

            // Log out for now — the challenge controller will log back in.
            Auth::guard('web')->logout();

            $request->session()->put('two_factor.user_id', $user->id);
            $request->session()->put('two_factor.remember', $remember);

            return redirect()->route('two-factor.challenge');
        }

        // Default PIN varsa, PIN dəyişmə səhifəsinə yönləndir.
        if ($user->pin_is_default && $user->pin) {
            return redirect()->route('pin.change.show');
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

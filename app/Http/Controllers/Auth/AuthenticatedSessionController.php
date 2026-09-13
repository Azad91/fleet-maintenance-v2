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
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Default PIN varsa, PIN dəyişmə səhifəsinə yönləndir.
        // Bu, PIN login ilə eyni davranışı təmin edir.
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

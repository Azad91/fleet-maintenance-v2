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
     * All post-login routing is delegated to PostLoginRedirector so
     * that email login and PIN login produce identical behavior:
     *
     *   - Director (company-level)      → director.dashboard
     *   - Super Admin                    → garage.selection
     *   - 1 active garage membership     → auto-select + dashboard
     *   - 0 or 2+ active memberships     → garage.selection
     *
     * Previously this method had inline routing that did not check for
     * the Director role, which sent Directors to the garage selection
     * page — breaking the company-level navigation promised in the spec.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        if (! $user) {
            // Defensive: authenticate() should have thrown on failure,
            // but we guarantee a sane fallback if it somehow returns.
            return redirect()->route('login');
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

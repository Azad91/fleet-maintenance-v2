<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PinLoginRequest;
use App\Services\PostLoginRedirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PinLoginController extends Controller
{
    /**
     * Show the login page (shared with email login).
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Authenticate via employee code + PIN.
     *
     * PIN login is only available to non-SuperAdmin users (the
     * PinLoginRequest rejects SuperAdmins), so no MFA branch is
     * needed here.
     */
    public function store(PinLoginRequest $request): RedirectResponse
    {
        $user = $request->authenticate();

        Auth::login($user);

        $request->session()->regenerate();

        // Force PIN change if the user still has the default PIN.
        if ($user->pin_is_default && $user->pin) {
            return redirect()->route('pin.change.show');
        }

        return PostLoginRedirector::redirect($user);
    }
}

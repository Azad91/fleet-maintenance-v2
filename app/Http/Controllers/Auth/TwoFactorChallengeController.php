<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Second step of the SuperAdmin login flow.
 *
 * The password step stores the authenticated user id in the session
 * under 'two_factor.user_id' and redirects here. Until the TOTP code
 * (or a recovery code) is verified, the user is NOT logged in — this
 * avoids the "authenticated but locked" state that is easy to get
 * wrong with middleware.
 */
class TwoFactorChallengeController extends Controller
{
    /**
     * Show the challenge page.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $userId = $request->session()->get('two_factor.user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    /**
     * Verify the submitted code and complete login.
     */
    public function store(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('two_factor.user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::find($userId);

        if (! $user) {
            $request->session()->forget('two_factor.user_id');

            return redirect()->route('login');
        }

        $code = trim($request->input('code'));

        // Accept either a TOTP code (6 digits) or a recovery code (X-XXXXX-XXXXX).
        $verified = false;

        if (preg_match('/^\d{6}$/', $code)) {
            $verified = $user->verifyTwoFactorCode($code);
        }

        if (! $verified && str_contains($code, '-')) {
            $verified = $user->useRecoveryCode($code);
        }

        if (! $verified) {
            return back()->withErrors([
                'code' => __('messages.two_factor.invalid_code'),
            ]);
        }

        // Code accepted — complete the login.
        Auth::login($user, remember: (bool) $request->session()->pull('two_factor.remember', false));

        $request->session()->forget('two_factor.user_id');
        $request->session()->regenerate();

        return redirect()->intended(route('super-admin.dashboard'));
    }

    /**
     * Abort the challenge and return to login.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget(['two_factor.user_id', 'two_factor.remember']);

        return redirect()->route('login');
    }
}

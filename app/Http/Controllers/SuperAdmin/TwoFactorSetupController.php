<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Support\TwoFactor\TwoFactorManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Handles MFA setup for the SuperAdmin account.
 *
 * Three-step flow:
 *   1. GET  /super-admin/security/2fa/setup
 *      → generates a pending secret, shows QR code
 *   2. POST /super-admin/security/2fa/setup/confirm
 *      → verifies the first code, marks the secret as confirmed,
 *        generates and shows recovery codes
 *   3. GET  /super-admin/security/2fa/recovery-codes
 *      → shows the codes once (from session), then forgets them
 */
class TwoFactorSetupController extends Controller
{
    public function __construct(
        protected TwoFactorManager $twoFactor
    ) {}

    /**
     * Step 1: Show QR code and first-code input.
     *
     * Generates a fresh secret if none is pending. This replaces any
     * pending secret on refresh — the user must complete the flow in
     * one sitting.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $this->ensureSuperAdmin();

        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return redirect()
                ->route('super-admin.settings.index')
                ->with('info', __('messages.two_factor.already_enabled'));
        }

        // Generate a fresh pending secret on each visit.
        $secret = $this->twoFactor->generateSecret();

        $user->forceFill(['two_factor_secret' => $secret])->save();

        return view('super-admin.security.two-factor-setup', [
            'secret' => $secret,
            'qrCodeUri' => $this->twoFactor->qrCodeDataUri($user, $secret),
        ]);
    }

    /**
     * Step 2: Confirm the first TOTP code.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        $user = $request->user();

        if (! $user->two_factor_secret) {
            return redirect()
                ->route('super-admin.security.2fa.setup')
                ->with('error', __('messages.two_factor.secret_missing'));
        }

        if (! $user->verifyTwoFactorCode($validated['code'])) {
            return back()->withErrors([
                'code' => __('messages.two_factor.invalid_code'),
            ]);
        }

        // Mark as confirmed and generate recovery codes.
        $recovery = $this->twoFactor->generateRecoveryCodes();

        $hashed = array_map(
            fn (string $plain) => Hash::make($plain),
            $recovery['hashed'],
        );

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $hashed,
        ])->save();

        // Store the plain codes in session — shown ONCE on the next page.
        $request->session()->put('two_factor_recovery_codes_plain', $recovery['plain']);

        return redirect()->route('super-admin.security.2fa.recovery-codes');
    }

    /**
     * Step 3: Show the recovery codes once.
     */
    public function showRecoveryCodes(Request $request): View|RedirectResponse
    {
        $this->ensureSuperAdmin();

        $codes = $request->session()->pull('two_factor_recovery_codes_plain');

        if (! is_array($codes) || empty($codes)) {
            return redirect()
                ->route('super-admin.settings.index')
                ->with('info', __('messages.two_factor.recovery_codes_gone'));
        }

        return view('super-admin.security.two-factor-recovery-codes', [
            'codes' => $codes,
        ]);
    }
}

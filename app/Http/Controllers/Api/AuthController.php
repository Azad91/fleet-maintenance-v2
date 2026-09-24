<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Dummy bcrypt hash used for timing-attack defense.
     *
     * When a login attempt targets an email that does not exist, we
     * still run Hash::check() against this constant value so that the
     * response time is indistinguishable from a real "wrong password"
     * attempt. Without this, an attacker can enumerate valid emails
     * by measuring response latency.
     *
     * The value below is the bcrypt hash of the string "password"
     * (used by Laravel's UserFactory) — a well-known constant that
     * contains no real secret.
     */
    private const DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    /**
     * Authenticate via email + password and issue a Sanctum token.
     *
     * ─── SUPERADMIN MFA ENFORCEMENT ────────────────────────────────
     * The web login flow forces SuperAdmin through a TOTP challenge
     * (see AuthenticatedSessionController). The API must enforce the
     * same rule — otherwise a leaked password grants full platform
     * access via /api without ever touching MFA.
     *
     * Two-step flow:
     *
     *   1. First call: password only → if SuperAdmin has MFA enabled,
     *      return {requires_2fa: true} and DO NOT issue a token.
     *
     *   2. Second call: password + two_factor_code → verify the TOTP
     *      code (or a recovery code), then issue the token.
     *
     * The `two_factor_code` parameter is ignored for every other user
     * role, so the standard flow is unchanged for regular users.
     *
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'two_factor_code' => ['nullable', 'string'],
        ]);

        $this->ensureIsNotRateLimited($request);

        $user = User::where('email', $request->email)->first();

        // Timing-attack defense: always run a bcrypt comparison, even
        // when the user does not exist.
        $hashToCheck = $user?->password ?? self::DUMMY_HASH;
        $passwordValid = Hash::check($request->password, $hashToCheck);

        // Generic error to prevent user enumeration. The same message
        // covers "no user" and "wrong password".
        if (! $user || ! $passwordValid) {
            RateLimiter::hit(
                $this->throttleKey($request),
                (int) config('rate_limits.login_decay_seconds', 900)
            );

            Log::warning('API login failed', [
                'email' => $request->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_id' => \Illuminate\Support\Facades\Context::get('request_id'),
            ]);

            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        // Reject deactivated accounts (matches web login behavior).
        if (! $user->is_active) {
            RateLimiter::hit(
                $this->throttleKey($request),
                (int) config('rate_limits.login_decay_seconds', 900)
            );

            Log::warning('API login blocked — inactive account', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'request_id' => \Illuminate\Support\Facades\Context::get('request_id'),
            ]);

            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        // ────────────────────────────────────────────────────────────
        // SUPERADMIN MFA GATE
        // ────────────────────────────────────────────────────────────
        if ($user->isSuperAdmin() && $user->hasTwoFactorEnabled()) {
            $code = trim((string) $request->input('two_factor_code', ''));

            if ($code === '') {
                // Step 1: password was correct, but MFA is required.
                // Do NOT issue a token yet. The client must retry with
                // the TOTP code in the `two_factor_code` field.
                Log::info('API login requires 2FA', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'request_id' => \Illuminate\Support\Facades\Context::get('request_id'),
                ]);

                return response()->json([
                    'requires_2fa' => true,
                    'message' => __('messages.two_factor.subtitle'),
                ], 200);
            }

            // Step 2: verify the TOTP code or a recovery code.
            $verified = false;

            if (preg_match('/^\d{6}$/', $code)) {
                $verified = $user->verifyTwoFactorCode($code);
            }

            if (! $verified && str_contains($code, '-')) {
                $verified = $user->useRecoveryCode($code);
            }

            if (! $verified) {
                RateLimiter::hit(
                    $this->throttleKey($request),
                    (int) config('rate_limits.login_decay_seconds', 900)
                );

                Log::warning('API login failed — invalid 2FA code', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'request_id' => \Illuminate\Support\Facades\Context::get('request_id'),
                ]);

                throw ValidationException::withMessages([
                    'two_factor_code' => [__('messages.two_factor.invalid_code')],
                ]);
            }
        }

        // Successful login — clear the limiter.
        RateLimiter::clear($this->throttleKey($request));

        $deviceName = $request->device_name
            ?? Str::limit($request->userAgent() ?? 'unknown', 100, '');

        $token = $user->createToken($deviceName)->plainTextToken;

        Log::info('API login successful', [
            'user_id' => $user->id,
            'device' => $deviceName,
            'ip' => $request->ip(),
            'request_id' => \Illuminate\Support\Facades\Context::get('request_id'),
        ]);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->only(['id', 'name', 'email', 'role']),
        ]);
    }

    /**
     * Revoke the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Return the authenticated user with their current garage.
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->load('currentGarage')
        );
    }

    /**
     * Ensure the login request is not rate-limited.
     *
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(Request $request): void
    {
        $maxAttempts = (int) config('rate_limits.login_attempts', 5);

        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), $maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        Log::warning('API login rate limit hit', [
            'email' => $request->email,
            'ip' => $request->ip(),
            'retry_in' => $seconds,
            'request_id' => \Illuminate\Support\Facades\Context::get('request_id'),
        ]);

        throw ValidationException::withMessages([
            'email' => [
                trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => (int) ceil($seconds / 60),
                ]),
            ],
        ]);
    }

    /**
     * Build a unique throttle key for the current request.
     *
     * Combines email + IP address so that an attacker cannot lock out
     * a legitimate user by hammering their email from a different IP,
     * nor bypass the limit by rotating emails from a single IP.
     */
    private function throttleKey(Request $request): string
    {
        return 'api_login:'.Str::transliterate(
            Str::lower((string) $request->input('email')).'|'.$request->ip()
        );
    }
}

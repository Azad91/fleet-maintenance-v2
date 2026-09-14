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
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $this->ensureIsNotRateLimited($request);

        $user = User::where('email', $request->email)->first();

        // Timing-attack defense: always run a bcrypt comparison, even
        // when the user does not exist. The response time then depends
        // only on the hash cost, not on whether the email is valid.
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

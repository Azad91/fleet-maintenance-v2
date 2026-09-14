<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PinLoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for PIN login.
     */
    public function rules(): array
    {
        return [
            'employee_code' => ['required', 'string', 'max:50'],
            'pin' => ['required', 'string', 'digits_between:4,6'],
        ];
    }

    /**
     * Attempt to authenticate using employee code + PIN.
     *
     * @throws ValidationException
     */
    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        $code = mb_strtoupper(trim((string) $this->input('employee_code')));
        $pin = (string) $this->input('pin');

        $user = User::where('employee_code', $code)->first();

        // Generic error messages to prevent user/account enumeration.
        if (! $user) {
            $this->failAndRateLimit('employee_code');
        }

        // Only role='user' accounts can login with PIN.
        if ($user->role !== 'user') {
            $this->failAndRateLimit('employee_code');
        }

        if (! $user->is_active) {
            $this->failAndRateLimit('employee_code');
        }

        if (! $user->pin || ! Hash::check($pin, $user->pin)) {
            $this->failAndRateLimit('pin');
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->accountThrottleKey());

        return $user;
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * Two independent limits are enforced:
     *   1. IP + employee_code — the existing per-endpoint guard
     *   2. employee_code only — an account-level guard that survives
     *      IP rotation, protecting against distributed brute-force
     *      attacks against a single PIN
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $maxAttempts = (int) config('rate_limits.login_attempts', 5);

        if (RateLimiter::tooManyAttempts($this->throttleKey(), $maxAttempts)) {
            $this->throwLockout($this->throttleKey());
        }

        $accountMax = (int) config('rate_limits.pin_account_attempts', 15);

        if (RateLimiter::tooManyAttempts($this->accountThrottleKey(), $accountMax)) {
            $this->throwLockout($this->accountThrottleKey());
        }
    }

    /**
     * @throws ValidationException
     */
    protected function throwLockout(string $key): never
    {
        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'employee_code' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Increase rate limit and throw a generic validation error.
     *
     * @throws ValidationException
     */
    protected function failAndRateLimit(string $field): never
    {
        $decay = (int) config('rate_limits.login_decay_seconds', 900);

        // Hər iki limiti eyni vaxtda vur — IP dəyişikliyi bypass etməsin.
        RateLimiter::hit($this->throttleKey(), $decay);
        RateLimiter::hit($this->accountThrottleKey(), $decay);

        throw ValidationException::withMessages([
            $field => __('auth.failed'),
        ]);
    }

    /**
     * Per-endpoint throttle key: employee_code + IP address.
     */
    public function throttleKey(): string
    {
        $code = Str::transliterate(Str::lower((string) $this->input('employee_code')));

        return $code.'|'.$this->ip();
    }

    /**
     * Account-level throttle key: employee_code only, IP-independent.
     *
     * This exists specifically to close the IP-rotation bypass
     * against a single employee account.
     */
    public function accountThrottleKey(): string
    {
        $code = Str::transliterate(Str::lower((string) $this->input('employee_code')));

        return 'pin_account:'.$code;
    }
}

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
            'pin'           => ['required', 'string', 'digits_between:4,6'],
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
        $pin  = (string) $this->input('pin');

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

        return $user;
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

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
        RateLimiter::hit($this->throttleKey());

        throw ValidationException::withMessages([
            $field => __('auth.failed'),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        $code = Str::transliterate(Str::lower((string) $this->input('employee_code')));

        return $code . '|' . $this->ip();
    }
}

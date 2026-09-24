<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Named Rate Limiters (used by the throttle middleware)
    |--------------------------------------------------------------------------
    |
    | Format: "<attempts>,<decay_minutes>"
    | Example: "5,1" = 5 attempts per minute
    |
    | These names must match the ones registered in
    | AppServiceProvider::registerRateLimiters(). Routes reference
    | them via `throttle:<name>`.
    |
    */
    'login' => env('RATE_LIMIT_LOGIN', '5,1'),
    'api' => env('RATE_LIMIT_API', '60,1'),
    'pdf' => env('RATE_LIMIT_PDF', '10,1'),
    'import' => env('RATE_LIMIT_IMPORT', '5,1'),

    /*
    |--------------------------------------------------------------------------
    | Login Lockout (web + API email login, PIN login)
    |--------------------------------------------------------------------------
    |
    | After `attempts` failed attempts, the source is locked out for
    | `decay_seconds`. This is NOT a RateLimiter::for() rule — it is
    | enforced directly inside LoginRequest using RateLimiter::hit()
    | and RateLimiter::tooManyAttempts().
    |
    */
    'login_attempts' => (int) env('RATE_LIMIT_LOGIN_ATTEMPTS', 5),
    'login_decay_seconds' => (int) env('RATE_LIMIT_LOGIN_DECAY', 900),

    /*
    |--------------------------------------------------------------------------
    | PIN Login — Account-Level Lockout
    |--------------------------------------------------------------------------
    |
    | This limit is IP-independent: it applies to a single employee_code.
    | Its purpose is to prevent an attacker who rotates IPs from
    | brute-forcing a 4-digit PIN.
    |
    | 15 attempts * 4-digit space = 15 out of 10,000 combinations.
    | That is generous for a real user but unrealistic for an attacker.
    |
    */
    'pin_account_attempts' => (int) env('RATE_LIMIT_PIN_ACCOUNT_ATTEMPTS', 15),
];

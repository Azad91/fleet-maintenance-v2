<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Named Rate Limiters (throttle middleware üçün)
    |--------------------------------------------------------------------------
    |
    | Format: "<attempts>,<decay_minutes>"
    | Nümunə: "5,1" = 1 dəqiqə ərzində 5 cəhd
    |
    | Bu adlar AppServiceProvider::registerRateLimiters()-dəki adlarla
    | üst-üstə düşməlidir. Route-larda `throttle:<ad>` kimi istifadə olunur.
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
    | `attempts` səhv cəhddən sonra `decay_seconds` müddətində blok.
    | Bu, RateLimiter::for() deyil — LoginRequest daxilində birbaşa
    | RateLimiter::hit() / tooManyAttempts() ilə idarə olunur.
    |
    */
    'login_attempts' => (int) env('RATE_LIMIT_LOGIN_ATTEMPTS', 5),
    'login_decay_seconds' => (int) env('RATE_LIMIT_LOGIN_DECAY', 900),

    /*
    |--------------------------------------------------------------------------
    | PIN Login — Account-Level Lockout
    |--------------------------------------------------------------------------
    |
    | Bu limit IP-dən asılı deyil — tək employee_code üzrə tətbiq olunur.
    | Məqsəd: IP rotasiya edən hücumçunun 4 rəqəmli PIN-i brute-force
    | etməsinin qarşısını almaq.
    |
    | 15 cəhd * 4 rəqəm = 10.000 kombinasiyadan 15-i. Bu, real istifadəçi
    | üçün səxavətlidir, hücumçu üçün isə qeyri-real bir pəncərə yaradır.
    |
    */
    'pin_account_attempts' => (int) env('RATE_LIMIT_PIN_ACCOUNT_ATTEMPTS', 15),
];

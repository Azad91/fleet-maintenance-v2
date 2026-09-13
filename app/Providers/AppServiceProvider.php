<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Global password policy
        Password::defaults(function () {
            return app()->isProduction()
                ? Password::min(10)->letters()->numbers()->mixedCase()->symbols()
                : Password::min(8);
        });

        $this->registerRateLimiters();
    }

    /**
     * Named rate limiters — `throttle:<name>` middleware vasitəsilə işlədilir.
     *
     * Hər limiter `config/rate_limits.php`-dən "<attempts>,<decay_minutes>"
     * formatını oxuyur. Yəni .env-də `RATE_LIMIT_PDF=3,5` yazsan, PDF
     * endpoint 5 dəqiqə ərzində 3 cəhddən sonra 429 qaytaracaq.
     */
    protected function registerRateLimiters(): void
    {
        RateLimiter::for('login', function (Request $request) {
            [$attempts, $decayMinutes] = $this->parseRateLimit('login');

            // Email + IP birləşməsi: attacker başqa IP-dən qurbanın
            // emailini kilidləyə bilməz, eyni IP-dən çoxlu email
            // yoxlaya da bilməz.
            return Limit::perMinutes($decayMinutes, $attempts)
                ->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            [$attempts, $decayMinutes] = $this->parseRateLimit('api');

            return Limit::perMinutes($decayMinutes, $attempts)
                ->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('pdf', function (Request $request) {
            [$attempts, $decayMinutes] = $this->parseRateLimit('pdf');

            return Limit::perMinutes($decayMinutes, $attempts)
                ->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('import', function (Request $request) {
            [$attempts, $decayMinutes] = $this->parseRateLimit('import');

            return Limit::perMinutes($decayMinutes, $attempts)
                ->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * `config/rate_limits.<name>` dəyərini [attempts, decayMinutes]-ə çevirir.
     *
     * Səhv format verilsə, təhlükəsiz default (60 cəhd / 1 dəqiqə) qaytarır —
     * production-da səhv env səbəbindən endpoint-in tam açıq qalmasındansa,
     * kiçik bir limit tətbiq olunması daha yaxşıdır.
     *
     * @return array{0: int, 1: int}
     */
    protected function parseRateLimit(string $name): array
    {
        $raw = (string) config("rate_limits.{$name}", '60,1');
        $parts = array_map('trim', explode(',', $raw));

        $attempts = max(1, (int) ($parts[0] ?? 60));
        $decayMinutes = max(1, (int) ($parts[1] ?? 1));

        return [$attempts, $decayMinutes];
    }
}

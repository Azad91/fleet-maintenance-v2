<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use App\View\Composers\PendingTransferComposer;
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Password::defaults(function () {
            return app()->isProduction()
                ? Password::min(10)->letters()->numbers()->mixedCase()->symbols()
                : Password::min(8);
        });

        // ✅ Auto-revoke API tokens when a user is deactivated.
        User::observe(UserObserver::class);
        // Inject the pending-transfer badge count into every page
        // that uses the main application layout.
        \Illuminate\Support\Facades\View::composer(
            'layouts.app',
            \App\View\Composers\PendingTransferComposer::class,
        );
        $this->registerRateLimiters();
        $this->registerSuperAdminAuthListeners();
    }

    /**
     * Log every successful and failed authentication involving the
     * SuperAdmin account.
     *
     * The SuperAdmin is the single most privileged account on the
     * platform. Even without MFA, emitting a `warning`-level log line
     * on every SuperAdmin login (success OR failure) gives operators
     * an immediate signal in monitoring tools if the account is being
     * attacked or used unexpectedly.
     *
     * We use `warning` rather than `info` so the entries surface in
     * production logs without lowering the log level.
     */
    protected function registerSuperAdminAuthListeners(): void
    {
        Event::listen(Login::class, function (Login $event) {
            if (! $event->user instanceof User) {
                return;
            }

            if (! $event->user->isSuperAdmin()) {
                return;
            }

            Log::warning('SuperAdmin logged in', [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'request_id' => \Illuminate\Support\Facades\Context::get('request_id'),
            ]);
        });

        Event::listen(Failed::class, function (Failed $event) {
            $email = $event->credentials['email'] ?? null;

            if (! is_string($email) || $email === '') {
                return;
            }

            // Look up the user to determine if the target is a SuperAdmin.
            // This does NOT leak information back to the caller — the
            // response to the user is identical regardless.
            $user = User::where('email', $email)->first();

            if (! $user || ! $user->isSuperAdmin()) {
                return;
            }

            Log::warning('Failed SuperAdmin login attempt', [
                'email' => $email,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'request_id' => \Illuminate\Support\Facades\Context::get('request_id'),
            ]);
        });
    }

    /**
     * Named rate limiters — `throttle:<name>` middleware vasitəsilə işlədilir.
     */
    protected function registerRateLimiters(): void
    {
        RateLimiter::for('login', function (Request $request) {
            [$attempts, $decayMinutes] = $this->parseRateLimit('login');

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

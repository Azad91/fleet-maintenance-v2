<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 1. Archive audit logs older than 6 months — runs every night at 03:00.
Schedule::command('audit:archive --months=6')
    ->dailyAt('03:00')
    ->runInBackground()
    ->withoutOverlapping();

// 2. Prune expired Sanctum API tokens every night at 02:00.
//
// Sanctum tokens have a 30-day TTL (config/sanctum.php). Without this
// schedule, expired rows accumulate in personal_access_tokens forever,
// bloating the table and slowing down token lookups.
//
// The `--hours=24` flag keeps a 24-hour grace window after expiry, so
// a client that fires a request in-flight around the expiration moment
// gets a clean 401 instead of a "token row already deleted" error.
Schedule::command('sanctum:prune-expired --hours=24')
    ->dailyAt('02:00')
    ->runInBackground()
    ->withoutOverlapping();

// 3. Daily KM reminder — placeholder for a future notification job.
// Schedule::command('buses:check-daily-km')->dailyAt('23:00');

// 4. Prune stale cache tags every Sunday at 04:00 so the cache does
//    not grow unbounded over time.
Schedule::command('cache:prune-stale-tags')->weeklyOn(0, '04:00');

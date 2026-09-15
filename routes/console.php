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

// 2. Daily KM reminder — placeholder for a future notification job.
// Schedule::command('buses:check-daily-km')->dailyAt('23:00');

// 3. Prune stale cache tags every Sunday at 04:00 so the cache does
//    not grow unbounded over time.
Schedule::command('cache:prune-stale-tags')->weeklyOn(0, '04:00');
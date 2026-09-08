<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 1. Hər gecə saat 03:00-da 6 aydan köhnə olan audit loglarını avtomatik arxivləşdirir
Schedule::command('audit:archive --months=6')
    ->dailyAt('03:00')
    ->runInBackground()
    ->withoutOverlapping();

// 2. Gündəlik KM qeydləri unudulan avtobusları yoxlamaq (Gələcəkdə bildiriş göndərmək üçün hazırlıq)
// Schedule::command('buses:check-daily-km')->dailyAt('23:00');

// 3. Keşin həddindən artıq şişməməsi üçün hər bazar günü gecəsi keşi təmizləmək
Schedule::command('cache:prune-stale-tags')->weeklyOn(0, '04:00');

<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Read-only system settings and maintenance actions for the Super Admin.
 *
 * This page intentionally exposes only safe, platform-wide info.
 * No tenant-scoped data is displayed here.
 */
class SettingsController extends Controller
{
    public function index(): View
    {
        $system = [
            'app_name'         => config('app.name'),
            'app_env'          => config('app.env'),
            'app_debug'        => config('app.debug'),
            'app_url'          => config('app.url'),
            'php_version'      => PHP_VERSION,
            'laravel_version'  => app()->version(),
            'timezone'         => config('app.timezone'),
            'locale'           => config('app.locale'),
            'fallback_locale'  => config('app.fallback_locale'),
            'supported_locales'=> array_keys(config('app.supported_locales', [])),
            'db_driver'        => DB::connection()->getDriverName(),
            'cache_driver'     => config('cache.default'),
            'session_driver'   => config('session.driver'),
            'queue_driver'     => config('queue.default'),
            'filesystem_disk'  => config('filesystems.default'),
            'log_channel'      => config('logging.default'),
        ];

        return view('super-admin.settings', compact('system'));
    }

    /**
     * Clear compiled views, application cache, and config cache.
     */
    public function clearCache(): RedirectResponse
    {
        try {
            Artisan::call('view:clear');
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');

            return back()->with('success', __('messages.super_admin.settings.cache_cleared'));
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', __('messages.super_admin.settings.cache_clear_failed'));
        }
    }
}

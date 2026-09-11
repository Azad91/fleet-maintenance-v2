<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Global, cross-tenant dashboard for the platform Super Admin.
 *
 * Unlike the regular DashboardController (garage-scoped), this view
 * aggregates data across ALL companies and garages on the platform.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'companies_total'     => Company::count(),
            'companies_active'    => Company::where('is_active', true)->count(),
            'garages_total'       => Garage::count(),
            'garages_active'      => Garage::where('is_active', true)->count(),
            'users_total'         => User::count(),
            'users_active'        => User::where('is_active', true)->count(),
            'users_super_admins'  => User::where('role', 'super_admin')->count(),
            'buses_total'         => Bus::withoutGlobalScopes()->count(),
            'complaints_open'     => Complaint::withoutGlobalScopes()->where('status', '!=', 'completed')->count(),
        ];

        $recentCompanies = Company::orderByDesc('id')->limit(5)->get();

        $recentGarages = Garage::with('company')
            ->withCount(['buses', 'users'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        // Top garages by bus count (across all companies)
        $topGarages = Garage::with('company')
            ->withCount('buses')
            ->where('is_active', true)
            ->orderByDesc('buses_count')
            ->limit(5)
            ->get();

        // Storage / runtime info
        $system = [
            'php_version'      => PHP_VERSION,
            'laravel_version'  => app()->version(),
            'db_driver'        => DB::connection()->getDriverName(),
            'cache_driver'     => config('cache.default'),
            'session_driver'   => config('session.driver'),
            'queue_driver'     => config('queue.default'),
            'app_env'          => config('app.env'),
            'app_debug'        => config('app.debug'),
            'timezone'         => config('app.timezone'),
            'locale'           => config('app.locale'),
        ];

        return view('super-admin.dashboard', compact(
            'stats',
            'recentCompanies',
            'recentGarages',
            'topGarages',
            'system'
        ));
    }
}

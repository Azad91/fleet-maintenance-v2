<?php

namespace App\Providers;

use App\Http\Controllers\DashboardController;
use App\Policies\DashboardPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // ✅ DashboardPolicy-ni Gate ilə qeydiyyatdan keçir
        Gate::policy(DashboardController::class, DashboardPolicy::class);
    }
}

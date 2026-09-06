<?php

namespace App\Providers;

use App\Policies\DashboardPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\Paginator;
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
        Gate::policy(\App\Http\Controllers\DashboardController::class, DashboardPolicy::class);
    }
}

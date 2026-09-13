<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
    }
}

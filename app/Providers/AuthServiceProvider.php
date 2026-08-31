<?php

namespace App\Providers;

use App\Models\Complaint;
use App\Models\User;
use App\Policies\ComplaintPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Complaint::class => ComplaintPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Gate-lər (əlavə)
        Gate::define('view-buses', function (User $user) {
            return $user->hasGarageRole(['admin', 'bus', 'directorate']);
        });

        Gate::define('manage-warehouse', function (User $user) {
            return $user->hasGarageRole(['admin', 'warehouse']);
        });

        Gate::define('manage-daily-km', function (User $user) {
            return $user->hasGarageRole(['admin', 'daily_km']);
        });

        Gate::define('manage-daily-status', function (User $user) {
            return $user->hasGarageRole(['admin', 'daily_status']);
        });
    }
}

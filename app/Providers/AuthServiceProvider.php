<?php

namespace App\Providers;

use App\Models\Bus;
use App\Models\Complaint;
use App\Models\Warehouse;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\User;
use App\Policies\BusPolicy;
use App\Policies\ComplaintPolicy;
use App\Policies\WarehousePolicy;
use App\Policies\DriverPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Bus::class => BusPolicy::class,
        Complaint::class => ComplaintPolicy::class,
        Warehouse::class => WarehousePolicy::class,
        Driver::class => DriverPolicy::class,
        Employee::class => EmployeePolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}

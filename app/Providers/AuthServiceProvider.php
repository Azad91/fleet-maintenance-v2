<?php

namespace App\Providers;

use App\Models\Complaint;
use App\Models\Bus;
use App\Models\Warehouse;
use App\Policies\ComplaintPolicy;
use App\Policies\BusPolicy;
use App\Policies\WarehousePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Complaint::class => ComplaintPolicy::class,
        Bus::class => BusPolicy::class,
        Warehouse::class => WarehousePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
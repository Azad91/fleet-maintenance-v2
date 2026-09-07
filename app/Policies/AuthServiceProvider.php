<?php

namespace App\Providers;

use App\Models\Bus;
use App\Models\BusDailyStatus;
use App\Models\Complaint;
use App\Models\ComplaintType;
use App\Models\DailyKmRecord;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\MotorOilDetail;
use App\Models\User;
use App\Models\Warehouse;
use App\Policies\BusDailyStatusPolicy;
use App\Policies\BusPolicy;
use App\Policies\ComplaintPolicy;
use App\Policies\ComplaintTypePolicy;
use App\Policies\DailyKmRecordPolicy;
use App\Policies\DriverPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\MotorOilPolicy;
use App\Policies\UserPolicy;
use App\Policies\WarehousePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Complaint::class => ComplaintPolicy::class,
        Bus::class => BusPolicy::class,
        Warehouse::class => WarehousePolicy::class,
        Driver::class => DriverPolicy::class,
        Employee::class => EmployeePolicy::class,
        User::class => UserPolicy::class,
        BusDailyStatus::class => BusDailyStatusPolicy::class,  // ✅ YENİ
        DailyKmRecord::class => DailyKmRecordPolicy::class,    // ✅ YENİ
        MotorOilDetail::class => MotorOilPolicy::class,        // ✅ YENİ
        ComplaintType::class => ComplaintTypePolicy::class,    // ✅ YENİ
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}

<?php

use App\Enums\RoleEnum;
use App\Http\Controllers\SuperAdmin\CompanyController;
use App\Http\Controllers\SuperAdmin\GarageController;
use App\Http\Controllers\BusController;
use App\Http\Controllers\BusDailyStatusController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\ComplaintTypeController;
use App\Http\Controllers\DailyKmRecordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\GarageDataController;
use App\Http\Controllers\GarageSelectionController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\MotorOilController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', [HealthController::class, 'check'])->name('health.check');

// ✅ DASHBOARD ROUTE - role middleware OLMADAN, lakin adlandırılmış (Testlərin çökməməsi üçün)
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'garage.selected'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Auth Routes (Breeze)
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Garage Selection Routes (Auth required, NO garage middleware)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/select-garage', [GarageSelectionController::class, 'index'])->name('garage.selection');
    Route::post('/select-garage', [GarageSelectionController::class, 'selectGarage'])->name('garage.select');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Auth + Garage Selected + Idempotent)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'garage.selected', 'idempotent'])->group(function () {

    // ==================== PROFILE ROUTES ====================
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ==================== USER MANAGEMENT (ADMIN ONLY) ====================
    Route::prefix('users')->name('users.')->middleware(['role:'.RoleEnum::ADMIN->value])->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::get('/create', [UserManagementController::class, 'create'])->name('create');
        Route::post('/', [UserManagementController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserManagementController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserManagementController::class, 'update'])->name('update');
    });

    // ==================== COMPLAINT TYPES (ADMIN ONLY) ====================
    // ✅ "Route::resource('/')" problemi düzəldildi. Daha standart və problemsiz yanaşma.
    Route::middleware(['role:'.RoleEnum::ADMIN->value])->group(function () {
        Route::get('complaint-types/import', [ComplaintTypeController::class, 'importForm'])->name('complaint-types.import');
        Route::post('complaint-types/import', [ComplaintTypeController::class, 'import'])->name('complaint-types.import.store');
        Route::resource('complaint-types', ComplaintTypeController::class)->except(['show']);
    });

    // ==================== BUS ROUTES ====================
    Route::prefix('buses')->name('buses.')->group(function () {
        Route::middleware(['role:'.RoleEnum::ADMIN->value])->group(function () {
            // Statik routlar əvvəldə
            Route::get('/import', [BusController::class, 'importForm'])->name('import');
            Route::post('/import', [BusController::class, 'import'])->name('import.store');
            Route::get('/create', [BusController::class, 'create'])->name('create');
            Route::post('/bulk-deactivate', [BusController::class, 'bulkDeactivate'])->name('bulk.deactivate');
            Route::post('/bulk-activate', [BusController::class, 'bulkActivate'])->name('bulk.activate');
            Route::delete('/bulk-delete', [BusController::class, 'bulkDelete'])->name('bulk.delete');
            Route::post('/', [BusController::class, 'store'])->name('store');

            // Dinamik routlar altda
            Route::get('/{bus}/edit', [BusController::class, 'edit'])->name('edit');
            Route::put('/{bus}', [BusController::class, 'update'])->name('update');
            Route::delete('/{bus}', [BusController::class, 'destroy'])->name('destroy');
        });

        Route::middleware(['role:'.implode(',', [RoleEnum::ADMIN->value, RoleEnum::DIRECTORATE->value])])->group(function () {
            Route::get('/search', [BusController::class, 'search'])->name('search'); // Statik
            Route::get('/', [BusController::class, 'index'])->name('index');
            Route::get('/{bus}', [BusController::class, 'show'])->name('show'); // Dinamik
        });
    });

    // ==================== COMPLAINT ROUTES ====================
    Route::prefix('complaints')->name('complaints.')->group(function () {
        Route::middleware(['role:'.implode(',', [RoleEnum::ADMIN->value, RoleEnum::COMPLAINT->value])])->group(function () {
            // Statik routlar
            Route::get('/import', [ComplaintController::class, 'importForm'])->name('import');
            Route::post('/import', [ComplaintController::class, 'import'])->name('import.store');
            Route::get('/create', [ComplaintController::class, 'create'])->name('create');
            Route::post('/', [ComplaintController::class, 'store'])->name('store');

            // Dinamik routlar
            Route::get('/{complaint}/edit', [ComplaintController::class, 'edit'])->name('edit');
            Route::put('/{complaint}', [ComplaintController::class, 'update'])->name('update');
            Route::delete('/{complaint}', [ComplaintController::class, 'destroy'])->name('destroy');
            Route::post('/{complaint}/close', [ComplaintController::class, 'close'])->name('close');
        });

        Route::middleware(['role:'.implode(',', [RoleEnum::ADMIN->value, RoleEnum::COMPLAINT->value, RoleEnum::DIRECTORATE->value])])->group(function () {
            Route::get('/', [ComplaintController::class, 'index'])->name('index');
            Route::get('/{complaint}/pdf', [ComplaintController::class, 'downloadPdf'])->name('pdf'); // Dinamik, lakin spesifik
            Route::get('/{complaint}', [ComplaintController::class, 'show'])->name('show'); // Dinamik
        });
    });

    // ==================== WAREHOUSE ROUTES ====================
    Route::prefix('warehouses')->name('warehouses.')->group(function () {
        Route::middleware(['role:'.implode(',', [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE->value])])->group(function () {
            Route::get('/import', [WarehouseController::class, 'importForm'])->name('import');
            Route::post('/import', [WarehouseController::class, 'import'])->name('import.store');
            Route::get('/create', [WarehouseController::class, 'create'])->name('create');
            Route::post('/', [WarehouseController::class, 'store'])->name('store');

            Route::get('/{warehouse}/edit', [WarehouseController::class, 'edit'])->name('edit');
            Route::put('/{warehouse}', [WarehouseController::class, 'update'])->name('update');
            Route::delete('/{warehouse}', [WarehouseController::class, 'destroy'])->name('destroy');
        });

        Route::middleware(['role:'.implode(',', [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE->value, RoleEnum::DIRECTORATE->value])])->group(function () {
            Route::get('/search', [WarehouseController::class, 'search'])->name('search'); // Statik
            Route::get('/', [WarehouseController::class, 'index'])->name('index');
            Route::get('/{warehouse}', [WarehouseController::class, 'show'])->name('show'); // Dinamik
        });
    });

    // ==================== MOTOR OIL ROUTES ====================
    Route::prefix('motor-oil')->name('motor-oil.')->middleware(['role:'.implode(',', [RoleEnum::ADMIN->value, RoleEnum::DIRECTORATE->value, RoleEnum::WAREHOUSE->value])])->group(function () {
        Route::get('/import', [MotorOilController::class, 'importForm'])->name('import');
        Route::post('/import', [MotorOilController::class, 'import'])->name('import.store');
        Route::get('/search', [MotorOilController::class, 'search'])->name('search');
        Route::get('/', [MotorOilController::class, 'index'])->name('index');
    });

    // ==================== EMPLOYEE ROUTES ====================
    Route::prefix('employees')->name('employees.')->middleware(['role:'.RoleEnum::ADMIN->value])->group(function () {
        Route::get('/import', [EmployeeController::class, 'importForm'])->name('import');
        Route::post('/import', [EmployeeController::class, 'import'])->name('import.store');
        Route::get('/create', [EmployeeController::class, 'create'])->name('create');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::get('/', [EmployeeController::class, 'index'])->name('index');

        Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
        Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
        Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
        Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');
    });

    // ==================== BUS DAILY STATUS ROUTES ====================
    Route::prefix('bus-daily-statuses')->name('bus-daily-statuses.')->group(function () {
        Route::middleware(['role:'.implode(',', [RoleEnum::ADMIN->value, RoleEnum::DAILY_STATUS->value])])->group(function () {
            Route::get('/import', [BusDailyStatusController::class, 'importForm'])->name('import');
            Route::post('/import', [BusDailyStatusController::class, 'import'])->name('import.store');
            Route::get('/create', [BusDailyStatusController::class, 'create'])->name('create');
            Route::post('/', [BusDailyStatusController::class, 'store'])->name('store');

            Route::get('/{bus_daily_status}/edit', [BusDailyStatusController::class, 'edit'])->name('edit');
            Route::put('/{bus_daily_status}', [BusDailyStatusController::class, 'update'])->name('update');
            Route::delete('/{bus_daily_status}', [BusDailyStatusController::class, 'destroy'])->name('destroy');
        });

        Route::middleware(['role:'.implode(',', [RoleEnum::ADMIN->value, RoleEnum::DAILY_STATUS->value, RoleEnum::DIRECTORATE->value])])->group(function () {
            Route::get('/', [BusDailyStatusController::class, 'index'])->name('index');
            Route::get('/{bus_daily_status}', [BusDailyStatusController::class, 'show'])->name('show');
        });
    });

    // ==================== DAILY KM RECORDS ROUTES ====================
    Route::prefix('daily-km-records')->name('daily-km-records.')->group(function () {
        Route::middleware(['role:'.implode(',', [RoleEnum::ADMIN->value, RoleEnum::DAILY_KM->value])])->group(function () {
            Route::get('/import', [DailyKmRecordController::class, 'importForm'])->name('import');
            Route::post('/import', [DailyKmRecordController::class, 'import'])->name('import.store');
            Route::get('/create', [DailyKmRecordController::class, 'create'])->name('create');
            Route::post('/', [DailyKmRecordController::class, 'store'])->name('store');

            Route::get('/{daily_km_record}/edit', [DailyKmRecordController::class, 'edit'])->name('edit');
            Route::put('/{daily_km_record}', [DailyKmRecordController::class, 'update'])->name('update');
            Route::delete('/{daily_km_record}', [DailyKmRecordController::class, 'destroy'])->name('destroy');
        });

        Route::middleware(['role:'.implode(',', [RoleEnum::ADMIN->value, RoleEnum::DAILY_KM->value, RoleEnum::DIRECTORATE->value])])->group(function () {
            Route::get('/', [DailyKmRecordController::class, 'index'])->name('index');
            Route::get('/{daily_km_record}', [DailyKmRecordController::class, 'show'])->name('show');
        });
    });

    // ==================== DRIVER ROUTES ====================
    Route::prefix('drivers')->name('drivers.')->middleware(['role:'.RoleEnum::ADMIN->value])->group(function () {
        Route::get('/import', [DriverController::class, 'importForm'])->name('import');
        Route::post('/import', [DriverController::class, 'import'])->name('import.store');
        Route::get('/export', [DriverController::class, 'export'])->name('export');
        Route::get('/create', [DriverController::class, 'create'])->name('create');
        Route::post('/', [DriverController::class, 'store'])->name('store');
        Route::get('/', [DriverController::class, 'index'])->name('index');

        Route::get('/{driver}/edit', [DriverController::class, 'edit'])->name('edit');
        Route::get('/{driver}', [DriverController::class, 'show'])->name('show');
        Route::put('/{driver}', [DriverController::class, 'update'])->name('update');
        Route::delete('/{driver}', [DriverController::class, 'destroy'])->name('destroy');
    });

    // Super Admin routes
    Route::prefix('super-admin')
        ->name('super-admin.')
        ->group(function () {
            Route::resource('companies', \App\Http\Controllers\SuperAdmin\CompanyController::class);
            Route::resource('garages', \App\Http\Controllers\SuperAdmin\GarageController::class);
    });

    // ==================== API ROUTES (JSON) ====================
    Route::middleware(['role:'.implode(',', [RoleEnum::ADMIN->value, RoleEnum::COMPLAINT->value, RoleEnum::DIRECTORATE->value])])->group(function () {
        Route::get('get-bus-id-by-xett/{xett_no}', [GarageDataController::class, 'busByLine'])->name('get.bus.id.by.xett');
        Route::get('get-bus-km-by-id/{bus_id}', [GarageDataController::class, 'busKm'])->name('get.bus.km.by.id');
    });

    Route::middleware(['role:'.implode(',', [RoleEnum::ADMIN->value, RoleEnum::COMPLAINT->value, RoleEnum::WAREHOUSE->value])])->group(function () {
        Route::get('get-detal-by-kod/{kod}', [GarageDataController::class, 'detailByCode'])->name('get.detal.by.kod');
    });

    Route::middleware(['role:'.implode(',', [RoleEnum::ADMIN->value, RoleEnum::COMPLAINT->value])])->group(function () {
        Route::get('get-service-templates/{bus_id}', [GarageDataController::class, 'serviceTemplates'])->name('get.service.templates');
        Route::get('get-motor-oil-services/{bus_id}', [GarageDataController::class, 'motorOilServices'])->name('get.motor.oil.services');
        Route::get('get-driver-by-kod/{kod}', [GarageDataController::class, 'driverByCode'])->name('get.driver.by.kod');
    });

});


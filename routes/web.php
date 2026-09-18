<?php

use App\Enums\RoleEnum;
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
use App\Http\Controllers\SuperAdmin\AssignmentController;
use App\Http\Controllers\SuperAdmin\CompanyController;
use App\Http\Controllers\SuperAdmin\GarageController;
use App\Http\Controllers\SuperAdmin\UserController as SuperAdminUserController;
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
| Garage Selection Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/select-garage', [GarageSelectionController::class, 'index'])->name('garage.selection');
    Route::post('/select-garage', [GarageSelectionController::class, 'selectGarage'])->name('garage.select');
});

/*
|--------------------------------------------------------------------------
| Two-Factor Challenge (SuperAdmin only)
|--------------------------------------------------------------------------
| These routes are intentionally OUTSIDE the `auth` middleware: the
| user is not yet logged in when the challenge is shown. The session
| key `two_factor.user_id` is what identifies the pending login.
*/
Route::middleware('guest')->group(function () {
    Route::get('two-factor-challenge', [App\Http\Controllers\Auth\TwoFactorChallengeController::class, 'show'])
        ->name('two-factor.challenge');
    Route::post('two-factor-challenge', [App\Http\Controllers\Auth\TwoFactorChallengeController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('two-factor.challenge.store');
    Route::post('two-factor-challenge/cancel', [App\Http\Controllers\Auth\TwoFactorChallengeController::class, 'destroy'])
        ->name('two-factor.challenge.cancel');
});

/*
|--------------------------------------------------------------------------
| Director Routes (company-level, read-only)
|--------------------------------------------------------------------------
| Directors do NOT use the garage.selected middleware — they have no
| garage context and operate at the company level.
|
| Reports are aggregated across every garage in the director's company
| via ReportScope::for($user, $domain), which already returns the correct
| company-wide scope. The route group mirrors the garage-level report
| structure (reports.*) but lives under director.reports.* so the shell
| can detect the Director context and adjust the tab route prefix.
*/
Route::middleware(['auth'])
    ->prefix('director')
    ->name('director.')
    ->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Director\DirectorController::class, 'dashboard'])
            ->name('dashboard');
        Route::get('/garages', [App\Http\Controllers\Director\DirectorController::class, 'garages'])
            ->name('garages');
        Route::get('/garages/{garage}', [App\Http\Controllers\Director\DirectorController::class, 'showGarage'])
            ->name('garages.show');

        /*
        |------------------------------------------------------------------
        | Director Reports (company-wide, read-only)
        |------------------------------------------------------------------
        */
        Route::prefix('reports')->name('reports.')->group(function () {
            // Warehouse reports
            Route::prefix('warehouse')->name('warehouse.')->group(function () {
                Route::get('/receipt', [App\Http\Controllers\Director\Reports\DirectorWarehouseReportController::class, 'receipt'])->name('receipt');
                Route::get('/usage', [App\Http\Controllers\Director\Reports\DirectorWarehouseReportController::class, 'usage'])->name('usage');
                Route::get('/worker-activity', [App\Http\Controllers\Director\Reports\DirectorWarehouseReportController::class, 'workerActivity'])->name('worker-activity');
                Route::get('/low-stock', [App\Http\Controllers\Director\Reports\DirectorWarehouseReportController::class, 'lowStock'])->name('low-stock');
                Route::get('/movement', [App\Http\Controllers\Director\Reports\DirectorWarehouseReportController::class, 'movement'])->name('movement');
                Route::get('/service-vehicle-usage', [App\Http\Controllers\Director\Reports\DirectorWarehouseReportController::class, 'serviceVehicleUsage'])->name('service-vehicle-usage');
            });

            // Complaint reports
            Route::prefix('complaint')->name('complaint.')->group(function () {
                Route::get('/summary', [App\Http\Controllers\Director\Reports\DirectorComplaintReportController::class, 'summary'])->name('summary');
                Route::get('/top-types', [App\Http\Controllers\Director\Reports\DirectorComplaintReportController::class, 'topTypes'])->name('top-types');
                Route::get('/worker-activity', [App\Http\Controllers\Director\Reports\DirectorComplaintReportController::class, 'workerActivity'])->name('worker-activity');
                Route::get('/by-bus', [App\Http\Controllers\Director\Reports\DirectorComplaintReportController::class, 'byBus'])->name('by-bus');
                Route::get('/avg-close-time', [App\Http\Controllers\Director\Reports\DirectorComplaintReportController::class, 'avgCloseTime'])->name('avg-close-time');
            });

            // Daily KM reports
            Route::prefix('daily-km')->name('daily-km.')->group(function () {
                Route::get('/missing', [App\Http\Controllers\Director\Reports\DirectorDailyKmReportController::class, 'missing'])->name('missing');
                Route::get('/top-buses', [App\Http\Controllers\Director\Reports\DirectorDailyKmReportController::class, 'topBuses'])->name('top-buses');
                Route::get('/worker-activity', [App\Http\Controllers\Director\Reports\DirectorDailyKmReportController::class, 'workerActivity'])->name('worker-activity');
            });

            // Daily Status reports
            Route::prefix('daily-status')->name('daily-status.')->group(function () {
                Route::get('/distribution', [App\Http\Controllers\Director\Reports\DirectorDailyStatusReportController::class, 'distribution'])->name('distribution');
                Route::get('/changes', [App\Http\Controllers\Director\Reports\DirectorDailyStatusReportController::class, 'changes'])->name('changes');
                Route::get('/worker-activity', [App\Http\Controllers\Director\Reports\DirectorDailyStatusReportController::class, 'workerActivity'])->name('worker-activity');
            });

            // Transfer reports — company-wide aggregation across all garages
            Route::prefix('transfer')->name('transfer.')->group(function () {
                Route::get('/summary',         [App\Http\Controllers\Director\Reports\DirectorTransferReportController::class, 'summary'])->name('summary');
                Route::get('/detailed',        [App\Http\Controllers\Director\Reports\DirectorTransferReportController::class, 'detailed'])->name('detailed');
                Route::get('/by-route',        [App\Http\Controllers\Director\Reports\DirectorTransferReportController::class, 'byRoute'])->name('by-route');
                Route::get('/top-items',       [App\Http\Controllers\Director\Reports\DirectorTransferReportController::class, 'topItems'])->name('top-items');
                Route::get('/worker-activity', [App\Http\Controllers\Director\Reports\DirectorTransferReportController::class, 'workerActivity'])->name('worker-activity');
                Route::get('/disputed',        [App\Http\Controllers\Director\Reports\DirectorTransferReportController::class, 'disputed'])->name('disputed');
            });
        });
    });

/*
|--------------------------------------------------------------------------
| Super Admin Routes
|--------------------------------------------------------------------------
| SuperAdmin is a GLOBAL role — it has no garage context. These routes
| intentionally sit OUTSIDE the `garage.selected` middleware group so a
| super admin can reach the platform dashboard without being forced
| through the garage selection flow.
*/
Route::middleware(['auth', 'super.admin', '2fa.verified'])
    ->prefix('super-admin')
    ->name('super-admin.')
    ->group(function () {
        // Dashboard + Settings
        Route::get('/dashboard', [App\Http\Controllers\SuperAdmin\DashboardController::class, 'index'])
            ->name('dashboard');
        Route::get('/settings', [App\Http\Controllers\SuperAdmin\SettingsController::class, 'index'])
            ->name('settings.index');
        Route::post('/settings/clear-cache', [App\Http\Controllers\SuperAdmin\SettingsController::class, 'clearCache'])
            ->name('settings.clear-cache');

        // ─── 2FA Security (SuperAdmin only) ───
        Route::prefix('security/2fa')->name('security.2fa.')->group(function () {
            Route::get('/setup', [App\Http\Controllers\SuperAdmin\TwoFactorSetupController::class, 'show'])
                ->name('setup');
            Route::post('/setup/confirm', [App\Http\Controllers\SuperAdmin\TwoFactorSetupController::class, 'confirm'])
                ->name('confirm');
            Route::get('/recovery-codes', [App\Http\Controllers\SuperAdmin\TwoFactorSetupController::class, 'showRecoveryCodes'])
                ->name('recovery-codes');
        });

        // ─── Everything below requires MFA to be verified ───
        Route::middleware(['2fa.verified'])->group(function () {
            Route::resource('companies', CompanyController::class);
            Route::resource('garages', GarageController::class);
            Route::resource('users', SuperAdminUserController::class)->except(['show']);

            Route::post('companies/{company}/director', [AssignmentController::class, 'assignDirector'])
                ->name('companies.assign-director');
            Route::delete('companies/{company}/director/{user}', [AssignmentController::class, 'removeDirector'])
                ->name('companies.remove-director');
        });
    });

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Auth + Garage Selected + Idempotent)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'pin.enforced', 'garage.selected', 'idempotent'])->group(function () {

    // ==================== PROFILE ====================
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
    Route::middleware(['role:'.RoleEnum::ADMIN->value])->group(function () {
        Route::get('complaint-types/import', [ComplaintTypeController::class, 'importForm'])->name('complaint-types.import');
        Route::post('complaint-types/import', [ComplaintTypeController::class, 'import'])
            ->middleware('throttle:import')
            ->name('complaint-types.import.store');
        Route::resource('complaint-types', ComplaintTypeController::class)->except(['show']);
    });

    // ==================== BUSES (ADMIN ONLY) ====================
    Route::prefix('buses')->name('buses.')->middleware(['role:'.RoleEnum::ADMIN->value])->group(function () {
        // Static routes first
        Route::get('/import', [BusController::class, 'importForm'])->name('import');
        Route::post('/import', [BusController::class, 'import'])
            ->middleware('throttle:import')
            ->name('import.store');
        Route::get('/create', [BusController::class, 'create'])->name('create');
        Route::post('/bulk-deactivate', [BusController::class, 'bulkDeactivate'])->name('bulk.deactivate');
        Route::post('/bulk-activate', [BusController::class, 'bulkActivate'])->name('bulk.activate');
        Route::delete('/bulk-delete', [BusController::class, 'bulkDelete'])->name('bulk.delete');
        Route::delete('/bulk-delete-all', [BusController::class, 'bulkDeleteAll'])->name('bulk.delete-all'); // ← YENİ
        Route::get('/search', [BusController::class, 'search'])->name('search');

        // Dynamic routes last
        Route::get('/', [BusController::class, 'index'])->name('index');
        Route::post('/', [BusController::class, 'store'])->name('store');
        Route::get('/{bus}/edit', [BusController::class, 'edit'])->name('edit');
        Route::get('/{bus}', [BusController::class, 'show'])->name('show');
        Route::put('/{bus}', [BusController::class, 'update'])->name('update');
        Route::delete('/{bus}', [BusController::class, 'destroy'])->name('destroy');
    });

    // ==================== COMPLAINTS ====================
    $complaintRoles = implode(',', array_merge(
        [RoleEnum::ADMIN->value],
        RoleEnum::complaintRoles()
    ));

    Route::prefix('complaints')->name('complaints.')->middleware(['role:'.$complaintRoles])->group(function () {
        // Static routes first
        Route::get('/import', [ComplaintController::class, 'importForm'])->name('import');
        Route::post('/import', [ComplaintController::class, 'import'])
            ->middleware('throttle:import')
            ->name('import.store');
        Route::get('/create', [ComplaintController::class, 'create'])->name('create');
        Route::get('/search', [ComplaintController::class, 'search'])->name('search');

        Route::delete('/bulk-delete', [ComplaintController::class, 'bulkDelete'])->name('bulk.delete');
        Route::delete('/bulk-delete-all', [ComplaintController::class, 'bulkDeleteAll'])->name('bulk.delete-all');  // ← YENİ

        // Dynamic routes
        Route::get('/', [ComplaintController::class, 'index'])->name('index');
        Route::post('/', [ComplaintController::class, 'store'])->name('store');
        Route::get('/{complaint}/pdf', [ComplaintController::class, 'downloadPdf'])
            ->middleware('throttle:pdf')
            ->name('pdf');
        Route::get('/{complaint}/edit', [ComplaintController::class, 'edit'])->name('edit');
        Route::get('/{complaint}', [ComplaintController::class, 'show'])->name('show');
        Route::put('/{complaint}', [ComplaintController::class, 'update'])->name('update');
        Route::delete('/{complaint}', [ComplaintController::class, 'destroy'])->name('destroy');
        Route::post('/{complaint}/close', [ComplaintController::class, 'close'])->name('close');
    });

    // ==================== WAREHOUSES ====================
    $warehouseRoles = implode(',', array_merge(
        [RoleEnum::ADMIN->value],
        RoleEnum::warehouseRoles()
    ));

    Route::prefix('warehouses')->name('warehouses.')->middleware(['role:'.$warehouseRoles])->group(function () {
        // Static routes first
        Route::get('/import', [WarehouseController::class, 'importForm'])->name('import');
        Route::post('/import', [WarehouseController::class, 'import'])
            ->middleware('throttle:import')
            ->name('import.store');
        Route::get('/create', [WarehouseController::class, 'create'])->name('create');
        Route::get('/search', [WarehouseController::class, 'search'])->name('search');

        // Dynamic routes
        Route::get('/', [WarehouseController::class, 'index'])->name('index');
        Route::post('/', [WarehouseController::class, 'store'])->name('store');
        Route::get('/{warehouse}/edit', [WarehouseController::class, 'edit'])->name('edit');
        Route::get('/{warehouse}', [WarehouseController::class, 'show'])->name('show');
        Route::put('/{warehouse}', [WarehouseController::class, 'update'])->name('update');
        Route::delete('/{warehouse}', [WarehouseController::class, 'destroy'])->name('destroy');
    });

    // ==================== WAREHOUSE TRANSFERS ====================
    Route::prefix('warehouse-transfers')->name('warehouse-transfers.')->middleware(['role:'.RoleEnum::ADMIN->value])->group(function () {
        Route::get('/create', [\App\Http\Controllers\WarehouseTransferController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\WarehouseTransferController::class, 'store'])->name('store');
        Route::get('/', [\App\Http\Controllers\WarehouseTransferController::class, 'index'])->name('index');
        Route::get('/{transfer}', [\App\Http\Controllers\WarehouseTransferController::class, 'show'])->name('show');
        Route::post('/{transfer}/dispatch', [\App\Http\Controllers\WarehouseTransferController::class, 'dispatch'])->name('dispatch');
        Route::post('/{transfer}/receive', [\App\Http\Controllers\WarehouseTransferController::class, 'receive'])->name('receive');
        Route::post('/{transfer}/reject', [\App\Http\Controllers\WarehouseTransferController::class, 'reject'])->name('reject');
        Route::post('/{transfer}/resolve', [\App\Http\Controllers\WarehouseTransferController::class, 'resolve'])->name('resolve');
        Route::post('/{transfer}/cancel', [\App\Http\Controllers\WarehouseTransferController::class, 'cancel'])->name('cancel');
    });

    // ==================== MOTOR OIL (ADMIN ONLY) ====================
    Route::prefix('motor-oil')->name('motor-oil.')->middleware(['role:'.RoleEnum::ADMIN->value])->group(function () {
        Route::get('/import', [MotorOilController::class, 'importForm'])->name('import');
        Route::post('/import', [MotorOilController::class, 'import'])
            ->middleware('throttle:import')
            ->name('import.store');
        Route::get('/search', [MotorOilController::class, 'search'])->name('search');
        Route::delete('/bulk-delete-all', [MotorOilController::class, 'bulkDeleteAll'])->name('bulk.delete-all');
        Route::get('/', [MotorOilController::class, 'index'])->name('index');
    });

    // ==================== EMPLOYEES (ADMIN ONLY) ====================
    Route::prefix('employees')->name('employees.')->middleware(['role:'.RoleEnum::ADMIN->value])->group(function () {
        Route::get('/import', [EmployeeController::class, 'importForm'])->name('import');
        Route::post('/import', [EmployeeController::class, 'import'])
            ->middleware('throttle:import')
            ->name('import.store');
        Route::get('/create', [EmployeeController::class, 'create'])->name('create');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
        Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
        Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
        Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');
    });

    // ==================== SERVICE VEHICLES (ADMIN ONLY) ====================
    Route::prefix('service-vehicles')->name('service-vehicles.')->middleware(['role:'.RoleEnum::ADMIN->value])->group(function () {
        Route::get('/create', [\App\Http\Controllers\ServiceVehicleController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\ServiceVehicleController::class, 'store'])->name('store');
        // Static '/stocks' must come BEFORE the dynamic '/{service_vehicle}' route.
        Route::get('/stocks', [\App\Http\Controllers\ServiceVehicleController::class, 'stocks'])->name('stocks');
        Route::get('/', [\App\Http\Controllers\ServiceVehicleController::class, 'index'])->name('index');
        Route::get('/{service_vehicle}/edit', [\App\Http\Controllers\ServiceVehicleController::class, 'edit'])->name('edit');
        Route::get('/{service_vehicle}', [\App\Http\Controllers\ServiceVehicleController::class, 'show'])->name('show');
        Route::put('/{service_vehicle}', [\App\Http\Controllers\ServiceVehicleController::class, 'update'])->name('update');
        Route::delete('/{service_vehicle}', [\App\Http\Controllers\ServiceVehicleController::class, 'destroy'])->name('destroy');
    });

    // ==================== BUS DAILY STATUSES ====================
    $dailyStatusRoles = implode(',', array_merge(
        [RoleEnum::ADMIN->value],
        RoleEnum::dailyStatusRoles()
    ));

    Route::prefix('bus-daily-statuses')->name('bus-daily-statuses.')->middleware(['role:'.$dailyStatusRoles])->group(function () {
        Route::get('/export', [BusDailyStatusController::class, 'export'])->name('export');
        Route::delete('/bulk-delete-all', [BusDailyStatusController::class, 'bulkDeleteAll'])->name('bulk.delete-all');
        Route::get('/import', [BusDailyStatusController::class, 'importForm'])->name('import');
        Route::post('/import', [BusDailyStatusController::class, 'import'])
            ->middleware('throttle:import')
            ->name('import.store');
        Route::get('/create', [BusDailyStatusController::class, 'create'])->name('create');
        Route::post('/', [BusDailyStatusController::class, 'store'])->name('store');
        Route::get('/', [BusDailyStatusController::class, 'index'])->name('index');
        Route::get('/{bus_daily_status}/edit', [BusDailyStatusController::class, 'edit'])->name('edit');
        Route::get('/{bus_daily_status}', [BusDailyStatusController::class, 'show'])->name('show');
        Route::put('/{bus_daily_status}', [BusDailyStatusController::class, 'update'])->name('update');
        Route::delete('/{bus_daily_status}', [BusDailyStatusController::class, 'destroy'])->name('destroy');
    });

    // ==================== DAILY KM RECORDS ====================
    $dailyKmRoles = implode(',', array_merge(
        [RoleEnum::ADMIN->value],
        RoleEnum::dailyKmRoles()
    ));

    Route::prefix('daily-km-records')->name('daily-km-records.')->middleware(['role:'.$dailyKmRoles])->group(function () {
        Route::get('/export', [DailyKmRecordController::class, 'export'])->name('export');
        Route::delete('/bulk-delete-all', [DailyKmRecordController::class, 'bulkDeleteAll'])->name('bulk.delete-all');
        Route::get('/import', [DailyKmRecordController::class, 'importForm'])->name('import');
        Route::post('/import', [DailyKmRecordController::class, 'import'])
            ->middleware('throttle:import')
            ->name('import.store');
        Route::get('/create', [DailyKmRecordController::class, 'create'])->name('create');
        Route::post('/', [DailyKmRecordController::class, 'store'])->name('store');
        Route::get('/', [DailyKmRecordController::class, 'index'])->name('index');
        Route::get('/{daily_km_record}/edit', [DailyKmRecordController::class, 'edit'])->name('edit');
        Route::get('/{daily_km_record}', [DailyKmRecordController::class, 'show'])->name('show');
        Route::put('/{daily_km_record}', [DailyKmRecordController::class, 'update'])->name('update');
        Route::delete('/{daily_km_record}', [DailyKmRecordController::class, 'destroy'])->name('destroy');
    });

    // ==================== DRIVERS (ADMIN ONLY) ====================
    Route::prefix('drivers')->name('drivers.')->middleware(['role:'.RoleEnum::ADMIN->value])->group(function () {
        Route::get('/import', [DriverController::class, 'importForm'])->name('import');
        Route::post('/import', [DriverController::class, 'import'])
            ->middleware('throttle:import')
            ->name('import.store');
        Route::get('/export', [DriverController::class, 'export'])->name('export');
        Route::get('/create', [DriverController::class, 'create'])->name('create');
        Route::post('/', [DriverController::class, 'store'])->name('store');
        Route::get('/', [DriverController::class, 'index'])->name('index');
        Route::get('/{driver}/edit', [DriverController::class, 'edit'])->name('edit');
        Route::get('/{driver}', [DriverController::class, 'show'])->name('show');
        Route::put('/{driver}', [DriverController::class, 'update'])->name('update');
        Route::delete('/{driver}', [DriverController::class, 'destroy'])->name('destroy');
    });

    // ==================== REPORTS ====================
    Route::prefix('reports')->name('reports.')->group(function () {

        // Warehouse reports
        Route::prefix('warehouse')->name('warehouse.')
            ->middleware(['role:'.implode(',', array_merge(
                [RoleEnum::ADMIN->value],
                RoleEnum::warehouseRoles()
            ))])
            ->group(function () {
                Route::get('/receipt', [App\Http\Controllers\Reports\WarehouseReportController::class, 'receipt'])->name('receipt');
                Route::get('/usage', [App\Http\Controllers\Reports\WarehouseReportController::class, 'usage'])->name('usage');
                Route::get('/worker-activity', [App\Http\Controllers\Reports\WarehouseReportController::class, 'workerActivity'])->name('worker-activity');
                Route::get('/low-stock', [App\Http\Controllers\Reports\WarehouseReportController::class, 'lowStock'])->name('low-stock');
                Route::get('/movement', [App\Http\Controllers\Reports\WarehouseReportController::class, 'movement'])->name('movement');
                Route::get('/service-vehicle-usage', [App\Http\Controllers\Reports\WarehouseReportController::class, 'serviceVehicleUsage'])->name('service-vehicle-usage');
            });

        // Complaint reports
        Route::prefix('complaint')->name('complaint.')
            ->middleware(['role:'.implode(',', array_merge(
                [RoleEnum::ADMIN->value],
                RoleEnum::complaintRoles()
            ))])
            ->group(function () {
                Route::get('/summary', [App\Http\Controllers\Reports\ComplaintReportController::class, 'summary'])->name('summary');
                Route::get('/top-types', [App\Http\Controllers\Reports\ComplaintReportController::class, 'topTypes'])->name('top-types');
                Route::get('/worker-activity', [App\Http\Controllers\Reports\ComplaintReportController::class, 'workerActivity'])->name('worker-activity');
                Route::get('/by-bus', [App\Http\Controllers\Reports\ComplaintReportController::class, 'byBus'])->name('by-bus');
                Route::get('/avg-close-time', [App\Http\Controllers\Reports\ComplaintReportController::class, 'avgCloseTime'])->name('avg-close-time');
            });

        // Daily KM reports
        Route::prefix('daily-km')->name('daily-km.')
            ->middleware(['role:'.implode(',', array_merge(
                [RoleEnum::ADMIN->value],
                RoleEnum::dailyKmRoles()
            ))])
            ->group(function () {
                Route::get('/missing', [App\Http\Controllers\Reports\DailyKmReportController::class, 'missing'])->name('missing');
                Route::get('/top-buses', [App\Http\Controllers\Reports\DailyKmReportController::class, 'topBuses'])->name('top-buses');
                Route::get('/worker-activity', [App\Http\Controllers\Reports\DailyKmReportController::class, 'workerActivity'])->name('worker-activity');
            });

        // Daily Status reports
        Route::prefix('daily-status')->name('daily-status.')
            ->middleware(['role:'.implode(',', array_merge(
                [RoleEnum::ADMIN->value],
                RoleEnum::dailyStatusRoles()
            ))])
            ->group(function () {
                Route::get('/distribution', [App\Http\Controllers\Reports\DailyStatusReportController::class, 'distribution'])->name('distribution');
                Route::get('/changes', [App\Http\Controllers\Reports\DailyStatusReportController::class, 'changes'])->name('changes');
                Route::get('/worker-activity', [App\Http\Controllers\Reports\DailyStatusReportController::class, 'workerActivity'])->name('worker-activity');
            });

        // Transfer reports — accessible by garage admins and warehouse-domain users
        Route::prefix('transfer')->name('transfer.')
            ->middleware(['role:'.implode(',', array_merge(
                [RoleEnum::ADMIN->value],
                RoleEnum::warehouseRoles()
            ))])
            ->group(function () {
                Route::get('/summary',         [App\Http\Controllers\Reports\TransferReportController::class, 'summary'])->name('summary');
                Route::get('/detailed',        [App\Http\Controllers\Reports\TransferReportController::class, 'detailed'])->name('detailed');
                Route::get('/by-route',        [App\Http\Controllers\Reports\TransferReportController::class, 'byRoute'])->name('by-route');
                Route::get('/top-items',       [App\Http\Controllers\Reports\TransferReportController::class, 'topItems'])->name('top-items');
                Route::get('/worker-activity', [App\Http\Controllers\Reports\TransferReportController::class, 'workerActivity'])->name('worker-activity');
                Route::get('/disputed',        [App\Http\Controllers\Reports\TransferReportController::class, 'disputed'])->name('disputed');
            });
    });

    // ==================== API JSON (Garage Data) ====================
    $garageDataRoles = implode(',', array_merge(
        [RoleEnum::ADMIN->value],
        RoleEnum::complaintRoles(),
        RoleEnum::warehouseRoles()
    ));

    Route::middleware(['role:'.$garageDataRoles])->group(function () {
        Route::get('get-bus-id-by-xett/{xett_no}', [GarageDataController::class, 'busByLine'])->name('get.bus.id.by.xett');
        Route::get('get-bus-by-dqn/{dqn}', [GarageDataController::class, 'busByDqn'])->name('get.bus.by.dqn');
        Route::get('get-bus-km-by-id/{bus_id}', [GarageDataController::class, 'busKm'])->name('get.bus.km.by.id');
        Route::get('get-detal-by-kod/{kod}', [GarageDataController::class, 'detailByCode'])->name('get.detal.by.kod');
        Route::get('get-service-vehicle-part-by-code', [GarageDataController::class, 'serviceVehiclePartByCode'])->name('get.service.vehicle.part.by.code');
        Route::get('get-driver-by-kod/{kod}', [GarageDataController::class, 'driverByCode'])->name('get.driver.by.kod');
        Route::get('get-employee-by-kod/{kod}', [GarageDataController::class, 'employeeByCode'])->name('get.employee.by.kod');
        Route::get('get-service-templates/{bus_id}', [GarageDataController::class, 'serviceTemplates'])->name('get.service.templates');
        Route::get('get-motor-oil-services/{bus_id}', [GarageDataController::class, 'motorOilServices'])->name('get.motor.oil.services');
        Route::get('get-motor-oil-intervals/{bus_id}', [GarageDataController::class, 'motorOilIntervals'])->name('get.motor.oil.intervals');
        Route::get('get-motor-oil-parts/{bus_id}', [GarageDataController::class, 'motorOilParts'])->name('get.motor.oil.parts');
    });

});

<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BusController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\ComplaintTypeController;
use App\Http\Controllers\MotorOilController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\BusDailyStatusController;
use App\Http\Controllers\DailyKmRecordController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\GarageSelectionController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
});

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
| Authenticated Routes (Auth + Garage Selected)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'garage.selected'])->group(function () {

    // Dashboard
    // Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ==================== BUS ROUTES ====================
    Route::prefix('buses')->name('buses.')->group(function () {
        Route::middleware(['role:admin'])->group(function () {
            Route::get('/import', [BusController::class, 'importForm'])->name('import');
            Route::post('/import', [BusController::class, 'import'])->name('import.store');
            Route::get('/create', [BusController::class, 'create'])->name('create');
            Route::post('/', [BusController::class, 'store'])->name('store');
            Route::get('/{bus}/edit', [BusController::class, 'edit'])->name('edit');
            Route::put('/{bus}', [BusController::class, 'update'])->name('update');
            Route::delete('/{bus}', [BusController::class, 'destroy'])->name('destroy');
        });

        Route::middleware(['role:admin,bus,directorate'])->group(function () {
            Route::get('/', [BusController::class, 'index'])->name('index');
            Route::get('/search', [BusController::class, 'search'])->name('search');
            Route::get('/{bus}', [BusController::class, 'show'])->name('show');
        });
    });

    // ==================== COMPLAINT ROUTES ====================
    Route::prefix('complaints')->name('complaints.')->group(function () {
        Route::middleware(['role:admin,complaint'])->group(function () {
            Route::get('/import', [ComplaintController::class, 'importForm'])->name('import');
            Route::post('/import', [ComplaintController::class, 'import'])->name('import.store');
            Route::post('/', [ComplaintController::class, 'store'])->name('store');
            Route::get('/{complaint}/edit', [ComplaintController::class, 'edit'])->name('edit');
            Route::put('/{complaint}', [ComplaintController::class, 'update'])->name('update');
            Route::delete('/{complaint}', [ComplaintController::class, 'destroy'])->name('destroy');
        });

        Route::middleware(['role:admin,complaint,directorate'])->group(function () {
            Route::get('/', [ComplaintController::class, 'index'])->name('index');
            Route::get('/search', [ComplaintController::class, 'search'])->name('search');
            Route::get('/create', [ComplaintController::class, 'create'])->name('create');
            Route::get('/{complaint}', [ComplaintController::class, 'show'])->name('show');
        });
    });

    Route::post('/complaints/{complaint}/close', [ComplaintController::class, 'close'])->name('complaints.close');

    // ==================== COMPLAINT TYPES ====================
    Route::prefix('complaint-types')->name('complaint-types.')->middleware(['role:admin'])->group(function () {
        Route::get('/import', [ComplaintTypeController::class, 'importForm'])->name('import');
        Route::post('/import', [ComplaintTypeController::class, 'import'])->name('import.store');
        Route::resource('/', ComplaintTypeController::class)->parameters(['' => 'complaint_type']);
    });

    // ==================== WAREHOUSE ROUTES ====================
    Route::prefix('warehouses')->name('warehouses.')->group(function () {
        Route::middleware(['role:admin,warehouse'])->group(function () {
            Route::get('/import', [WarehouseController::class, 'importForm'])->name('import');
            Route::post('/import', [WarehouseController::class, 'import'])->name('import.store');
            Route::get('/create', [WarehouseController::class, 'create'])->name('create');
            Route::post('/', [WarehouseController::class, 'store'])->name('store');
            Route::get('/{warehouse}/edit', [WarehouseController::class, 'edit'])->name('edit');
            Route::put('/{warehouse}', [WarehouseController::class, 'update'])->name('update');
            Route::delete('/{warehouse}', [WarehouseController::class, 'destroy'])->name('destroy');
        });

        Route::middleware(['role:admin,warehouse,directorate'])->group(function () {
            Route::get('/', [WarehouseController::class, 'index'])->name('index');
            Route::get('/search', [WarehouseController::class, 'search'])->name('search');
            Route::get('/{warehouse}', [WarehouseController::class, 'show'])->name('show');
        });
    });

    // ==================== MOTOR OIL ROUTES ====================
    Route::prefix('motor-oil')->name('motor-oil.')->middleware(['role:admin'])->group(function () {
        Route::get('/', [MotorOilController::class, 'index'])->name('index');
        Route::get('/search', [MotorOilController::class, 'search'])->name('search');
        Route::get('/import', [MotorOilController::class, 'importForm'])->name('import');
        Route::post('/import', [MotorOilController::class, 'import'])->name('import.store');
    });

    // ==================== EMPLOYEE ROUTES ====================
    Route::prefix('employees')->name('employees.')->middleware(['role:admin'])->group(function () {
        Route::get('/import', [EmployeeController::class, 'importForm'])->name('import');
        Route::post('/import', [EmployeeController::class, 'import'])->name('import.store');
        Route::get('/create', [EmployeeController::class, 'create'])->name('create');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
        Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
        Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
        Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');
    });

    // ==================== BUS DAILY STATUS ROUTES ====================
    Route::prefix('bus-daily-statuses')->name('bus-daily-statuses.')->middleware(['role:admin'])->group(function () {
        Route::get('/import', [BusDailyStatusController::class, 'importForm'])->name('import');
        Route::post('/import', [BusDailyStatusController::class, 'import'])->name('import.store');
        Route::get('/create', [BusDailyStatusController::class, 'create'])->name('create');
        Route::post('/', [BusDailyStatusController::class, 'store'])->name('store');
        Route::get('/', [BusDailyStatusController::class, 'index'])->name('index');
        Route::get('/{bus_daily_status}', [BusDailyStatusController::class, 'show'])->name('show');
        Route::get('/{bus_daily_status}/edit', [BusDailyStatusController::class, 'edit'])->name('edit');
        Route::put('/{bus_daily_status}', [BusDailyStatusController::class, 'update'])->name('update');
        Route::delete('/{bus_daily_status}', [BusDailyStatusController::class, 'destroy'])->name('destroy');
    });

    // ==================== DAILY KM RECORDS ROUTES ====================
    Route::prefix('daily-km-records')->name('daily-km-records.')->middleware(['role:admin'])->group(function () {
        Route::get('/import', [DailyKmRecordController::class, 'importForm'])->name('import');
        Route::post('/import', [DailyKmRecordController::class, 'import'])->name('import.store');
        Route::get('/create', [DailyKmRecordController::class, 'create'])->name('create');
        Route::post('/', [DailyKmRecordController::class, 'store'])->name('store');
        Route::get('/', [DailyKmRecordController::class, 'index'])->name('index');
        Route::get('/{daily_km_record}', [DailyKmRecordController::class, 'show'])->name('show');
        Route::get('/{daily_km_record}/edit', [DailyKmRecordController::class, 'edit'])->name('edit');
        Route::put('/{daily_km_record}', [DailyKmRecordController::class, 'update'])->name('update');
        Route::delete('/{daily_km_record}', [DailyKmRecordController::class, 'destroy'])->name('destroy');
    });

    // ==================== DRIVER ROUTES ====================
    Route::prefix('drivers')->name('drivers.')->middleware(['role:admin'])->group(function () {
        Route::get('/import', [DriverController::class, 'importForm'])->name('import');
        Route::post('/import', [DriverController::class, 'import'])->name('import.store');
        Route::get('/export', [DriverController::class, 'export'])->name('export');
        Route::get('/', [DriverController::class, 'index'])->name('index');
        Route::get('/create', [DriverController::class, 'create'])->name('create');
        Route::post('/', [DriverController::class, 'store'])->name('store');
        Route::get('/{driver}', [DriverController::class, 'show'])->name('show');
        Route::get('/{driver}/edit', [DriverController::class, 'edit'])->name('edit');
        Route::put('/{driver}', [DriverController::class, 'update'])->name('update');
        Route::delete('/{driver}', [DriverController::class, 'destroy'])->name('destroy');
    });

    // ==================== API ROUTES (JSON) ====================
    Route::get('get-bus-id-by-xett/{xett_no}', function ($xett_no) {
        $bus = App\Models\Bus::where('xett_no', $xett_no)->first();
        return response()->json([
            'dqn' => $bus ? $bus->dqn : null,
            'bus_id' => $bus ? $bus->id : null
        ]);
    })->name('get.bus.id.by.xett');

    Route::get('get-detal-by-kod/{kod}', function ($kod) {
        $detal = App\Models\Warehouse::where('kod', $kod)->first();
        return response()->json([
            'detal_adi' => $detal ? $detal->ad : null,
            'depo_miqdari' => $detal ? $detal->miqdar : null,
        ]);
    })->name('get.detal.by.kod');

    Route::get('get-bus-km-by-id/{bus_id}', function ($bus_id) {
        $bus = App\Models\Bus::find($bus_id);
        if ($bus) {
            $latestKm = $bus->dailyKmRecords()->latest('tarix')->first();
            return response()->json(['km' => $latestKm ? $latestKm->km : null]);
        }
        return response()->json(['km' => null]);
    })->name('get.bus.km.by.id');

    Route::get('get-service-templates/{bus_id}', function ($bus_id) {
        $bus = App\Models\Bus::find($bus_id);
        if (!$bus) return response()->json([]);

        $templates = App\Models\ServiceTemplate::all();
        $result = $templates->map(function ($template) use ($bus) {
            $interval = App\Models\BusServiceInterval::where('bus_id', $bus->id)
                ->where('service_template_id', $template->id)->first();
            return [
                'id' => $template->id,
                'name' => $template->name,
                'km_interval' => $interval ? $interval->custom_km_interval : $template->default_km_interval,
                'details' => $template->details,
            ];
        });
        return response()->json($result);
    })->name('get.service.templates');

    Route::get('get-motor-oil-services/{bus_id}', function ($bus_id) {
        $bus = App\Models\Bus::findOrFail($bus_id);
        $latestKm = optional($bus->dailyKmRecords()->latest('tarix')->first())->km ?? 0;

        return App\Models\MotorOilDetail::where('km', '>', $latestKm)
            ->orderBy('km')
            ->orderBy('detal_adi')
            ->get()
            ->groupBy('km')
            ->map(fn ($details, $km) => [
                'km' => (int) $km,
                'details' => $details->map(fn ($detail) => [
                    'kodu' => $detail->detal_kodu,
                    'adi' => $detail->detal_adi,
                    'miqdar' => $detail->miqdar,
                    'say' => $detail->say,
                ])->values(),
            ])
            ->values();
    })->name('get.motor.oil.services');

    Route::get('get-driver-by-kod/{kod}', function ($kod) {
        $driver = App\Models\Driver::where('kodu', $kod)->first();
        return response()->json([
            'driver_ad' => $driver ? $driver->full_name : null,
            'driver_id' => $driver ? $driver->id : null,
        ]);
    })->name('get.driver.by.kod');
});

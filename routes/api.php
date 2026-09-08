<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BusController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    Route::middleware(['auth:sanctum','api.garage','idempotent'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/user', [AuthController::class, 'user'])->name('user');

        Route::apiResource('buses', BusController::class)->only([
            'index', 'show', 'store', 'update', 'destroy',
        ]);

        Route::apiResource('complaints', ComplaintController::class)->only([
            'index', 'show', 'store', 'update', 'destroy',
        ]);
        Route::post('/complaints/{complaint}/close', [ComplaintController::class, 'close'])->name('complaints.close');
        Route::get('/complaints/{complaint}/pdf', [ComplaintController::class, 'downloadPdf'])->name('complaints.pdf');

        Route::apiResource('warehouses', WarehouseController::class)->only([
            'index', 'show', 'store', 'update', 'destroy',
        ]);

        Route::get('/search/buses', [BusController::class, 'search'])->name('buses.search');
        Route::get('/search/warehouses', [WarehouseController::class, 'search'])->name('warehouses.search');
    });
});

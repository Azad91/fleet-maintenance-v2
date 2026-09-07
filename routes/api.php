<?php

use App\Http\Controllers\Api\BusController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ==================== PUBLIC ROUTES ====================
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// ==================== PROTECTED ROUTES ====================
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // ==================== BUSES ====================
    Route::apiResource('buses', BusController::class)->only([
        'index', 'show', 'store', 'update', 'destroy'
    ]);

    // ==================== COMPLAINTS ====================
    Route::apiResource('complaints', ComplaintController::class)->only([
        'index', 'show', 'store', 'update', 'destroy'
    ]);
    Route::post('/complaints/{complaint}/close', [ComplaintController::class, 'close']);
    Route::get('/complaints/{complaint}/pdf', [ComplaintController::class, 'downloadPdf']);

    // ==================== WAREHOUSE ====================
    Route::apiResource('warehouses', WarehouseController::class)->only([
        'index', 'show', 'store', 'update', 'destroy'
    ]);

    // ==================== SEARCH ====================
    Route::get('/search/buses', [BusController::class, 'search']);
    Route::get('/search/warehouses', [WarehouseController::class, 'search']);
});

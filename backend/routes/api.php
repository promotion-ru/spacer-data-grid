<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
//    Route::post('/register', [AuthController::class, 'register']);
});

// Protected routes (token-based)
Route::middleware(['auth:sanctum'])->group(function () {
    // User routes
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-other-devices', [AuthController::class, 'logoutOtherDevices']);
    Route::post('/auth/logout-all-devices', [AuthController::class, 'logoutAllDevices']);
    Route::post('/auth/refresh-token', [AuthController::class, 'refreshToken']);
    Route::get('/auth/check-token', [AuthController::class, 'checkToken']);

    // Token management
    Route::prefix('tokens')->group(function () {
        Route::get('/', [AuthController::class, 'tokens']);
        Route::delete('/{tokenId}', [AuthController::class, 'revokeToken']);
    });
});

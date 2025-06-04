<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
//    Route::post('/register', [AuthController::class, 'register']);
});

// Protected routes (token-based)
Route::middleware(['auth:sanctum'])->group(function () {
    // User routes
    Route::get('/user', [AuthController::class, 'user']);

    // Authentication management
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-other-devices', [AuthController::class, 'logoutOtherDevices']);
        Route::post('/logout-all-devices', [AuthController::class, 'logoutAllDevices']);
        Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
        Route::get('/check-token', [AuthController::class, 'checkToken']);
    });

    // Token management
    Route::prefix('tokens')->group(function () {
        Route::get('/', [AuthController::class, 'tokens']);
        Route::delete('/{tokenId}', [AuthController::class, 'revokeToken']);
    });

    // Profile management
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::patch('/', [ProfileController::class, 'update']);
    });
});

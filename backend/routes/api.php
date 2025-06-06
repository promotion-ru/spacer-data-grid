<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DataGridController;
use App\Http\Controllers\DataGridInvitationController;
use App\Http\Controllers\DataGridMemberController;
use App\Http\Controllers\DataGridRecordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
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

    // Data grid management
    Route::prefix('data-grid')->name('data-grid.')->group(function () {
        Route::get('/', [DataGridController::class, 'index'])->name('index');
        Route::post('/', [DataGridController::class, 'store'])->name('store');
        Route::get('/{dataGrid}', [DataGridController::class, 'show'])->name('show');
        Route::patch('/{dataGrid}', [DataGridController::class, 'update'])->name('update.patch');
        Route::delete('/{dataGrid}', [DataGridController::class, 'destroy'])->name('destroy');

        Route::prefix('{dataGrid}')->group(function () {
            // Отправка приглашения
            Route::post('invite', [DataGridInvitationController::class, 'store']);
            // Управление участниками
            Route::get('members', [DataGridMemberController::class, 'index']);
            Route::put('members/{member}', [DataGridMemberController::class, 'update']);
            Route::delete('members/{member}', [DataGridMemberController::class, 'destroy']);
            Route::post('leave', [DataGridMemberController::class, 'leave']);
            // Управление приглашениями
            Route::delete('invitations/{invitation}', [DataGridInvitationController::class, 'destroy']);
        });

        Route::prefix('{dataGrid}/records')->name('records.')->group(function () {
            Route::get('/', [DataGridRecordController::class, 'index'])->name('index');
            Route::post('/', [DataGridRecordController::class, 'store'])->name('store');
            Route::get('/{record}', [DataGridRecordController::class, 'show'])->name('show');
            Route::patch('/{record}', [DataGridRecordController::class, 'update'])->name('update');
            Route::delete('/{record}', [DataGridRecordController::class, 'destroy'])->name('destroy');
        });
    });

    // Получение приглашений для текущего пользователя
    Route::get('invitations', [DataGridInvitationController::class, 'index']);
    // Принятие/отклонение приглашений
    Route::post('invitations/{token}/accept', [DataGridInvitationController::class, 'accept']);
    Route::post('invitations/{token}/decline', [DataGridInvitationController::class, 'decline']);


    Route::prefix('users')->name('users.')->group(function () {

        // Публичные маршруты (если нужны)
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');

        // Защищенные маршруты (требуют авторизации)
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::patch('/{user}', [UserController::class, 'update'])->name('patch');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });
});

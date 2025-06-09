<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DataGrid\DataGridController;
use App\Http\Controllers\DataGrid\DataGridFileDownloadController;
use App\Http\Controllers\DataGrid\DataGridInvitationController;
use App\Http\Controllers\DataGrid\DataGridMemberController;
use App\Http\Controllers\DataGrid\DataGridRecordController;
use App\Http\Controllers\DataGrid\DataGridTypeController;
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

    // Роуты для скачивания файлов с middleware
    Route::middleware(['sanctum.query'])->group(function () {
        Route::get('/data-grid/{gridId}/records/{recordId}/media/{mediaId}/download',
            [DataGridFileDownloadController::class, 'downloadMedia']
        )->name('api.media.download');
    });

    // Data grid management
    Route::prefix('data-grid')->name('data-grid.')->group(function () {
        Route::get('/', [DataGridController::class, 'index'])->name('index');
        Route::post('/', [DataGridController::class, 'store'])->name('store');
        Route::get('/{dataGrid}', [DataGridController::class, 'show'])->name('show');
        Route::patch('/{dataGrid}', [DataGridController::class, 'update'])->name('update.patch');
        Route::delete('/{dataGrid}', [DataGridController::class, 'destroy'])->name('destroy');
        Route::get('/{dataGrid}/logs', [DataGridController::class, 'logs']);

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
            Route::get('/{record}/logs', [DataGridRecordController::class, 'logs']);
        });

        Route::prefix('types')->name('types.')->group(function () {
            Route::get('/search', [DataGridTypeController::class, 'search'])->name('search');
            Route::post('/', [DataGridTypeController::class, 'store'])->name('store');
            Route::get('/{dataGridType}', [DataGridTypeController::class, 'show'])->name('show');
            Route::delete('/{dataGridType}', [DataGridTypeController::class, 'destroy'])->name('destroy');
        });
    });

    // Получение приглашений для текущего пользователя
    Route::get('invitations', [DataGridInvitationController::class, 'index']);
    // Принятие/отклонение приглашений
    Route::post('invitations/{token}/accept', [DataGridInvitationController::class, 'accept']);
    Route::post('invitations/{token}/decline', [DataGridInvitationController::class, 'decline']);

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::patch('/{user}', [UserController::class, 'update'])->name('patch');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });
});

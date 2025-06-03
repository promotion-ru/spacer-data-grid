<?php

//use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//// Публичные маршруты
//Route::post('/register', [AuthController::class, 'register']);
//Route::post('/login', [AuthController::class, 'login']);
//
//// Защищенные маршруты
//Route::middleware('auth:sanctum')->group(function () {
//    Route::get('/user', [AuthController::class, 'user']);
//    Route::post('/logout', [AuthController::class, 'logout']);
//});

// Тестовый маршрут
Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});

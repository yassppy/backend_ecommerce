<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - V1
|--------------------------------------------------------------------------
|
| Base URL: /api/v1
| Endpoints para el MVP de Ecommerce en Laravel 13
|
*/

Route::prefix('v1')->group(function () {

    // Rutas de Autenticación
    Route::prefix('auth')->group(function () {
        // Endpoints públicos
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login'])->name('login');

        // Endpoints privados (requieren Bearer Token con Sanctum)
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

});

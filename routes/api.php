<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ShippingAddressController;
use App\Http\Controllers\Api\V1\WishlistController;
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

    // Rutas de Perfil (requieren Bearer Token con Sanctum)
    Route::middleware('auth:sanctum')->prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::put('/password', [ProfileController::class, 'updatePassword']);
    });

    // Rutas de Carrito (requieren Bearer Token con Sanctum)
    Route::middleware('auth:sanctum')->prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/', [CartController::class, 'store']);
        Route::get('/summary', [CartController::class, 'summary']);
        Route::delete('/clear', [CartController::class, 'clear']);
        Route::put('/{id}', [CartController::class, 'update'])->whereNumber('id');
        Route::delete('/{id}', [CartController::class, 'destroy'])->whereNumber('id');
    });

    // Rutas de Wishlist / Favoritos (requieren Bearer Token con Sanctum)
    Route::middleware('auth:sanctum')->prefix('wishlist')->group(function () {
        Route::get('/', [WishlistController::class, 'index']);
        Route::post('/', [WishlistController::class, 'store']);
        Route::post('/toggle', [WishlistController::class, 'toggle']);
        Route::delete('/{product_id}', [WishlistController::class, 'destroy'])->whereNumber('product_id');
    });

    // Rutas de Direcciones de Envío (requieren Bearer Token con Sanctum)
    Route::middleware('auth:sanctum')->prefix('shipping-addresses')->group(function () {
        Route::get('/', [ShippingAddressController::class, 'index']);
        Route::post('/', [ShippingAddressController::class, 'store']);
        Route::get('/{id}', [ShippingAddressController::class, 'show'])->whereNumber('id');
        Route::put('/{id}', [ShippingAddressController::class, 'update'])->whereNumber('id');
        Route::delete('/{id}', [ShippingAddressController::class, 'destroy'])->whereNumber('id');
    });

    // Rutas públicas de Categorías
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/{slug}', [CategoryController::class, 'show']);
        Route::get('/{slug}/products', [CategoryController::class, 'products']);
    });

    // Rutas públicas de Marcas (Brands)
    Route::prefix('brands')->group(function () {
        Route::get('/', [BrandController::class, 'index']);
        Route::get('/{slug}', [BrandController::class, 'show']);
        Route::get('/{slug}/products', [BrandController::class, 'products']);
    });

    // Rutas públicas de Productos
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/featured', [ProductController::class, 'featured']);
        Route::get('/search', [ProductController::class, 'search']);
        Route::get('/{slug}', [ProductController::class, 'show']);
        Route::get('/{product_id}/reviews', [ProductController::class, 'reviews'])->whereNumber('product_id');
    });

});

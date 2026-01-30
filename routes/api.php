<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\ReportController;

use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\ProductPublicController;


//  Login & Register (public)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes
Route::middleware(['auth:sanctum', 'role:cashier'])->group(function () {
    // Cart main CRUD
    Route::apiResource('cart', CartController::class)
        ->only(['index', 'store', 'destroy']);
    // Cart action (non-resource)
    Route::delete('cart', [CartController::class, 'clear']);
    // Checkout
    Route::post('checkout', [CheckoutController::class, 'checkout']);
});

Route::middleware(['auth:sanctum', 'role:admin'])
    ->prefix('admin')
    ->group(function () {

        // Product Management
        Route::apiResource('products', ProductController::class);

        // Reports
        Route::get('/reports/summary', [ReportController::class, 'summary']);
        Route::get('/reports/top-products', [ReportController::class, 'topProducts']);
        Route::get('/reports/sales-by-date', [ReportController::class, 'salesByDate']);
    });


Route::middleware('auth:sanctum')
    ->get('/products', [ProductPublicController::class, 'index']);

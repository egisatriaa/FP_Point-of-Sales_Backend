<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Cashier\CheckoutController;
use App\Http\Controllers\Api\Cashier\CartController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\TransactionController;
use App\Http\Controllers\Api\ProductPublicController;
use App\Http\Controllers\Api\Cashier\KasirTransactionController;
use App\Http\Controllers\Api\TransactionReceiptController;
use App\Http\Controllers\Api\PublicReceiptController;
use App\Http\Controllers\Api\ReceiptPdfController;
use App\Http\Controllers\Api\PublicReceiptPdfController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Cashier\CashierDashboardController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Cashier\CategoryController as CashierCategoryController;

/*
|--------------------------------------------------------------------------
| Auth (Public)
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout']);

/*
|--------------------------------------------------------------------------
| Profile (Authenticated)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'update']);
    Route::get('/user/profile', [\App\Http\Controllers\Api\ProfileController::class, 'show']);
    Route::post('/user/change-password', [\App\Http\Controllers\Api\ProfileController::class, 'changePassword']);
});

/*
|--------------------------------------------------------------------------
| Public Receipt (Self Order)
|--------------------------------------------------------------------------
*/
Route::get('/receipt/{transaction_code}', [PublicReceiptController::class, 'show']);
Route::get('/receipt/{transaction_code}/pdf', [PublicReceiptPdfController::class, 'download']);
Route::get('/receipt/{transaction_code}/pdf-link', [PublicReceiptController::class, 'pdfLink']);

/*
|--------------------------------------------------------------------------
| Protected PDF (Cashier & Admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:cashier,admin'])->group(function () {
    // JSON receipt (view)
    Route::get(
        '/transactions/{id}/receipt',
        [TransactionReceiptController::class, 'show']
    );

    // PDF receipt (download)
    Route::get(
        '/transactions/{id}/receipt/pdf',
        [ReceiptPdfController::class, 'download']
    );
});

/*
|--------------------------------------------------------------------------
| Cashier
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:cashier'])->group(function () {
    Route::apiResource('cart', CartController::class)->only(['index', 'store', 'destroy']);
    Route::get('cashier/categories', [CashierCategoryController::class, 'index']);
    Route::delete('cart', [CartController::class, 'clear']);
    Route::post('checkout', [CheckoutController::class, 'store']);
    Route::get('/transactions/my', [KasirTransactionController::class, 'index']);
    Route::post('/transactions', [KasirTransactionController::class, 'store']);
    Route::get('/transactions/my/{id}', [KasirTransactionController::class, 'show']);

    // Cashier Dashboard (Personal Scope)
    Route::prefix('cashier/dashboard')->group(function () {
        Route::get('/stats', [CashierDashboardController::class, 'stats']);
        Route::get('/chart', [CashierDashboardController::class, 'chart']);
        Route::get('/recent-sales', [CashierDashboardController::class, 'recentSales']);
    });
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        Route::apiResource('products', ProductController::class);
        Route::apiResource('categories', AdminCategoryController::class);
        Route::get('/reports/summary', [ReportController::class, 'summary']);
        Route::get('/reports/top-products', [ReportController::class, 'topProducts']);
        Route::get('/reports/sales-by-date', [ReportController::class, 'salesByDate']);

        //Admin Transactions (READ ONLY)
        Route::get('/transactions', [TransactionController::class, 'index']);
        Route::get('/transactions/{id}', [TransactionController::class, 'show']);
        
        // Admin Dashboard (Global Scope)
        Route::prefix('dashboard')->group(function () {
            Route::get('/stats', [AdminDashboardController::class, 'stats']);
            Route::get('/chart', [AdminDashboardController::class, 'chart']);
            Route::get('/recent-sales', [AdminDashboardController::class, 'recentSales']);
        });
    });

/*
|--------------------------------------------------------------------------
| Authenticated Public Data
|--------------------------------------------------------------------------
*/

Route::get('/products', [ProductPublicController::class, 'index']);

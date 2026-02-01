<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Kasir\CheckoutController;
use App\Http\Controllers\Api\Kasir\CartController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\TransactionController;
use App\Http\Controllers\Api\ProductPublicController;
use App\Http\Controllers\Api\Kasir\KasirTransactionController;
use App\Http\Controllers\Api\TransactionReceiptController;
use App\Http\Controllers\Api\PublicReceiptController;
use App\Http\Controllers\Api\ReceiptPdfController;
use App\Http\Controllers\Api\PublicReceiptPdfController;

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
    Route::delete('cart', [CartController::class, 'clear']);
    Route::post('checkout', [CheckoutController::class, 'checkout']);
    Route::get('/transactions/my', [KasirTransactionController::class, 'index']);
    Route::get('/transactions/my/{id}', [KasirTransactionController::class, 'show']);
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
        Route::get('/reports/summary', [ReportController::class, 'summary']);
        Route::get('/reports/top-products', [ReportController::class, 'topProducts']);
        Route::get('/reports/sales-by-date', [ReportController::class, 'salesByDate']);

        //Admin Transactions (READ ONLY)
        Route::get('/transactions', [TransactionController::class, 'index']);
        Route::get('/transactions/{id}', [TransactionController::class, 'show']);
    });

/*
|--------------------------------------------------------------------------
| Authenticated Public Data
|--------------------------------------------------------------------------
*/

Route::get('/products', [ProductPublicController::class, 'index']);

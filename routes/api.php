<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Transactions\TransactionController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\OfflineSaleController;

/*
|--------------------------------------------------------------------------
| POS API ROUTES (JSON ONLY)
|--------------------------------------------------------------------------
|
| All POS endpoints are under /api and protected by 'auth' middleware.
| Includes product search, barcode lookup, POS checkout, and offline sync.
|
*/

Route::prefix('api')->middleware(['auth'])->group(function () {

    // 🔎 SEARCH PRODUCTS
    Route::get('/products/search', [ProductController::class, 'search']);

    // 🔎 BARCODE LOOKUP
    Route::get('/products/barcode/{barcode}', [ProductController::class, 'searchByBarcode']);

    // 💰 POS CHECKOUT
    Route::post('/pos/checkout', [TransactionController::class, 'posCheckout']);

    // 🔄 OFFLINE SALES SYNC
    Route::post('/offline-sync', [OfflineSaleController::class, 'sync']);
});

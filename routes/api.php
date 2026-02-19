<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Transactions\TransactionController;
use App\Http\Controllers\Products\ProductController;

/*
|--------------------------------------------------------------------------
| POS API ROUTES (JSON ONLY)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    // 🔎 SEARCH PRODUCTS
    Route::get('/products/search', [ProductController::class,'search']);

    // 🔎 BARCODE LOOKUP
    Route::get('/products/barcode/{barcode}', [ProductController::class,'searchByBarcode']);

    // 💰 POS CHECKOUT
    Route::post('/pos/checkout', [TransactionController::class,'posCheckout']);

});

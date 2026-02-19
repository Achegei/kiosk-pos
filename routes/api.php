<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Transactions\TransactionController;

// Search products by name
Route::get('/products/search', [TransactionController::class, 'searchProduct']);

// Get product by barcode
Route::get('/products/barcode/{barcode}', [TransactionController::class, 'productByBarcode']);

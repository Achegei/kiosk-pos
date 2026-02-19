<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Users\UserController;

use App\Http\Controllers\Customers\CustomerController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\Transactions\TransactionController;
use App\Http\Controllers\Transactions\TransactionItemController;
use App\Http\Controllers\Inventories\InventoryController;

/*
|--------------------------------------------------------------------------
| ROOT REDIRECT
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| GUEST ROUTES (NOT LOGGED IN)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class,'showLogin'])->name('login');
    Route::post('/login', [AuthController::class,'login']);
    Route::get('/admin/login', [AuthController::class,'showLogin']);
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DEVICE ACTIVATION CHECK (FOR TABLET LICENSING)
    |--------------------------------------------------------------------------
    */
    Route::post('/device/check', [DeviceController::class,'check']);

    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */
    Route::post('/logout', [AuthController::class,'logout'])->name('logout');

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD (ALL ROLES)
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class,'dashboard'])
        ->middleware('role:super_admin,admin,supervisor,staff')
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | POS & DEVICE-LOCKED ROUTES
    |--------------------------------------------------------------------------
    | Only active devices can access POS pages
    */
    Route::middleware(['role:super_admin,admin,supervisor,staff','device'])->group(function () {
        // POS MAIN PAGE
        Route::get('/pos', [TransactionController::class,'pos'])->name('pos');
    });

    /*
    |--------------------------------------------------------------------------
    | LIVE SEARCH ROUTES (AJAX)
    |--------------------------------------------------------------------------
    | Accessible for all authenticated users
    */
    Route::get('/products/search', [ProductController::class,'search'])->name('products.search');
    Route::get('/products/barcode/{barcode}', [ProductController::class,'searchByBarcode'])->name('products.barcode');

    /*
    |--------------------------------------------------------------------------
    | SETTINGS (ADMIN / SUPERVISOR ONLY)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin,superadmin,supervisor')->group(function(){
        Route::get('/settings/low-stock', [SettingsController::class, 'editLowStock'])->name('settings.low_stock');
        Route::post('/settings/low-stock', [SettingsController::class, 'updateLowStock'])->name('settings.low_stock.update');
    });

    /*
    |--------------------------------------------------------------------------
    | CRUD RESOURCES (AUTHENTICATED USERS)
    |--------------------------------------------------------------------------
    */
    Route::resource('products', ProductController::class);
    Route::resource('customers', CustomerController::class);
    Route::resource('transactions', TransactionController::class);
    Route::resource('transaction-items', TransactionItemController::class);
    Route::resource('inventories', InventoryController::class);
});

/*
|--------------------------------------------------------------------------
| INVENTORY TOGGLE (OUTSIDE GROUP)
|--------------------------------------------------------------------------
*/
Route::patch('inventories/{inventory}/toggle', [InventoryController::class, 'toggle'])
    ->name('inventories.toggle');

/*
|--------------------------------------------------------------------------
| DEBUG ROUTE (SAFE)
|--------------------------------------------------------------------------
*/
Route::get('/test/products', function () {
    return \App\Models\Product::with('inventory')->get();
});

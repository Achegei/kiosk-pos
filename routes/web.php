<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\StaffController;
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
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/admin/login', [AuthController::class, 'showLogin']);

});
/// Register routes (open/close) - can be accessed by authenticated users, but handled via AJAX
Route::middleware('auth')->group(function () {
    // Open register via AJAX
    Route::post('/register/open', [RegisterController::class,'open'])->name('register.open');

    // Optional: normal open/close pages
    Route::get('/register/open-form', [RegisterController::class,'openForm'])->name('register.open.form');
    Route::get('/register/close-form', [RegisterController::class,'closeForm'])->name('register.close.form');
    Route::post('/register/close', [RegisterController::class,'close'])->name('register.close');
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    /* 
    --------------------------------------------------------------------------
    | STAFF MANAGEMENT (SUPER ADMIN ONLY)
    */
    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
    Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
    Route::get('/staff/{staff}/edit', [StaffController::class, 'edit'])->name('staff.edit');
    Route::put('/staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
    Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy');
    /*
    |--------------------------------------------------------------------------
    | DEVICE ACTIVATION CHECK (FOR TABLET LICENSING)
    |--------------------------------------------------------------------------
    */
    Route::post('/device/check', [DeviceController::class, 'check']);

    /*
    |--------------------------------------------------------------------------
    | POS CHECKOUT ROUTE
    |--------------------------------------------------------------------------
    */
    Route::post('/transactions/pos-checkout', [TransactionController::class, 'posCheckout'])
        ->name('transactions.pos_checkout');

    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD (ALL ROLES)
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])
        ->middleware('role:super_admin,admin,supervisor,staff')
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | POS & DEVICE-LOCKED ROUTES
    |--------------------------------------------------------------------------
    | Only active devices can access POS pages
    */
    Route::middleware(['role:super_admin,admin,supervisor,staff', 'device'])->group(function () {
        Route::get('/pos', [TransactionController::class, 'pos'])->name('pos');
    });

    /*
    |--------------------------------------------------------------------------
    | LIVE SEARCH ROUTES (AJAX)
    |--------------------------------------------------------------------------
    */
    Route::get('/products/search', [ProductController::class, 'search'])->name('products.search');
    Route::get('/products/barcode/{barcode}', [ProductController::class, 'searchByBarcode'])->name('products.barcode');

    /*
    |--------------------------------------------------------------------------
    | SETTINGS (ADMIN / SUPERVISOR ONLY)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin,superadmin,supervisor')->group(function () {
        Route::get('/settings/low-stock', [SettingsController::class, 'editLowStock'])->name('settings.low_stock');
        Route::post('/settings/low-stock', [SettingsController::class, 'updateLowStock'])->name('settings.low_stock.update');
    });

    // Quick add customer from POS
    Route::post('/pos/customer-quick-create', [CustomerController::class, 'quickStore'])->name('customers.quickStore');
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

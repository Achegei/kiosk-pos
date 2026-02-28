<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\CashMovementController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Customers\CustomerController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\Transactions\TransactionController;
use App\Http\Controllers\Transactions\TransactionItemController;
use App\Http\Controllers\Inventories\InventoryController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\ProformaQuotes\ProformaQuoteController;
use App\Http\Controllers\Admin\Invoices\InvoiceController;



use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\TenantController as SuperTenantController;
use App\Http\Controllers\Admin\Tenants\SettingsController as TenantsSettingsController;

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

/*
|--------------------------------------------------------------------------
| REGISTER ROUTES (OPEN / CLOSE) – AJAX HANDLED
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Open register via AJAX
    Route::post('/register/open', [RegisterController::class,'open'])->name('register.open');

    // Optional normal open/close forms
    Route::get('/register/open-form', [RegisterController::class,'openForm'])->name('register.open.form');
    Route::get('/register/close-form', [RegisterController::class,'closeForm'])->name('register.close.form');
    Route::post('/register/close', [RegisterController::class,'close'])->name('register.close');
    Route::get('/register/{id}/movements', [RegisterController::class, 'movements'])
    ->name('register.movements');
    Route::get('/register/{register}/totals', [RegisterController::class,'totals'])->name('register.totals');
    Route::get('/register/close/data',[RegisterController::class,'closeData']);
});

Route::prefix('admin/tenants/settings')->middleware(['web', 'auth'])->group(function () {
    // Show the edit form
    Route::get('/notes', [TenantsSettingsController::class, 'editDefaultNotes'])
        ->name('tenants.settings.notes');

    // Handle the form submission (your Blade form posts here)
    Route::post('/notes', [TenantsSettingsController::class, 'updateDefaultNotes'])
        ->name('tenant.settings.default_notes.update');
});
/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::patch('admin/invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.updateStatus');
    Route::get('admin/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::get('/quotes/{quote}/pdf', [ProformaQuoteController::class, 'downloadPdf'])
    ->name('quotes.pdf');
    Route::post('admin/invoices/pdf-preview', [InvoiceController::class, 'generatePdf'])->name('invoices.pdf.preview');

    /*
|--------------------------------------------------------------------------
| TENANT MANAGEMENT (SUPER ADMIN ONLY)
|--------------------------------------------------------------------------
*/
    Route::middleware('role:super_admin')->prefix('admin')->name('admin.')->group(function () {

    Route::get('/tenants', [TenantController::class,'index'])
        ->name('tenants.index');

    Route::get('/tenants/create',[TenantController::class,'create'])
        ->name('tenants.create');

    Route::post('/tenants',[TenantController::class,'store'])
        ->name('tenants.store');

    Route::get('/tenants/{tenant}', [TenantController::class,'show'])
        ->name('tenants.show');

    Route::get('/tenants/{tenant}/users',[TenantController::class,'users'])
        ->name('tenants.users');
    Route::get('/tenants/export/{tenant}', [SuperAdminDashboardController::class, 'export'])
    ->name('tenants.export');

    Route::get('/tenants/{tenant}/edit', [TenantController::class,'edit'])->name('tenants.edit');
    Route::put('/tenants/{tenant}', [TenantController::class,'update'])->name('tenants.update'); 
    Route::delete('/tenants/{tenant}', [TenantController::class,'destroy'])->name('tenants.destroy');

});

    /*
    |--------------------------------------------------------------------------
    | STAFF MANAGEMENT (SUPER ADMIN ONLY)
    |--------------------------------------------------------------------------
    */
    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
    Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
    Route::get('/staff/{staff}/edit', [StaffController::class, 'edit'])->name('staff.edit');
    Route::put('/staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
    Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy');

    /*
    |--------------------------------------------------------------------------
    | CASH MOVEMENTS (TILL ACTIONS)
    |--------------------------------------------------------------------------
    */
    Route::post('/cash-movements/store', [CashMovementController::class, 'store'])
        ->name('cash-movements.store');

    // 🔹 Summary of cash movements for a register session (used in close register modal)
    Route::get('/cash-movements/summary/{session}', function($session){
        return response()->json([
            'drops'=>CashMovement::where('register_session_id',$session)->where('type','drop')->sum('amount'),
            'expenses'=>CashMovement::where('register_session_id',$session)->where('type','expense')->sum('amount'),
            'payouts'=>CashMovement::where('register_session_id',$session)->where('type','payout')->sum('amount'),
            'deposits'=>CashMovement::where('register_session_id',$session)->where('type','deposit')->sum('amount'),
            'adjustments'=>CashMovement::where('register_session_id',$session)->where('type','adjustment')->sum('amount'),
        ]);
    });

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
    Route::get('/admin/dashboard', [SuperAdminDashboardController::class, 'index'])
    ->middleware('role:super_admin')
    ->name('superadmin.dashboard');
    
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])
        ->middleware('role:admin,supervisor,staff')
        ->name('dashboard');


    Route::get('/export-transactions', [TransactionController::class, 'export'])->name('transactions.export');

    /*
    |--------------------------------------------------------------------------
    | POS & DEVICE-LOCKED ROUTES
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:super_admin,admin,supervisor,staff', 'device'])->group(function () {
        Route::get('/pos', [TransactionController::class, 'pos'])->name('pos');
    });

    /*
    |--------------------------------------------------------------------------
    | LIVE SEARCH ROUTES (AJAX)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth'])->group(function() {
    Route::get('/products/search', [ProformaQuoteController::class, 'searchProducts']);
    Route::get('/products/barcode/{barcode}', [ProductController::class, 'searchByBarcode'])->name('products.barcode');
    Route::get('/customers/search', [ProformaQuoteController::class, 'searchCustomers'])
    ->name('customers.search');

    // POS Product Search (not ProformaQuote)
    Route::get('/fetch/products', [ProductController::class, 'search'])
        ->name('products.fetch');
    Route::get('/fetch/products/{barcode}', [ProductController::class, 'searchByBacode'])
        ->name('products.fetch.barcode');

    });

    
    /*
    |--------------------------------------------------------------------------
    | SETTINGS (ADMIN / SUPERVISOR ONLY)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin,superadmin,supervisor')->group(function () {
        Route::get('/settings/low-stock', [SettingsController::class, 'editLowStock'])->name('settings.low_stock');
        Route::post('/settings/low-stock', [SettingsController::class, 'updateLowStock'])->name('settings.low_stock.update');
    });

    Route::prefix('admin')->group(function () {
        // Proforma Quotes
        Route::post('quotes/{quote}/convert', [ProformaQuoteController::class, 'convert'])->name('quotes.convert');
        Route::resource('invoices', \App\Http\Controllers\Admin\Invoices\InvoiceController::class);
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
    Route::resource('quotes', ProformaQuoteController::class);
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

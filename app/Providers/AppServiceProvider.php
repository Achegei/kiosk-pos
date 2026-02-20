<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// ✅ IMPORT YOUR MODELS
use App\Models\Product;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Inventory;
use App\Models\User;
use App\Models\OfflineSale;
use App\Models\StockMovement;
use App\Models\Device;
use App\Models\Setting;
use App\Models\TransactionPayment;
use App\Observers\TransactionItemObserver;


// ✅ IMPORT OBSERVER
use App\Observers\GlobalAuditObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ✅ Attach observer to REAL models (NOT abstract Model)

        Product::observe(GlobalAuditObserver::class);
        Customer::observe(GlobalAuditObserver::class);
        Transaction::observe(GlobalAuditObserver::class);
        TransactionItem::observe(GlobalAuditObserver::class);
        Inventory::observe(GlobalAuditObserver::class);
        User::observe(GlobalAuditObserver::class);
        OfflineSale::observe(GlobalAuditObserver::class);
        StockMovement::observe(GlobalAuditObserver::class);
        Device::observe(GlobalAuditObserver::class);
        Setting::observe(GlobalAuditObserver::class);
        TransactionPayment::observe(GlobalAuditObserver::class);
        TransactionItem::observe(TransactionItemObserver::class);
    }
}

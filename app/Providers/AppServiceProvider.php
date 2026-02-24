<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// ✅ MODELS
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

// ✅ OBSERVERS
use App\Observers\GlobalAuditObserver;
use App\Observers\TransactionItemObserver;

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
        // -----------------------------
        // GLOBAL AUDIT OBSERVER
        // -----------------------------
        $auditModels = [
            Product::class,
            Customer::class,
            Transaction::class,
            Inventory::class,
            User::class,
            OfflineSale::class,
            StockMovement::class,
            Device::class,
            Setting::class,
            TransactionPayment::class,
        ];

        foreach ($auditModels as $model) {
            $model::observe(GlobalAuditObserver::class);
        }

        // -----------------------------
        // TRANSACTION ITEM OBSERVER
        // -----------------------------
        // Attach both audit + stock observer for TransactionItem
        TransactionItem::observe([GlobalAuditObserver::class, TransactionItemObserver::class]);

        // -----------------------------
        // OPTIONAL: GLOBAL TENANT SCOPES (if multi-tenant)
        // -----------------------------
        // Example: only load records for current user's tenant automatically
        if (auth()->check()) {
            $tenantId = auth()->user()->tenant_id ?? null;
            if ($tenantId) {
                Product::addGlobalScope('tenant', function ($builder) use ($tenantId) {
                    $builder->where('tenant_id', $tenantId);
                });
                Customer::addGlobalScope('tenant', function ($builder) use ($tenantId) {
                    $builder->where('tenant_id', $tenantId);
                });
                Inventory::addGlobalScope('tenant', function ($builder) use ($tenantId) {
                    $builder->where('tenant_id', $tenantId);
                });
                Transaction::addGlobalScope('tenant', function ($builder) use ($tenantId) {
                    $builder->where('tenant_id', $tenantId);
                });
                TransactionPayment::addGlobalScope('tenant', function ($builder) use ($tenantId) {
                    $builder->where('tenant_id', $tenantId);
                });
                OfflineSale::addGlobalScope('tenant', function ($builder) use ($tenantId) {
                    $builder->where('tenant_id', $tenantId);
                });
            }
        }
    }
}
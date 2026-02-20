<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Inventory;
use App\Models\Customer;
use App\Models\Device;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Role-based dashboard
     */
    public function dashboard()
    {
        $user = auth()->user();

        // ===== DEVICE AUTO REGISTER (SAFE INSERT) =====
        $uuid = request()->header('X-DEVICE-ID') ?? request()->input('device_uuid');

        if ($uuid) {
            Device::firstOrCreate(
                ['device_uuid' => $uuid],
                [
                    'device_name' => request()->userAgent(),
                    'license_expires_at' => Carbon::now()->addDays(7)
                ]
            );
        }
        // ===== END DEVICE BLOCK =====

        $today = Carbon::today();
        $data = [];

        // ------------------------
        // Low Stock Threshold (global)
        // ------------------------
        $lowStockThreshold = (int) setting('low_stock_threshold', 10);

        // ------------------------
        // PRODUCTS & CUSTOMERS
        // ------------------------
        if (in_array($user->role, ['staff', 'supervisor', 'admin', 'super_admin'])) {
            $data['products'] = Product::orderBy('name')->get();

            // Low-stock products (adjusted for pending offline sales)
            $data['lowStock'] = Inventory::with('product')->get()->filter(function($inventory) use ($lowStockThreshold) {

                // Count pending offline sales for this product
                $pendingOffline = DB::table('offline_sales')
                    ->where('synced', 0)
                    ->whereJsonContains('sale_data->products', ['product_id' => $inventory->product_id])
                    ->sum(DB::raw("JSON_EXTRACT(sale_data, '$.quantity')"));

                // Adjusted quantity
                $availableQty = $inventory->quantity - $pendingOffline;

                // Return true if below threshold
                return $availableQty <= $lowStockThreshold;
            });

            $data['lowStockThreshold'] = $lowStockThreshold;

            $data['customers'] = Customer::orderBy('name')->get();
            $data['activeCustomers'] = $data['customers']->count();
        } else {
            $data['products'] = collect();
            $data['lowStock'] = collect();
            $data['lowStockThreshold'] = $lowStockThreshold;
            $data['customers'] = collect();
            $data['activeCustomers'] = 0;
        }

        // ------------------------
        // POS / STAFF DATA
        // ------------------------
        if (in_array($user->role, ['staff', 'supervisor', 'admin', 'super_admin'])) {
            $baseQuery = Transaction::whereDate('transactions.created_at', $today);

            if ($user->role === 'staff') {
                $baseQuery = $baseQuery->where('staff_id', $user->id);
            }

            $data['todaySales'] = (clone $baseQuery)->where('status', 'Paid')->sum('total_amount');
            $data['todayCredit'] = (clone $baseQuery)->where('status', 'On Credit')->sum('total_amount');
            $data['totalTransactions'] = (clone $baseQuery)->count();
        } else {
            $data['todaySales'] = 0;
            $data['todayCredit'] = 0;
            $data['totalTransactions'] = 0;
        }

        // ------------------------
        // OFFLINE CART ITEMS (STAFF ONLY)
        // ------------------------
        if ($user->role === 'staff') {

            $offlineCartItems = DB::table('offline_sales')
                ->where('user_id', $user->id)
                ->where('synced', 0)
                ->get()
                ->map(function($sale) {
                    $saleData = json_decode($sale->sale_data, true);
                    return $saleData['products'] ?? [];
                })->flatten(1);

            $data['offlineCartItems'] = $offlineCartItems;

        } else {
            $data['offlineCartItems'] = collect();
        }

        // ------------------------
        // ADMIN / SUPER ADMIN DATA
        // ------------------------
        if (in_array($user->role, ['admin', 'super_admin'])) {
            $data['dailySales'] = $data['todaySales'];
            $data['dailyCreditSales'] = $data['todayCredit'];
            $data['totalRevenue'] = Transaction::sum('total_amount');
            $data['moneyIn'] = $data['todaySales'] + $data['todayCredit'];

            $data['moneyOut'] = DB::table('inventories')
                ->join('products', 'inventories.product_id', '=', 'products.id')
                ->sum(DB::raw('inventories.quantity * COALESCE(products.cost_price,0)'));

            $data['profitToday'] = Transaction::join('transaction_items', 'transactions.id', '=', 'transaction_items.transaction_id')
                ->join('products', 'transaction_items.product_id', '=', 'products.id')
                ->whereDate('transactions.created_at', $today)
                ->where('transactions.status', 'Paid')
                ->sum(DB::raw('(transaction_items.quantity * products.price) - (transaction_items.quantity * COALESCE(products.cost_price,0))'));

            $data['recentTransactions'] = Transaction::with(['customer', 'items.product'])
                ->latest()
                ->take(10)
                ->get();

            // ===== Active devices & unsynced offline sales =====
            $data['activeDevices'] = Device::all()->map(function($device) {
                $unsyncedSales = DB::table('offline_sales')
                    ->where('device_uuid', $device->device_uuid)
                    ->where('synced', 0)
                    ->count();

                return [
                    'uuid' => $device->device_uuid,
                    'name' => $device->device_name,
                    'license_expires_at' => $device->license_expires_at ? Carbon::parse($device->license_expires_at) : null,
                    'unsynced_sales' => $unsyncedSales,
                ];
            });

        } else {
            $data['dailySales'] = 0;
            $data['dailyCreditSales'] = 0;
            $data['totalRevenue'] = 0;
            $data['moneyIn'] = 0;
            $data['moneyOut'] = 0;
            $data['profitToday'] = 0;
            $data['recentTransactions'] = collect();
            $data['activeDevices'] = collect();
        }

        // ------------------------
        // ROLE-BASED VIEW
        // ------------------------
        return match($user->role) {
            'staff' => view('dashboard.pos', $data),
            'supervisor' => view('dashboard.supervisor', $data),
            'admin' => view('dashboard.admin', $data),
            'super_admin' => view('dashboard.super_admin', $data),
            default => abort(403, 'Unauthorized'),
        };
    }
}

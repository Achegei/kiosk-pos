<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Inventory;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Device;

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

        if($uuid){
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
        if (in_array($user->role, ['supervisor', 'admin', 'super_admin'])) {
            // All products for admin/supervisor
            $data['products'] = Product::orderBy('name')->get();

            // Low-stock products (based on threshold)
            $data['lowStock'] = Inventory::with('product')
                ->where('quantity', '<=', $lowStockThreshold)
                ->get();

            // Pass threshold to view
            $data['lowStockThreshold'] = $lowStockThreshold;

            // All customers
            $data['customers'] = Customer::orderBy('name')->get();
            $data['activeCustomers'] = $data['customers']->count();
        } else {
            // Ensure variables exist for staff views
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

            // Base query for today
            $baseQuery = Transaction::whereDate('transactions.created_at', $today);

            // Staff: filter to their own transactions
            if ($user->role === 'staff') {
                $baseQuery = $baseQuery->where('staff_id', $user->id);
            }

            // Sales and credits
            $data['todaySales'] = (clone $baseQuery)->where('status', 'Paid')->sum('total_amount');
            $data['todayCredit'] = (clone $baseQuery)->where('status', 'On Credit')->sum('total_amount');
            $data['totalTransactions'] = (clone $baseQuery)->count();
        } else {
            $data['todaySales'] = 0;
            $data['todayCredit'] = 0;
            $data['totalTransactions'] = 0;
        }

        // ------------------------
        // ADMIN / SUPER ADMIN DATA
        // ------------------------
        if (in_array($user->role, ['admin', 'super_admin'])) {

            // Daily sales & credit
            $data['dailySales'] = $data['todaySales'];
            $data['dailyCreditSales'] = $data['todayCredit'];

            // Total revenue
            $data['totalRevenue'] = Transaction::sum('total_amount');

            // Total cash-in today
            $data['moneyIn'] = $data['todaySales'] + $data['todayCredit'];

            // Total cost of all products in stock
            $data['moneyOut'] = DB::table('inventories')
                ->join('products', 'inventories.product_id', '=', 'products.id')
                ->sum(DB::raw('inventories.quantity * COALESCE(products.cost_price,0)'));

            // Profit for today
            $data['profitToday'] = Transaction::join('transaction_items', 'transactions.id', '=', 'transaction_items.transaction_id')
                ->join('products', 'transaction_items.product_id', '=', 'products.id')
                ->whereDate('transactions.created_at', $today)
                ->where('transactions.status', 'Paid')
                ->sum(DB::raw('(transaction_items.quantity * products.price) - (transaction_items.quantity * COALESCE(products.cost_price,0))'));

            // Latest 10 transactions
            $data['recentTransactions'] = Transaction::with(['customer', 'items.product'])
                ->latest()
                ->take(10)
                ->get();
        } else {
            $data['dailySales'] = 0;
            $data['dailyCreditSales'] = 0;
            $data['totalRevenue'] = 0;
            $data['moneyIn'] = 0;
            $data['moneyOut'] = 0;
            $data['profitToday'] = 0;
            $data['recentTransactions'] = collect();
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

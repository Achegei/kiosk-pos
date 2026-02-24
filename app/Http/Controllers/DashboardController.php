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
        if (!$user) abort(403);

        $tenantId = $user->tenant_id;

        // superadmin bypass
        $tenantFilter = fn($query) =>
            $user->role === 'super_admin'
                ? $query
                : $query->where('tenant_id', $tenantId);

        // ===== DEVICE REGISTER (TENANT SAFE)
        $uuid = request()->header('X-DEVICE-ID') ?? request()->input('device_uuid');

        // ✅ FIX: do NOT auto-register device for super_admin
        if ($uuid && $user->role !== 'super_admin') {
            Device::firstOrCreate(
                [
                    'device_uuid' => $uuid,
                    'tenant_id' => $tenantId
                ],
                [
                    'device_name' => request()->userAgent(),
                    'license_expires_at' => now()->addDays(7)
                ]
            );
        }

        $today = now()->startOfDay();
        $data = [];

        $lowStockThreshold = (int) setting('low_stock_threshold', 10);

        // ---------------- PRODUCTS ----------------
        if (in_array($user->role, ['staff', 'supervisor', 'admin', 'super_admin'])) {

            $data['products'] = $tenantFilter(Product::orderBy('name'))->get();
            $inventories = $tenantFilter(Inventory::with('product'))->get();

            $data['lowStock'] = $inventories->filter(fn($inv) => $inv->quantity <= $lowStockThreshold);

            $customers = $tenantFilter(Customer::orderBy('name'))->get();
            $data['customers'] = $customers;
            $data['activeCustomers'] = $customers->count();
            $data['lowStockThreshold'] = $lowStockThreshold;

        } else {

            $data['products'] = collect();
            $data['customers'] = collect();
            $data['lowStock'] = collect();
            $data['activeCustomers'] = 0;
            $data['lowStockThreshold'] = $lowStockThreshold;
        }

        // ---------------- SALES ----------------
        $baseQuery = Transaction::whereDate('created_at', $today);

        // ✅ super_admin should see ALL tenants
        if ($user->role !== 'super_admin') {
            $baseQuery->where('tenant_id', $tenantId);
        }

        if ($user->role === 'staff') {
            $baseQuery->where('staff_id', $user->id);
        }

        $data['todaySales'] = (clone $baseQuery)->where('status', 'Paid')->sum('total_amount');
        $data['todayCredit'] = (clone $baseQuery)->where('status', 'On Credit')->sum('total_amount');

        $data['dailySales'] = $data['todaySales'];
        $data['dailyCreditSales'] = $data['todayCredit'];
        $data['totalTransactions'] = (clone $baseQuery)->count();

        // ---------------- ADMIN GLOBAL DATA ----------------
        if (in_array($user->role, ['admin', 'super_admin'])) {

            $revQuery = Transaction::query();

            // ✅ allow super_admin to see platform revenue
            if ($user->role !== 'super_admin') {
                $revQuery->where('tenant_id', $tenantId);
            }

            $data['totalRevenue'] = $revQuery->sum('total_amount');

            $data['recentTransactions'] =
                $tenantFilter(
                    Transaction::with(['customer', 'items.product'])
                        ->latest()
                        ->take(10)
                )->get();

            // ✅ FIX: super_admin gets ALL devices platform-wide
            $data['activeDevices'] =
                $user->role === 'super_admin'
                    ? Device::with('tenant')->latest()->get()
                    : $tenantFilter(Device::query())->get();

        } else {

            $data['totalRevenue'] = 0;
            $data['recentTransactions'] = collect();
            $data['activeDevices'] = collect();
        }

        // ---------------- REGISTER ----------------
        $openRegister = $user->openRegister;
        $data['openRegister'] = $openRegister;

        if ($openRegister) {

            $tx = $openRegister->transactions();

            // super_admin shouldn't filter register by tenant
            if ($user->role !== 'super_admin') {
                $tx->where('tenant_id', $tenantId);
            }

            $data['cashSales'] = (clone $tx)->where('payment_method', 'Cash')->sum('total_amount');
            $data['mpesaSales'] = (clone $tx)->where('payment_method', 'Mpesa')->sum('total_amount');
            $data['creditSales'] = (clone $tx)->where('payment_method', 'Credit')->sum('total_amount');

            $mov = $openRegister->cashMovementsSummary($user->role !== 'super_admin' ? $tenantId : null);

            $data['drops'] = $mov['drop'] ?? 0;
            $data['expenses'] = $mov['expense'] ?? 0;
            $data['payouts'] = $mov['payout'] ?? 0;
            $data['deposits'] = $mov['deposit'] ?? 0;
            $data['adjustments'] = $mov['adjustment'] ?? 0;

            $data['moneyIn'] = $data['drops'] + $data['deposits'] + $data['adjustments'];
            $data['moneyOut'] = $data['drops'] + $data['expenses'] + $data['payouts'];

            $data['expectedCash'] = $openRegister->calculateExpectedCash($user->role !== 'super_admin' ? $tenantId : null);

        } else {

            foreach ([
                'cashSales','mpesaSales','creditSales','drops','expenses',
                'payouts','deposits','adjustments','expectedCash','moneyIn','moneyOut'
            ] as $k)
                $data[$k] = 0;
        }

        // ---------------- RETURN VIEW ----------------
        return match ($user->role) {
            'staff' => view('dashboard.pos', $data),
            'supervisor' => view('dashboard.supervisor', $data),
            'admin' => view('dashboard.admin', $data),
            'super_admin' => view('dashboard.super_admin', $data),
            default => abort(403)
        };
    }
}
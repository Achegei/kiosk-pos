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
use App\Models\RegisterSession;
use App\Models\CashMovement;

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

        // Superadmin bypass
        $tenantFilter = fn($query) =>
            $user->role === 'super_admin'
                ? $query
                : $query->where('tenant_id', $tenantId);

        // ===== DEVICE REGISTER (TENANT SAFE)
        $uuid = request()->header('X-DEVICE-ID') ?? request()->input('device_uuid');
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
        if ($user->role !== 'super_admin') $baseQuery->where('tenant_id', $tenantId);
        if ($user->role === 'staff') $baseQuery->where('staff_id', $user->id);

        $data['dailySales'] = (clone $baseQuery)->where('status', 'Paid')->sum('total_amount');
        $data['dailyCreditSales'] = (clone $baseQuery)->where('status', 'On Credit')->sum('total_amount');
        $data['totalTransactions'] = (clone $baseQuery)->count();

        // ---------------- ADMIN GLOBAL DATA ----------------
        if (in_array($user->role, ['admin', 'super_admin'])) {
            $revQuery = Transaction::query();
            if ($user->role !== 'super_admin') $revQuery->where('tenant_id', $tenantId);
            $data['totalRevenue'] = $revQuery->sum('total_amount');

            $data['recentTransactions'] = $tenantFilter(
                Transaction::with(['customer', 'items.product'])->latest()->take(10)
            )->get();

            $data['activeDevices'] =
                $user->role === 'super_admin'
                    ? Device::with('tenant')->latest()->get()
                    : $tenantFilter(Device::query())->get();
        } else {
            $data['totalRevenue'] = 0;
            $data['recentTransactions'] = collect();
            $data['activeDevices'] = collect();
        }

        // ---------------- REGISTER / CASH MOVEMENTS ----------------
        if (!in_array($user->role, ['admin', 'super_admin'])) {
            // Staff & supervisor: only open registers
            $openRegisters = RegisterSession::when($user->role !== 'super_admin', function ($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                })
                ->whereNull('closed_at')
                ->get();

            $data['openRegister'] = $openRegisters->first();
            $data['openRegisters'] = $openRegisters;

            if ($openRegisters->isNotEmpty()) {
                $registerIds = $openRegisters->pluck('id')->toArray();

                $tx = Transaction::whereIn('register_session_id', $registerIds);

                $data['cashSales']   = (clone $tx)->where('payment_method', 'Cash')->sum('total_amount');
                $data['mpesaSales']  = (clone $tx)->where('payment_method', 'Mpesa')->sum('total_amount');
                $data['creditSales'] = (clone $tx)->where('payment_method', 'Credit')->sum('total_amount');

                $mov = CashMovement::whereIn('register_session_id', $registerIds)
                    ->selectRaw('type, SUM(amount) as total')
                    ->groupBy('type')
                    ->pluck('total', 'type')
                    ->toArray();

                $data['drops']       = $mov['drop'] ?? 0;
                $data['expenses']    = $mov['expense'] ?? 0;
                $data['payouts']     = $mov['payout'] ?? 0;
                $data['deposits']    = $mov['deposit'] ?? 0;
                $data['adjustments'] = $mov['adjustment'] ?? 0;

                $data['moneyIn']  = $data['deposits'] + $data['adjustments'];
                $data['moneyOut'] = $data['drops'] + $data['expenses'] + $data['payouts'];
            } else {
                foreach ([
                    'cashSales','mpesaSales','creditSales','drops','expenses',
                    'payouts','deposits','adjustments','moneyIn','moneyOut'
                ] as $k) {
                    $data[$k] = 0;
                }
            }
        } else {
            // Admin / super_admin: all cash movements for today
            $data['openRegister'] = null;
            $data['openRegisters'] = collect();

            $mov = CashMovement::whereDate('created_at', $today)
                ->selectRaw('type, SUM(amount) as total')
                ->groupBy('type')
                ->pluck('total', 'type')
                ->toArray();

            $data['drops']       = $mov['drop'] ?? 0;
            $data['expenses']    = $mov['expense'] ?? 0;
            $data['payouts']     = $mov['payout'] ?? 0;
            $data['deposits']    = $mov['deposit'] ?? 0;
            $data['adjustments'] = $mov['adjustment'] ?? 0;

            $data['moneyIn']  = $data['deposits'] + $data['adjustments'];
            $data['moneyOut'] = $data['drops'] + $data['expenses'] + $data['payouts'];

            $data['cashSales']   = Transaction::whereDate('created_at', $today)
                                    ->where('payment_method', 'Cash')
                                    ->sum('total_amount');
            $data['mpesaSales']  = Transaction::whereDate('created_at', $today)
                                    ->where('payment_method', 'Mpesa')
                                    ->sum('total_amount');
            $data['creditSales'] = Transaction::whereDate('created_at', $today)
                                    ->where('payment_method', 'Credit')
                                    ->sum('total_amount');
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
<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Transaction;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
        {
            try {
                $tz = 'Africa/Nairobi';

                $todayStart = Carbon::now($tz)->startOfDay()->setTimezone('UTC');
                $todayEnd   = Carbon::now($tz)->endOfDay()->setTimezone('UTC');

                $monthStart = Carbon::now($tz)->startOfMonth()->setTimezone('UTC');
                $monthEnd   = Carbon::now($tz)->endOfMonth()->setTimezone('UTC');

                $tenants = Tenant::query()
                    ->withCount('staff')
                    ->withCount([
                        'transactions as today_transactions' => fn($q) =>
                            $q->whereBetween('created_at', [$todayStart, $todayEnd]),

                        'transactions as month_transactions' => fn($q) =>
                            $q->whereBetween('created_at', [$monthStart, $monthEnd]),
                    ])
                    ->withSum([
                        'transactions as today_revenue' => fn($q) =>
                            $q->whereBetween('created_at', [$todayStart, $todayEnd]),

                        'transactions as month_revenue' => fn($q) =>
                            $q->whereBetween('created_at', [$monthStart, $monthEnd]),
                    ], 'total_amount')
                    ->orderBy('name')
                    ->paginate(50);

                $activeTenantsToday = $tenants->where('today_transactions', '>', 0)->count();
                $monthlyRevenue = $tenants->sum(fn($t) => $t->month_revenue ?? 0);

                return view('superadmin.dashboard', compact(
                    'tenants',
                    'activeTenantsToday',
                    'monthlyRevenue'
                ));

            } catch (\Throwable $e) {
                \Log::channel('superadmin')->error('Failed to load SuperAdmin dashboard', [
                    'user_id' => auth()->id() ?? null,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return back()->withErrors('Unable to load dashboard. Please try again later.');
            }
        }

    // CSV EXPORT
    public function export(Tenant $tenant): StreamedResponse
    {
        try {
            $filename = "tenant_{$tenant->id}_transactions.csv";

            return response()->streamDownload(function () use ($tenant) {
                $handle = fopen('php://output', 'w');

                fputcsv($handle, [
                    'Transaction ID',
                    'Customer',
                    'Payment Method',
                    'Status',
                    'Total Amount',
                    'Date'
                ]);

                Transaction::with('customer')
                    ->where('tenant_id', $tenant->id)
                    ->orderByDesc('created_at')
                    ->chunk(500, function ($transactions) use ($handle) {
                        foreach ($transactions as $tx) {
                            fputcsv($handle, [
                                $tx->id,
                                optional($tx->customer)->name ?? 'Walk-in',
                                $tx->payment_method,
                                $tx->status,
                                number_format($tx->total_amount, 2),
                                $tx->created_at->format('Y-m-d H:i:s')
                            ]);
                        }
                    });

                fclose($handle);
            }, $filename);

        } catch (\Throwable $e) {
            \Log::channel('superadmin')->error('Failed to export tenant transactions', [
                'tenant_id' => $tenant->id,
                'user_id'   => auth()->id() ?? null,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return back()->withErrors('Unable to export transactions. Please try again later.');
        }
    }
}
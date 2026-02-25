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
        $tz = 'Africa/Nairobi';

        // TODAY / MONTH UTC RANGE
        $todayStart = Carbon::now($tz)->startOfDay()->setTimezone('UTC');
        $todayEnd   = Carbon::now($tz)->endOfDay()->setTimezone('UTC');

        $monthStart = Carbon::now($tz)->startOfMonth()->setTimezone('UTC');
        $monthEnd   = Carbon::now($tz)->endOfMonth()->setTimezone('UTC');

        // LOAD TENANTS WITH TRANSACTION COUNTS & REVENUE
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

        // ACTIVE TENANTS TODAY
        $activeTenantsToday = $tenants->where('today_transactions', '>', 0)->count();

        // TOTAL MONTHLY REVENUE
        $monthlyRevenue = $tenants->sum(fn($t) => $t->month_revenue ?? 0);

        return view('superadmin.dashboard', compact(
            'tenants',
            'activeTenantsToday',
            'monthlyRevenue'
        ));
    }

    // CSV EXPORT
    public function export(Tenant $tenant): StreamedResponse
    {
        $filename = "tenant_{$tenant->id}_transactions.csv";

        return response()->streamDownload(function () use ($tenant) {

            $handle = fopen('php://output', 'w');

            // CSV HEADER
            fputcsv($handle, [
                'Transaction ID',
                'Customer',
                'Payment Method',
                'Status',
                'Total Amount',
                'Date'
            ]);

            // Load transactions in chunks
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
    }
}
<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Inventory;
use Maatwebsite\Excel\Facades\Excel;
use PDF;
use App\Exports\LowStockExport;
use App\Exports\RecentTransactionsExport;
use App\Exports\DailySalesExport;

class ReportsController extends Controller
{
    public function __construct()
    {
        // Only authenticated tenant users with active subscription
        //$this->middleware(['auth', 'tenant.subscription']);
    }

    /**
     * ===========================
     * LOW STOCK
     * ===========================
     */

    // Preview page
    public function lowStockPreview()
    {
        $tenantId = auth()->user()->tenant_id;

        // Fetch products with low stock (adjust the threshold as needed)
        $lowStock = \App\Models\Inventory::where('tenant_id', $tenantId)
                        ->where('quantity', '<=', 5)
                        ->with('product') // eager load product details if needed
                        ->get();

        // Pass the variable to the view
        return view('admin.reports.low_stock', [
            'lowStock' => $lowStock,
        ]);
    }

    // Export as Excel, CSV, or PDF
    public function lowStockExport($format = 'xlsx')
    {
        $threshold = 5;
        $lowStock = Inventory::with('product')
            ->where('quantity', '<=', $threshold)
            ->get();

        if ($format === 'pdf') {
            $pdf = PDF::loadView('admin.reports.low_stock_pdf', compact('lowStock'));
            return $pdf->download('low_stock.pdf');
        }

        if ($format === 'csv' || $format === 'xlsx') {
            return Excel::download(new LowStockExport($lowStock), 'low_stock.' . $format);
        }

        return back()->with('error', 'Invalid format');
    }

    /**
     * ===========================
     * RECENT TRANSACTIONS
     * ===========================
     */

    // Preview page with pagination
    public function recentTransactions(Request $request, $format = null)
{
    // Get tenant ID from authenticated user (adjust if using multi-tenant package)
    $tenantId = auth()->user()->tenant_id;

    // Date filters
    $from = $request->input('from');
    $to = $request->input('to');

    // If format is provided, handle export
    if ($format) {

        if ($format === 'xlsx' || $format === 'csv') {
            return Excel::download(new RecentTransactionsExport($tenantId, $from, $to), "transactions.$format");
        } elseif ($format === 'pdf') {
            $transactions = Transaction::with('customer')
                ->where('tenant_id', $tenantId)
                ->when($from && $to, function($q) use ($from, $to) {
                    $q->whereBetween('created_at', ["$from 00:00:00", "$to 23:59:59"]);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            $pdf = Pdf::loadView('admin.reports.recent_transactions_pdf', compact('transactions'));
            return $pdf->download('transactions.pdf');
        } else {
            abort(404);
        }
    }

    // Otherwise, show Blade preview
    $transactions = Transaction::with('customer')
        ->where('tenant_id', $tenantId)
        ->when($from && $to, function($q) use ($from, $to) {
            $q->whereBetween('created_at', ["$from 00:00:00", "$to 23:59:59"]);
        })
        ->orderBy('created_at', 'desc')
        ->paginate(25);

    return view('admin.reports.recent_transactions', compact('transactions', 'from', 'to'));
}

    /**
     * ===========================
     * DAILY SALES
     * ===========================
     */

    // Preview page with filters and pagination
    public function dailySales(Request $request)
    {
        $type = $request->query('type', 'all'); // all, cash, credit
        $start = $request->query('start', now()->startOfMonth()->format('Y-m-d'));
        $end = $request->query('end', now()->format('Y-m-d'));

        $query = Transaction::where('tenant_id', auth()->user()->tenant_id)
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59']);

        if ($type === 'cash') {
            $query->where('status', 'Paid');
        } elseif ($type === 'credit') {
            $query->where('status', 'On Credit');
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('admin.reports.daily_sales', compact('transactions', 'type', 'start', 'end'));
    }

    // Export daily sales as Excel/CSV/PDF
    public function dailySalesExport(Request $request, $format = 'xlsx')
    {
        $typeFilter = $request->query('type', 'all');
        $start = $request->query('start', now()->startOfMonth()->format('Y-m-d'));
        $end = $request->query('end', now()->format('Y-m-d'));

        if ($format === 'pdf') {
            $transactions = Transaction::where('tenant_id', auth()->user()->tenant_id)
                ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
                ->when($typeFilter === 'cash', fn($q) => $q->where('status', 'Paid'))
                ->when($typeFilter === 'credit', fn($q) => $q->where('status', 'On Credit'))
                ->with('items.product', 'customer')
                ->get();

            $pdf = PDF::loadView('admin.reports.daily_sales_pdf', compact('transactions', 'typeFilter', 'start', 'end'));
            return $pdf->download('daily_sales_' . $start . '_to_' . $end . '.pdf');
        }

        return Excel::download(new DailySalesExport($start, $end, $typeFilter), 'daily_sales_' . $start . '_to_' . $end . '.' . $format);
    }
}
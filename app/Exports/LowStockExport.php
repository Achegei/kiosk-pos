<?php

namespace App\Exports;

use App\Models\Inventory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class LowStockExport implements FromView
{
    protected $lowStock;

    public function __construct($lowStock)
    {
        $this->lowStock = $lowStock;
    }

    public function view(): View
    {
        return view('admin.reports.low_stock_excel', [
            'lowStock' => $this->lowStock
        ]);
    }
}
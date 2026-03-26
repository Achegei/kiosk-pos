<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RecentTransactionsExport implements FromCollection, WithHeadings
{
    protected $tenantId;
    protected $start;
    protected $end;

    public function __construct($tenantId, $start = null, $end = null)
    {
        $this->tenantId = $tenantId;
        $this->start = $start;
        $this->end = $end;
    }

    public function collection()
    {
        $query = Transaction::where('tenant_id', $this->tenantId)->with('customer')->orderBy('created_at', 'desc');

        if ($this->start && $this->end) {
            $query->whereBetween('created_at', [$this->start.' 00:00:00', $this->end.' 23:59:59']);
        }

        return $query->get()->map(function($txn) {
            return [
                'Transaction #' => $txn->id,
                'Customer' => optional($txn->customer)->name ?? 'Unknown',
                'Date' => $txn->created_at->format('Y-m-d H:i'),
                'Total Amount' => $txn->total_amount,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Transaction #',
            'Customer',
            'Date',
            'Total Amount',
        ];
    }
}
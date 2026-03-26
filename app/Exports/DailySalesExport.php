<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DailySalesExport implements FromCollection, WithHeadings
{
    protected $start;
    protected $end;
    protected $type;

    public function __construct($start, $end, $type = 'all')
    {
        $this->start = $start;
        $this->end = $end;
        $this->type = $type;
    }

    public function collection()
    {
        $query = Transaction::with('items.product', 'customer')
            ->whereBetween('created_at', [$this->start . ' 00:00:00', $this->end . ' 23:59:59']);

        if ($this->type === 'cash') {
            $query->where('status', 'Paid');
        } elseif ($this->type === 'credit') {
            $query->where('status', 'On Credit');
        }

        return $query->get()->map(function($txn) {
            return [
                'Customer' => optional($txn->customer)->name ?? 'Walk-in',
                'Items' => $txn->items->map(fn($item) => optional($item->product)->name.' x'.$item->quantity)->implode(', '),
                'Total' => $txn->total_amount,
                'Status' => $txn->status,
                'Date' => $txn->created_at->format('d-M-Y H:i'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Customer',
            'Items',
            'Total',
            'Status',
            'Date',
        ];
    }
}
{{-- resources/views/admin/reports/daily_sales.blade.php --}}
@extends('layouts.admin')

@section('title', 'Daily Sales Report')

@section('content')
<div class="max-w-7xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Daily Sales Report</h1>

    {{-- Filter & Export Form --}}
    <div class="flex flex-col md:flex-row md:items-end gap-4 mb-6">
        {{-- Filter Form --}}
        <form method="GET" action="{{ route('reports.daily_sales') }}" class="flex gap-2 flex-wrap items-end">
            <div>
                <label class="block text-sm font-medium">Start Date</label>
                <input type="date" name="start" value="{{ $start }}" class="border px-2 py-1 rounded">
            </div>
            <div>
                <label class="block text-sm font-medium">End Date</label>
                <input type="date" name="end" value="{{ $end }}" class="border px-2 py-1 rounded">
            </div>
            <div>
                <label class="block text-sm font-medium">Type</label>
                <select name="type" class="border px-2 py-1 rounded">
                    <option value="all" {{ $type=='all'?'selected':'' }}>All</option>
                    <option value="cash" {{ $type=='cash'?'selected':'' }}>Cash</option>
                    <option value="credit" {{ $type=='credit'?'selected':'' }}>Credit</option>
                </select>
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Filter</button>
        </form>

        {{-- Export Buttons --}}
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('reports.daily_sales_export', ['format' => 'xlsx', 'start' => $start, 'end' => $end, 'type' => $type]) }}" 
               class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Export Excel</a>
            <a href="{{ route('reports.daily_sales_export', ['format' => 'csv', 'start' => $start, 'end' => $end, 'type' => $type]) }}" 
               class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700">Export CSV</a>
            <a href="{{ route('reports.daily_sales_export', ['format' => 'pdf', 'start' => $start, 'end' => $end, 'type' => $type]) }}" 
               class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Export PDF</a>
        </div>
    </div>

    {{-- Transactions Table --}}
    @if($transactions->isEmpty())
        <div class="bg-white border rounded p-6 text-center text-gray-400">
            No sales transactions found for this period
        </div>
    @else
        <table class="min-w-full bg-white border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-2 px-4 border text-left">Customer</th>
                    <th class="py-2 px-4 border text-left">Items</th>
                    <th class="py-2 px-4 border text-center">Total</th>
                    <th class="py-2 px-4 border text-center">Status</th>
                    <th class="py-2 px-4 border text-center">Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $txn)
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 border">{{ $txn->customer->name ?? 'Walk-in' }}</td>
                        <td class="py-2 px-4 border">
                            @foreach($txn->items as $item)
                                <div class="text-sm">{{ $item->product->name }} x{{ $item->quantity }}</div>
                            @endforeach
                        </td>
                        <td class="py-2 px-4 border text-center font-semibold">KSh {{ number_format($txn->total_amount, 2) }}</td>
                        <td class="py-2 px-4 border text-center">
                            @if($txn->status === 'Paid')
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-sm">Paid</span>
                            @elseif($txn->status === 'On Credit')
                                <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-sm">Credit</span>
                            @else
                                <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-sm">{{ $txn->status }}</span>
                            @endif
                        </td>
                        <td class="py-2 px-4 border text-center">{{ $txn->created_at->format('d-M-Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Pagination Links --}}
        <div class="mt-4">
            {{ $transactions->links('vendor.pagination.tailwind') }}
        </div>
    @endif
</div>
@endsection
{{-- resources/views/admin/reports/low_stock.blade.php --}}
@extends('layouts.admin')

@section('title', 'Low Stock Alerts')

@section('content')
<div class="max-w-7xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Low Stock Alerts</h1>

    @if($lowStock->isEmpty())
        <div class="bg-white border rounded p-6 text-center text-gray-400">
            All products have sufficient stock
        </div>
    @else
        {{-- Export Buttons --}}
        <div class="flex gap-2 mb-4">
            <a href="{{ route('reports.low_stock_export', ['format' => 'pdf']) }}" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Export PDF</a>
            <a href="{{ route('reports.low_stock_export', ['format' => 'xlsx']) }}" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Export Excel</a>
            <a href="{{ route('reports.low_stock_export', ['format' => 'csv']) }}" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700">Export CSV</a>
        </div>

        <table class="min-w-full bg-white border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-2 px-4 border text-left">Product</th>
                    <th class="py-2 px-4 border text-left">SKU / Barcode</th>
                    <th class="py-2 px-4 border text-center">Quantity</th>
                    <th class="py-2 px-4 border text-center">Threshold</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lowStock as $inventory)
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 border font-medium">{{ optional($inventory->product)->name ?? 'N/A' }}</td>
                        <td class="py-2 px-4 border">{{ optional($inventory->product)->sku ?? '-' }}</td>
                        <td class="py-2 px-4 border text-center font-semibold">
                            @if($inventory->quantity <= 0)
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-sm font-semibold">OUT</span>
                            @elseif($inventory->quantity <= 3)
                                <span class="bg-red-100 text-red-600 px-2 py-1 rounded text-sm font-semibold">{{ $inventory->quantity }}</span>
                            @else
                                <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-sm font-semibold">{{ $inventory->quantity }}</span>
                            @endif
                        </td>
                        <td class="py-2 px-4 border text-center text-gray-600">{{ $inventory->low_stock_threshold ?? 5 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
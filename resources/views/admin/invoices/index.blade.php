@extends('layouts.admin')

@section('title', 'Invoices')

@section('content')
<div class="p-6">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Invoices</h1>

        <a href="{{ route('invoices.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            + Create Invoice
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white shadow rounded overflow-x-auto">
        <table class="min-w-full border-collapse">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left">#</th>
                    <th class="p-3 text-left">Customer</th>
                    <th class="p-3 text-left">Staff</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left">Total</th>
                    <th class="p-3 text-left">Date</th>
                    <th class="p-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr class="border-t">
                        <td class="p-3 font-semibold">{{ str_pad($invoice->invoice_number, 5, '0', STR_PAD_LEFT) }}</td>
                        <td class="p-3">{{ $invoice->customer->name ?? 'Walk-in' }}</td>
                        <td class="p-3">{{ $invoice->staff->name ?? 'N/A' }}</td>
                        <td class="p-3">
                            @php
                                $statusColors = [
                                    'Paid' => 'bg-green-100 text-green-700',
                                    'Pending' => 'bg-yellow-100 text-yellow-700',
                                    'On Credit' => 'bg-blue-100 text-blue-700',
                                    'Returned' => 'bg-gray-200 text-gray-700',
                                    'Adjusted' => 'bg-purple-100 text-purple-700',
                                ];
                            @endphp
                            <span class="px-2 py-1 rounded text-sm {{ $statusColors[$invoice->status] ?? 'bg-red-100 text-red-700' }}">
                                {{ $invoice->status }}
                            </span>
                        </td>
                        <td class="p-3 font-semibold">KES {{ number_format($invoice->total_amount, 2) }}</td>
                        <td class="p-3">{{ $invoice->created_at->format('d M Y') }}</td>
                        <td class="p-3 flex flex-col gap-2">

                            <a href="{{ route('invoices.show', $invoice->id) }}" class="text-blue-600 hover:underline">
                                View
                            </a>

                            {{-- Return forms for each item --}}
                            @foreach($invoice->items as $item)
                                @php
                                    $remaining = ($item->quantity ?? 0) - ($item->returned_quantity ?? 0);
                                @endphp

                                @if($remaining > 0)
                                    <form action="{{ route('invoices.returnItem', $invoice->id) }}" method="POST" class="flex items-center gap-1">
                                        @csrf
                                        <input type="hidden" name="invoice_item_id" value="{{ $item->id }}">
                                        <input type="number"
                                               name="quantity"
                                               min="1"
                                               max="{{ $remaining }}"
                                               value="{{ $remaining }}"
                                               class="border rounded px-1 w-16 text-sm">
                                        <button type="submit"
                                                class="bg-red-600 text-white px-2 py-1 rounded text-xs hover:bg-red-700">
                                            Return: {{ $item->product?->name ?? 'Deleted Product' }}
                                        </button>
                                    </form>
                                @endif
                            @endforeach

                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-4 text-center text-gray-500">No invoices found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $invoices->links() }}
    </div>

</div>
@endsection
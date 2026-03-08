@extends('layouts.admin')

@section('title', 'View Purchase Order')

@php
$tenant = auth()->user()->tenant ?? null;
@endphp

@section('content')
<div class="container mx-auto py-6 max-w-6xl">

    {{-- Tenant Branding --}}
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold">
            {{ $tenant->name ?? 'My Business' }} - Purchase Order #{{ $purchaseOrder->id }}
        </h1>

        <a href="{{ route('purchase_orders.index') }}"
           class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
            Back
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('info'))
        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
            {{ session('info') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- PO Details Card --}}
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="grid md:grid-cols-3 gap-4">
            <div>
                <p class="text-gray-500 text-sm">Supplier</p>
                <p class="font-semibold">{{ $purchaseOrder->supplier->name ?? '-' }}</p>
            </div>

            <div>
                <p class="text-gray-500 text-sm">Status</p>
                @if($purchaseOrder->status == 'pending')
                    <span class="bg-yellow-200 text-yellow-800 px-3 py-1 rounded text-sm">Pending</span>
                @elseif($purchaseOrder->status == 'received')
                    <span class="bg-green-200 text-green-800 px-3 py-1 rounded text-sm">Received</span>
                @elseif($purchaseOrder->status == 'cancelled')
                    <span class="bg-red-200 text-red-800 px-3 py-1 rounded text-sm">Cancelled</span>
                @endif
            </div>

            <div>
                <p class="text-gray-500 text-sm">Created At</p>
                <p class="font-semibold">{{ $purchaseOrder->created_at->format('d M Y') }}</p>
            </div>
        </div>
    </div>

    {{-- Items Table with Partial Receiving --}}
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Purchase Items</h2>

        <form action="{{ route('purchase_orders.receive', $purchaseOrder) }}" method="POST" 
              onsubmit="return confirm('Receive selected quantities and update inventory?')">
            @csrf

            <div class="overflow-x-auto">
                <table class="w-full table-auto border-collapse border border-gray-200">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border px-4 py-2">Product</th>
                            <th class="border px-4 py-2">Ordered Qty</th>
                            <th class="border px-4 py-2">Received Qty</th>
                            <th class="border px-4 py-2">Receive Now</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseOrder->items as $item)
                            @php
                                $pending = $item->quantity - ($item->received_quantity ?? 0);
                            @endphp
                            <tr>
                                <td class="border px-4 py-2">{{ $item->product->name ?? '-' }}</td>
                                <td class="border px-4 py-2">{{ $item->quantity }}</td>
                                <td class="border px-4 py-2">{{ $item->received_quantity ?? 0 }}</td>
                                <td class="border px-4 py-2">
                                    @if($pending > 0)
                                        <input type="number" 
                                               name="items[{{ $item->id }}]" 
                                               min="0" 
                                               max="{{ $pending }}" 
                                               value="{{ $pending }}" 
                                               class="border px-2 py-1 w-20">
                                    @else
                                        <span class="text-green-600 font-semibold">Completed</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Receive Button --}}
            @if($purchaseOrder->status == 'pending' && $purchaseOrder->items->sum('received_quantity') < $purchaseOrder->items->sum('quantity'))
                <div class="mt-4">
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        Receive Stock
                    </button>
                </div>
            @endif
        </form>
    </div>

    {{-- Cancel PO Button --}}
    @if($purchaseOrder->status != 'cancelled')
        <form action="{{ route('purchase_orders.destroy', $purchaseOrder) }}"
              method="POST"
              onsubmit="return confirm('Cancel this Purchase Order?')">
            @csrf
            @method('DELETE')
            <button class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                Cancel PO
            </button>
        </form>
    @endif

</div>
@endsection
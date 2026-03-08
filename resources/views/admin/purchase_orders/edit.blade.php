@extends('layouts.admin')

@section('title', 'Edit Purchase Order')

@php
$tenant = auth()->user()->tenant ?? null;
@endphp

@section('content')
<div class="container mx-auto py-6 max-w-5xl">

    {{-- Tenant Branding --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold">
            {{ $tenant->name ?? 'My Business' }} - Edit Purchase Order #{{ $purchaseOrder->id }}
        </h1>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
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

    {{-- Edit Purchase Order --}}
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Edit Order Details</h2>

        <form action="{{ route('purchase_orders.update', $purchaseOrder) }}" method="POST">
            @csrf
            @method('PUT')

            @include('admin.purchase_orders._form')

            <button type="submit"
                class="bg-blue-600 text-white px-4 py-2 rounded mt-4 hover:bg-blue-700">
                Update Purchase Order
            </button>
        </form>
    </div>

    {{-- Adjust Received Quantities --}}
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-xl font-semibold mb-4">Adjust Received Quantities</h2>

        <form action="{{ route('purchase_orders.adjust_received', $purchaseOrder) }}" method="POST">
            @csrf

            <table class="w-full border border-gray-300 mb-4">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 border">Product</th>
                        <th class="px-4 py-2 border">Ordered</th>
                        <th class="px-4 py-2 border">Received</th>
                        <th class="px-4 py-2 border">Adjust To</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($purchaseOrder->items as $item)
                        <tr>
                            <td class="px-4 py-2 border">
                                {{ $item->product->name ?? 'Deleted Product' }}
                            </td>

                            <td class="px-4 py-2 border">
                                {{ $item->quantity }}
                            </td>

                            <td class="px-4 py-2 border">
                                {{ $item->received_quantity }}
                            </td>

                            <td class="px-4 py-2 border">
                                <input
                                    type="number"
                                    name="received_quantity[{{ $item->id }}]"
                                    value="{{ $item->received_quantity }}"
                                    min="0"
                                    max="{{ $item->quantity }}"
                                    class="border rounded px-2 py-1 w-24"
                                >
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="flex justify-between items-center">

                <p class="text-sm text-gray-500">
                    Adjusting quantities will automatically update inventory.
                </p>

                <button type="submit"
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Adjust Received Quantities
                </button>

            </div>

        </form>
    </div>

</div>
@endsection
@extends('layouts.admin')

@section('title', 'Create Purchase Order')

@php
    $tenant = auth()->user()->tenant;
@endphp

@section('content')
<div class="container mx-auto py-6 max-w-5xl">

    {{-- Tenant Branding --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold">
            {{ $tenant->name }} - Create Purchase Order
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

    <div class="bg-white shadow rounded-lg p-6">
        <form action="{{ route('purchase_orders.store') }}" method="POST">
            @csrf
            <div class="space-y-4">

                {{-- Supplier --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Supplier</label>
                    <select name="supplier_id" required class="mt-1 block w-full border rounded p-2">
                        <option value="">Select Supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}"
                                {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- PO Items --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Items</label>
                    <table class="w-full border table-auto" id="items-table">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-2 py-1">Product</th>
                                <th class="px-2 py-1">Quantity</th>
                                <th class="px-2 py-1">Price</th>
                                <th class="px-2 py-1">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(old('items'))
                                @foreach(old('items') as $i => $item)
                                    <tr>
                                        <td>
                                            <select name="items[{{ $i }}][product_id]" class="border rounded p-1 w-full" required>
                                                <option value="">Select Product</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}" {{ $item['product_id'] == $product->id ? 'selected' : '' }}>
                                                        {{ $product->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $i }}][quantity]" value="{{ $item['quantity'] }}" class="border rounded p-1 w-24" required min="1">
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $i }}][price]" value="{{ $item['price'] ?? '' }}" class="border rounded p-1 w-24" required step="0.01" min="0">
                                        </td>
                                        <td>
                                            <button type="button" class="remove-row bg-red-500 text-white px-2 py-1 rounded">Remove</button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>

                    <button type="button" id="add-row" class="mt-2 bg-blue-500 text-white px-3 py-1 rounded">Add Item</button>
                </div>

                <div>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Save Purchase Order</button>
                </div>
            </div>
        </form>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('items-table').getElementsByTagName('tbody')[0];
    const addRowBtn = document.getElementById('add-row');

    // Pre-render product options as JS
    const productOptions = `@foreach($products as $product)
        <option value="{{ $product->id }}">{{ $product->name }}</option>
    @endforeach`;

    addRowBtn.addEventListener('click', () => {
        const index = table.rows.length;
        const row = table.insertRow();
        row.innerHTML = `
            <td>
                <select name="items[${index}][product_id]" class="border rounded p-1 w-full" required>
                    <option value="">Select Product</option>
                    ${productOptions}
                </select>
            </td>
            <td>
                <input type="number" name="items[${index}][quantity]" class="border rounded p-1 w-24" required min="1">
            </td>
            <td>
                <input type="number" name="items[${index}][price]" class="border rounded p-1 w-24" required step="0.01" min="0">
            </td>
            <td>
                <button type="button" class="remove-row bg-red-500 text-white px-2 py-1 rounded">Remove</button>
            </td>
        `;
    });

    table.addEventListener('click', function(e) {
        if(e.target && e.target.matches('button.remove-row')) {
            e.target.closest('tr').remove();
        }
    });
});
</script>
@endsection
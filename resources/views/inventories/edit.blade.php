@extends('layouts.admin')

@section('title', 'Edit Inventory')
@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold mb-6">Edit Inventory</h1>

    @if ($errors->any())
        <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('inventories.update', $inventory->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="product_id" class="block text-gray-700 font-medium mb-2">Product</label>
            <select name="product_id" id="product_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                @foreach($products as $product)
                    <option value="{{ $product->id }}" {{ $product->id == $inventory->product_id ? 'selected' : '' }}>
                        {{ $product->name }} ({{ $product->barcode ?? 'N/A' }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label for="quantity" class="block text-gray-700 font-medium mb-2">Quantity</label>
            <input type="number" name="quantity" id="quantity" value="{{ old('quantity', $inventory->quantity) }}" min="0" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Update Inventory</button>
    </form>
</div>
@endsection

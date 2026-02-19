@extends('layouts.admin')

@section('content')
<div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold mb-6">Edit Product</h1>

    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('products.update', $product->id) }}" method="POST" class="bg-white p-6 rounded shadow-md">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block mb-1 font-medium">Product Name</label>
            <input type="text" name="name" value="{{ old('name', $product->name) }}" class="w-full border border-gray-300 px-3 py-2 rounded" required>
        </div>

        <div class="mb-4">
            <label class="block mb-1 font-medium">SKU</label>
            <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" class="w-full border border-gray-300 px-3 py-2 rounded" required>
        </div>

        <div class="mb-4">
            <label class="block mb-1 font-medium">Price (KSh)</label>
            <input type="number" name="price" value="{{ old('price', $product->price) }}" step="0.01" class="w-full border border-gray-300 px-3 py-2 rounded" required>
        </div>

        <div class="mb-4">
            <label class="block mb-1 font-medium">Quantity</label>
            <input type="number" name="quantity" value="{{ old('quantity', $product->quantity) }}" class="w-full border border-gray-300 px-3 py-2 rounded" required>
        </div>

        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Update Product</button>
        <a href="{{ route('products.index') }}" class="ml-3 text-gray-700 hover:underline">Cancel</a>
    </form>
</div>
@endsection

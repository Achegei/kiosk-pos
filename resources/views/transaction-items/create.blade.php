@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Add Transaction Item</h1>

    <form action="{{ route('transaction-items.store') }}" method="POST">
        @csrf
        <div class="mb-4">
            <label class="block mb-2 font-semibold">Transaction ID</label>
            <input type="number" name="transaction_id" class="border p-2 w-full rounded" required>
        </div>

        <div class="mb-4">
            <label class="block mb-2 font-semibold">Product</label>
            <select name="product_id" class="border p-2 w-full rounded" required>
                <option value="">-- Select Product --</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" data-price="{{ $product->price }}">
                        {{ $product->name }} (KSh {{ $product->price }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="block mb-2 font-semibold">Quantity</label>
            <input type="number" name="quantity" class="border p-2 w-full rounded" min="1" value="1" required>
        </div>

        <div class="mb-4">
            <label class="block mb-2 font-semibold">Price</label>
            <input type="number" name="price" class="border p-2 w-full rounded" min="0" value="0" required>
        </div>

        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Add Item</button>
    </form>
</div>
@endsection

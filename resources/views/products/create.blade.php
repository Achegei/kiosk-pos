@extends('layouts.admin')

@section('title', 'Add New Product')

@section('content')
<div class="min-h-screen bg-gray-100">

    <!-- Top Navbar -->
    <nav class="bg-white shadow-md px-6 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <a href="{{ route('dashboard') }}" class="text-xl font-bold text-blue-600">MyPOS</a>
            <a href="{{ route('products.index') }}" class="text-gray-700 hover:text-blue-600 transition">Products</a>
            <a href="{{ route('customers.index') }}" class="text-gray-700 hover:text-blue-600 transition">Customers</a>
            <a href="{{ route('transactions.index') }}" class="text-gray-700 hover:text-blue-600 transition">Transactions</a>
            <a href="{{ route('inventories.index') }}" class="text-gray-700 hover:text-blue-600 transition">Inventory</a>
        </div>
        <div class="flex items-center space-x-3">
            <span class="text-gray-600">Admin / Shopkeeper</span>
            <img src="https://i.pravatar.cc/32" class="rounded-full" alt="User">
        </div>
    </nav>

    <!-- Page Container -->
    <div class="max-w-4xl mx-auto py-10">

        <!-- Header Card -->
        <div class="bg-gradient-to-r from-blue-50 to-blue-100 p-6 rounded-lg shadow-md mb-8 border-l-4 border-blue-600">
            <h2 class="text-2xl font-bold text-gray-800">Add New Product</h2>
            <p class="text-gray-500 mt-1">Create a new product to manage your inventory and POS sales.</p>
        </div>

        <!-- Form Card -->
        <div class="bg-white shadow-lg rounded-lg p-8 border">
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg mb-6">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('products.store') }}" method="POST" class="space-y-6">
                @csrf

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Product Name</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        placeholder="e.g., Royco Masala" required>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">SKU / Barcode</label>
                    <input type="text" name="sku" value="{{ old('sku') }}"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        placeholder="e.g., 123456789" required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Price (KSh)</label>
                        <input type="number" name="price" value="{{ old('price') }}" step="0.01"
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            placeholder="e.g., 150.00" required>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Quantity</label>
                        <input type="number" name="quantity" value="{{ old('quantity') }}"
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            placeholder="e.g., 20" required>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex items-center justify-end space-x-4 mt-4">
                    <a href="{{ route('products.index') }}"
                       class="px-5 py-3 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 transition font-semibold">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition shadow">
                        Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('content')
<div class="container mx-auto py-8">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-semibold text-gray-900">Products</h1>
        <a href="{{ route('products.create') }}" 
           class="inline-flex items-center bg-gradient-to-r from-blue-500 to-indigo-500 hover:from-blue-600 hover:to-indigo-600 text-white px-5 py-2 rounded-lg shadow-md transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Add Product
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-6 shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price (KSh)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>

            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($products as $product)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $product->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $product->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $product->sku }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ number_format($product->price, 2) }}</td>

                        <td class="px-6 py-4 whitespace-nowrap flex space-x-2">
                            <a href="{{ route('products.edit', $product->id) }}"
                               class="flex items-center text-yellow-600 hover:text-yellow-800 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M17.414 2.586a2 2 0 010 2.828L8 14.828V18h3.172l9.414-9.414a2 2 0 00-2.828-2.828L8 14.828V18h3.172l6.242-6.242z"/>
                                </svg>
                                Edit
                            </a>

                            <form action="{{ route('products.destroy', $product->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="flex items-center text-red-600 hover:text-red-800 transition"
                                    onclick="return confirm('Are you sure you want to delete this product?')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none"
                                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Delete
                                </button>
                            </form>

                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-400">
                            No products found.
                        </td>
                    </tr>
                @endforelse
            </tbody>

        </table>
    </div>

</div>
@endsection

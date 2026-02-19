@extends('layouts.admin')

@section('title', 'Inventory')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Inventory</h1>
        <a href="{{ route('inventories.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Add Product
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white shadow rounded-lg overflow-hidden">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Product</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">SKU / Barcode</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Quantity</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Price</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inventories as $inventory)
                    <tr class="border-b">
                        <td class="px-6 py-4">{{ $inventory->product->name }}</td>
                        <td class="px-6 py-4">{{ $inventory->product->sku ?? 'N/A' }}</td>
                        <td class="px-6 py-4">{{ $inventory->quantity }}</td>
                        <td class="px-6 py-4">${{ number_format($inventory->product->price, 2) }}</td>
                        <td class="px-6 py-4">
                            @if($inventory->product->is_active)
                                <span class="text-green-600 font-semibold">Active</span>
                            @else
                                <span class="text-red-600 font-semibold">Out of Stock</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 flex items-center space-x-2">
                            <a href="{{ route('inventories.edit', $inventory->id) }}" class="text-blue-600 hover:underline">Edit</a>

                            <!-- Toggle Active / Out of Stock -->
                            <form action="{{ route('inventories.toggle', $inventory->id) }}" method="POST" class="inline-block">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                    class="text-purple-600 hover:underline"
                                    onclick="return confirm('Are you sure you want to {{ $inventory->product->is_active ? 'disable' : 'enable' }} this product?')">
                                    {{ $inventory->product->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>

                            <form action="{{ route('inventories.destroy', $inventory->id) }}" method="POST" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No inventory found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $inventories->links() }}
    </div>
</div>
@endsection

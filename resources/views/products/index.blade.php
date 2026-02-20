@extends('layouts.admin')

@section('title', 'Products')
@section('content')

<div class="overflow-x-auto">

    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-gray-800">Products</h1>
        <a href="{{ route('products.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 font-semibold">
            + Add Product
        </a>
    </div>

    <table class="min-w-full bg-white border">

        <thead class="bg-gray-100">
            <tr>
                <th class="py-2 px-4 border text-left">ID</th>
                <th class="py-2 px-4 border text-left">Name</th>
                <th class="py-2 px-4 border text-left">SKU</th>
                <th class="py-2 px-4 border text-center">Price (KSh)</th>
                <th class="py-2 px-4 border text-center">Status</th>
                <th class="py-2 px-4 border text-center">Actions</th>
            </tr>
        </thead>

        <tbody>

        @forelse($products as $product)

            <tr class="hover:bg-gray-50">

                <td class="py-2 px-4 border">{{ $product->id }}</td>
                <td class="py-2 px-4 border font-medium">{{ $product->name }}</td>
                <td class="py-2 px-4 border">{{ $product->sku ?? '-' }}</td>
                <td class="py-2 px-4 border text-center">
                    KSh {{ number_format($product->price, 2) }}
                </td>

                <td class="py-2 px-4 border text-center">

                    @if($product->is_active)
                        <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-sm">
                            Active
                        </span>
                    @else
                        <span class="bg-gray-200 text-gray-700 px-2 py-1 rounded text-sm">
                            Disabled
                        </span>
                    @endif

                </td>

                <td class="py-2 px-4 border text-center whitespace-nowrap">

                    <a href="{{ route('products.edit', $product->id) }}"
                       class="text-yellow-600 font-semibold mr-3">
                       Edit
                    </a>

                    <form action="{{ route('products.destroy', $product->id) }}"
                          method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="text-red-600 font-semibold"
                                onclick="return confirm('Delete this product?')">
                            Delete
                        </button>
                    </form>

                </td>

            </tr>

        @empty

            <tr>
                <td colspan="6"
                    class="py-6 text-center text-gray-400 border">
                    No products found
                </td>
            </tr>

        @endforelse

        </tbody>

    </table>

    <div class="mt-4">
        {{ $products->links() }}
    </div>

</div>

@endsection

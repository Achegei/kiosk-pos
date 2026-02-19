@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Transaction Items</h1>

    <a href="{{ route('transaction-items.create') }}" class="bg-blue-500 text-white px-4 py-2 rounded mb-4 inline-block">Add Item</a>

    <table class="min-w-full bg-white border">
        <thead>
            <tr class="bg-gray-100 border-b">
                <th class="p-2 border">Transaction ID</th>
                <th class="p-2 border">Product</th>
                <th class="p-2 border">Quantity</th>
                <th class="p-2 border">Price</th>
                <th class="p-2 border">Total</th>
                <th class="p-2 border">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr class="border-b">
                <td class="p-2 border">{{ $item->transaction_id }}</td>
                <td class="p-2 border">{{ $item->product->name }}</td>
                <td class="p-2 border">{{ $item->quantity }}</td>
                <td class="p-2 border">KSh {{ number_format($item->price, 2) }}</td>
                <td class="p-2 border">KSh {{ number_format($item->total, 2) }}</td>
                <td class="p-2 border flex gap-2">
                    <a href="{{ route('transaction-items.edit', $item) }}" class="bg-yellow-400 px-2 py-1 rounded">Edit</a>
                    <form action="{{ route('transaction-items.destroy', $item) }}" method="POST" onsubmit="return confirm('Delete this item?')">
                        @csrf
                        @method('DELETE')
                        <button class="bg-red-500 text-white px-2 py-1 rounded">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $items->links() }}
    </div>
</div>
@endsection

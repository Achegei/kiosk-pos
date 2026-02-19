@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Transaction Item #{{ $transactionItem->id }}</h1>

    <div class="bg-white shadow rounded p-6">
        <div class="mb-4">
            <span class="font-semibold">Transaction ID:</span>
            {{ $transactionItem->transaction_id }}
        </div>

        <div class="mb-4">
            <span class="font-semibold">Product:</span>
            {{ $transactionItem->product->name }}
        </div>

        <div class="mb-4">
            <span class="font-semibold">Quantity:</span>
            {{ $transactionItem->quantity }}
        </div>

        <div class="mb-4">
            <span class="font-semibold">Price:</span>
            KSh {{ number_format($transactionItem->price, 2) }}
        </div>

        <div class="mb-4">
            <span class="font-semibold">Total:</span>
            KSh {{ number_format($transactionItem->total, 2) }}
        </div>

        <div class="flex gap-2 mt-4">
            <a href="{{ route('transaction-items.edit', $transactionItem) }}" 
               class="bg-yellow-400 px-4 py-2 rounded">Edit</a>
            <a href="{{ route('transaction-items.index') }}" 
               class="bg-gray-400 text-white px-4 py-2 rounded">Back to List</a>
        </div>
    </div>
</div>
@endsection

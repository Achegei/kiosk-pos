@extends('layouts.admin')

@section('content')
<div class="container mx-auto p-4">

    <h1 class="text-2xl font-bold mb-4">Transactions</h1>

    {{-- ---------------- MINI POS ---------------- --}}
    <div class="bg-gray-50 p-4 rounded mb-6 shadow">

        <h2 class="text-xl font-semibold mb-2">Quick POS</h2>

        <div class="mb-4 flex gap-2">
            {{-- Customer --}}
            <select id="customer" name="customer_id" class="w-full rounded-lg border-gray-300 shadow-sm">
                <option value="" selected>Walk-in Customer</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}">
                        {{ $customer->name }} @if($customer->phone) ({{ $customer->phone }}) @endif
                    </option>
                @endforeach
            </select>

            {{-- Quick Add --}}
            <button type="button" id="newCustomerBtn" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 rounded-lg shadow">
                + New
            </button>
        </div>

        {{-- Product Search --}}
        <div class="flex gap-2 mb-4">
            <input type="text" id="productSearch" placeholder="Search product..." class="w-full rounded-lg border-gray-300 shadow px-3 py-2">
            <button id="addProductBtn" class="bg-green-500 hover:bg-green-600 text-white px-4 rounded-lg">Add</button>
        </div>

        {{-- Cart Table --}}
        <table class="min-w-full bg-white border mb-2">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-2 px-4 border">Product</th>
                    <th class="py-2 px-4 border">Price</th>
                    <th class="py-2 px-4 border">Qty</th>
                    <th class="py-2 px-4 border">Total</th>
                    <th class="py-2 px-4 border">Action</th>
                </tr>
            </thead>
            <tbody id="cartBody">
                <tr>
                    <td colspan="5" class="text-center py-4 text-gray-400">Cart is empty</td>
                </tr>
            </tbody>
        </table>

        {{-- Totals & Cash --}}
        <div class="flex gap-4 mb-2">
            <div>Subtotal: KES <span id="subtotal">0.00</span></div>
            <div>Tax: KES <span id="tax">0.00</span></div>
            <div>Change: KES <span id="change">0.00</span></div>
        </div>

        <div class="flex gap-2 mb-2">
            <input type="number" id="cashGiven" placeholder="Cash given" class="w-32 rounded-lg border-gray-300 px-3 py-2">
            <select id="payment_method" class="rounded-lg border-gray-300 px-3 py-2">
                <option value="Cash">Cash</option>
                <option value="Mpesa">Mpesa</option>
                <option value="Credit">Credit</option>
            </select>
        </div>

        <form id="checkoutForm" action="{{ url('/transactions/pos-checkout') }}" method="POST">
            @csrf
            <input type="hidden" name="products" id="products">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Checkout</button>
        </form>
    </div>

    {{-- ---------------- EXISTING TRANSACTIONS ---------------- --}}
    <a href="{{ route('transactions.create') }}" class="bg-blue-500 text-white px-4 py-2 rounded mb-4 inline-block">Add Transaction</a>

    <table class="min-w-full bg-white border">
        <thead class="bg-gray-100">
            <tr>
                <th class="py-2 px-4 border">ID</th>
                <th class="py-2 px-4 border">Customer</th>
                <th class="py-2 px-4 border">Total</th>
                <th class="py-2 px-4 border">Date</th>
                <th class="py-2 px-4 border">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $transaction)
            <tr>
                <td class="py-2 px-4 border">{{ $transaction->id }}</td>
                <td class="py-2 px-4 border">{{ $transaction->customer?->name ?? 'Walk-in' }}</td>
                <td class="py-2 px-4 border">KSh {{ number_format($transaction->total_amount ?? 0, 2) }}</td>
                <td class="py-2 px-4 border">{{ $transaction->created_at->format('d-M-Y H:i') }}</td>
                <td class="py-2 px-4 border">
                    <a href="{{ route('transactions.edit', $transaction) }}" class="text-blue-600 mr-2">Edit</a>
                    <form action="{{ route('transactions.destroy', $transaction) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $transactions->links() }}
    </div>

</div>

@include('transactions.pos-js') {{-- include your JS here --}}
@endsection

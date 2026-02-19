@extends('layouts.admin')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Edit Transaction #{{ $transaction->id }}</h1>

    <form action="{{ route('transactions.update', $transaction) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block mb-2 font-semibold">Customer (Optional)</label>
            <select name="customer_id" class="border p-2 w-full rounded">
                <option value="">Walk-in Customer</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" 
                        {{ $transaction->customer_id == $customer->id ? 'selected' : '' }}>
                        {{ $customer->name }} (Credit: KSh {{ number_format($customer->credit, 2) }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="block mb-2 font-semibold">Products</label>
            <div id="products-container">
                @foreach($transaction->items as $item)
                <div class="flex gap-2 mb-2 product-row">
                    <select name="product_id[]" class="border p-2 rounded flex-1 product-select">
                        <option value="">-- Select Product --</option>
                        @foreach($products as $product)
                        <option value="{{ $product->id }}" 
                            data-price="{{ $product->price }}"
                            {{ $item->product_id == $product->id ? 'selected' : '' }}>
                            {{ $product->name }} (KSh {{ $product->price }})
                        </option>
                        @endforeach
                    </select>
                    <input type="number" name="quantity[]" min="1" value="{{ $item->quantity }}" 
                        class="border p-2 rounded w-20 quantity-input" />
                    <span class="price-text flex-1 self-center">Total: KSh {{ $item->quantity * $item->product->price }}</span>
                    <button type="button" class="bg-red-500 text-white px-2 rounded remove-row">Remove</button>
                </div>
                @endforeach
            </div>
            <button type="button" id="add-product" class="bg-green-500 text-white px-4 py-2 rounded mt-2">Add Product</button>
        </div>

        <div class="mb-4">
            <label class="block mb-2 font-semibold">Transaction Total:</label>
            <span id="transaction-total" class="font-bold">KSh {{ number_format($transaction->total, 2) }}</span>
        </div>

        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Update Transaction</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('products-container');
    const addBtn = document.getElementById('add-product');

    function updateTotals(row) {
        const select = row.querySelector('.product-select');
        const qty = row.querySelector('.quantity-input').value;
        const price = select.selectedOptions[0]?.dataset.price || 0;
        const total = qty * price;
        row.querySelector('.price-text').textContent = 'Total: KSh ' + total.toLocaleString();
        updateTransactionTotal();
    }

    function updateTransactionTotal() {
        let total = 0;
        document.querySelectorAll('.product-row').forEach(row => {
            const select = row.querySelector('.product-select');
            const qty = row.querySelector('.quantity-input').value;
            const price = select.selectedOptions[0]?.dataset.price || 0;
            total += qty * price;
        });
        document.getElementById('transaction-total').textContent = 'KSh ' + total.toLocaleString();
    }

    addBtn.addEventListener('click', () => {
        const newRow = container.firstElementChild.cloneNode(true);
        newRow.querySelector('.quantity-input').value = 1;
        container.appendChild(newRow);
        newRow.querySelector('.remove-row').addEventListener('click', () => newRow.remove());
        newRow.querySelector('.product-select').addEventListener('change', () => updateTotals(newRow));
        newRow.querySelector('.quantity-input').addEventListener('input', () => updateTotals(newRow));
        updateTotals(newRow);
    });

    container.querySelectorAll('.remove-row').forEach(btn => {
        btn.addEventListener('click', (e) => btn.closest('.product-row').remove());
    });

    container.querySelectorAll('.product-select').forEach(select => {
        select.addEventListener('change', (e) => updateTotals(select.closest('.product-row')));
    });

    container.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('input', (e) => updateTotals(input.closest('.product-row')));
    });

    updateTransactionTotal();
});
</script>
@endsection

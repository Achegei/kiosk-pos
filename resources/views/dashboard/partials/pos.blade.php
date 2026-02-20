@if(auth()->check() && auth()->user()->role === 'staff')

<div class="bg-white shadow-xl rounded-2xl p-6">

    <h3 class="text-2xl font-bold mb-6 text-gray-800">🛒 Smart POS</h3>

    <!-- 🟢🔴 ONLINE/OFFLINE STATUS -->
    <div id="networkStatus" class="px-3 py-1 rounded text-white font-bold mb-4">
        Checking...
    </div>

    <!-- ===== RECEIPT MODAL ===== -->
    <div id="receiptModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded shadow-lg w-96">
            <h2 class="text-xl font-bold mb-4">Receipt</h2>
            <div id="receiptContent" class="mb-4"></div>
            <div id="receiptTotal" class="mb-4 font-bold text-right"></div>
            <div class="flex justify-end gap-2">
                <button id="printReceipt" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Print</button>
                <button id="closeReceipt" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Close</button>
            </div>
        </div>
    </div>

    <!-- CUSTOMER -->
    <div class="mb-6">
        <label class="font-semibold block mb-1">Customer</label>
        <div class="flex gap-2">
            <select id="customer" name="customer_id" class="w-full rounded-lg border-gray-300 shadow-sm">
                <option value="" selected>Walk-in Customer</option>
                @foreach($customers ?? [] as $customer)
                    <option value="{{ $customer->id }}">
                        {{ $customer->name }}
                        @if($customer->phone)
                            ({{ $customer->phone }})
                        @endif
                        {{ $customer->credit ?? '' }}
                    </option>
                @endforeach
            </select>
            <button type="button" id="newCustomerBtn" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 rounded-lg shadow">
                + New
            </button>
        </div>
    </div>

    <!-- SEARCH -->
    <div class="relative mb-6 flex gap-2">
        <input
            id="productSearch"
            placeholder="Scan barcode or search product..."
            class="flex-1 p-4 text-lg rounded-xl border-2 border-indigo-200 focus:border-indigo-500"
        >
        <button id="addProductBtn" class="px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 font-semibold">Add</button>
        <ul id="suggestions" class="absolute top-full left-0 right-0 bg-white border rounded-xl shadow-xl hidden max-h-72 overflow-auto z-40"></ul>
    </div>

    <!-- CART TABLE -->
    <div class="overflow-x-auto mb-6">
        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 text-left">Product</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Total</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="cartBody">

                {{-- Merge online and offline cart items --}}
                @php
                    // onlineCartItems should come from JS/Livewire or session
                    $offlineCartItems = $offlineCartItems ?? collect();

                    // Merge offline items
                    $mergedCart = $offlineCartItems->groupBy('product_id')->map(function($items, $productId) {
                        $first = $items->first();
                        return [
                            'product_id' => $productId,
                            'name' => $first['name'] ?? 'Unknown',
                            'price' => $first['price'] ?? 0,
                            'quantity' => $items->sum(fn($i) => $i['quantity'] ?? 0),
                        ];
                    });
                @endphp

                @forelse($mergedCart as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="p-2">{{ $item['name'] }}</td>
                        <td class="p-2">KES {{ number_format($item['price'],2) }}</td>
                        <td class="p-2 text-center">{{ $item['quantity'] }}</td>
                        <td class="p-2 text-right font-semibold">
                            KES {{ number_format($item['price'] * $item['quantity'],2) }}
                        </td>
                        <td class="p-2 text-center">
                            <button class="text-red-600 hover:text-red-800 removeItemBtn" data-id="{{ $item['product_id'] }}">❌</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center p-4 text-gray-400">
                            Your cart is empty
                        </td>
                    </tr>
                @endforelse

            </tbody>
        </table>
    </div>

    <!-- TOTALS -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-gray-50 p-4 rounded-xl mb-6">
        <div>
            <div class="text-xs text-gray-500">Subtotal</div>
            <div class="text-xl font-bold">KES <span id="subtotal">0.00</span></div>
        </div>
        <div>
            <div class="text-xs text-gray-500">Tax (0%)</div>
            <div class="text-xl font-bold">KES <span id="tax">0.00</span></div>
        </div>
        <div>
            <div class="text-xs text-gray-500">Cash Given</div>
            <input id="cashGiven" type="number" class="w-full mt-1 rounded border p-2">
        </div>
        <div>
            <div class="text-xs text-gray-500">Change</div>
            <div class="text-2xl font-extrabold text-green-600">
                KES <span id="change">0.00</span>
            </div>
        </div>
    </div>

    <!-- PAYMENT -->
    <form id="checkoutForm" action="{{ route('transactions.pos_checkout') }}" method="POST">
        @csrf
        <input type="hidden" name="customer_id" id="customer_id">
        <input type="hidden" name="products" id="products">

        <div class="flex gap-4 items-center mb-4">
            <select name="payment_method" id="payment_method" class="rounded-lg border-gray-300 p-3">
                <option value="Cash">Cash</option>
                <option value="Mpesa">Mpesa</option>
                <option value="Credit">Credit</option>
            </select>

            <button type="submit" class="px-10 py-4 bg-indigo-600 text-white rounded-xl text-lg font-bold hover:bg-indigo-700">
                💳 CHECKOUT
            </button>
        </div>
    </form>

</div>

@push('scripts')
@vite('resources/js/pos.js')
  <script>
    // ===== ENRICH: Initialize cart with offline items from server =====
    @if(isset($offlineCartItems) && $offlineCartItems->count())
        @foreach($offlineCartItems as $item)
            window.cart.push({
                id: {{ $item['product_id'] }},
                name: "{{ $item['name'] }}",
                price: {{ $item['price'] }},
                quantity: {{ $item['quantity'] }},
                stock: {{ $item['stock'] ?? 1000 }} // fallback stock
            });
        @endforeach
        renderCart(); // make sure cart UI reflects these items
    @endif
</script>
@endpush
@endif

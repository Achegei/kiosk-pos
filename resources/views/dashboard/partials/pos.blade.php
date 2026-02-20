@php
$store = config('store');
@endphp

@if(auth()->check() && auth()->user()->role === 'staff')

<div class="flex flex-col md:flex-row gap-6 p-6 bg-gray-50 min-h-screen">

    {{-- LEFT PANEL: CART + TOTALS --}}
    <div class="flex-1 bg-white shadow-xl rounded-2xl p-6 flex flex-col">

        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-800">🛒 Smart POS</h3>
            <div id="networkStatus" class="px-3 py-1 rounded font-bold text-white">Checking...</div>
        </div>

        {{-- Customer Selection --}}
        <div class="mb-4">
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

        {{-- Cart Table --}}
        <div class="overflow-x-auto mb-4 flex-1">
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
                    {{-- Populated via JS --}}
                </tbody>
            </table>
        </div>

        {{-- Totals --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-gray-50 p-4 rounded-xl mb-4">
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

        {{-- Checkout --}}
        <form id="checkoutForm" action="{{ route('transactions.pos_checkout') }}" method="POST" class="flex gap-4 items-center">
            @csrf
            <input type="hidden" name="customer_id" id="customer_id">
            <input type="hidden" name="products" id="products">

            <select name="payment_method" id="payment_method" class="rounded-lg border-gray-300 p-3">
                <option value="Cash">Cash</option>
                <option value="Mpesa">Mpesa</option>
                <option value="Credit">Credit</option>
            </select>

            <button type="submit" class="px-10 py-4 bg-indigo-600 text-white rounded-xl text-lg font-bold hover:bg-indigo-700">
                💳 CHECKOUT
            </button>
        </form>

    </div>

    {{-- RIGHT PANEL: SEARCH + TOOLS --}}
    <div class="w-full md:w-1/3 flex flex-col gap-4">

        {{-- Product Search --}}
        <div class="bg-white shadow-xl rounded-2xl p-4">
            <input
                id="productSearch"
                placeholder="Scan barcode or search product..."
                class="w-full p-4 text-lg rounded-xl border-2 border-indigo-200 focus:border-indigo-500"
            >
            <button id="addProductBtn" class="w-full mt-2 px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 font-semibold">
                Add Product
            </button>
            <ul id="suggestions" class="hidden max-h-72 overflow-auto mt-2 bg-white rounded-xl shadow-xl border"></ul>
        </div>

        {{-- Quick Assistive Calculator --}}
        <div class="bg-white shadow-xl rounded-2xl p-4">
            <div class="text-gray-600 font-semibold mb-2">Calculator</div>
            <input type="text" id="quickCalcInput" placeholder="0" class="w-full p-3 rounded-xl border text-right text-lg font-mono mb-2">
            <div class="grid grid-cols-4 gap-2">
                @foreach(['7','8','9','C','4','5','6','/','1','2','3','*','0','.','=','-','+'] as $btn)
                    <button class="calcBtn p-2 bg-gray-200 rounded @if(in_array($btn,['C','/','*','-','=','+'])) bg-indigo-600 text-white @endif">{{ $btn }}</button>
                @endforeach
            </div>
        </div>

        {{-- Offline Banner --}}
        <div id="offlineBanner" class="hidden p-3 bg-yellow-100 border-l-4 border-yellow-500 rounded font-semibold">
            ⚠ 0 sales pending sync
        </div>

    </div>

</div>

@push('scripts')
<script>
window.currentUserName = @json(auth()->user()->name ?? 'Staff');

// Initialize offline cart safely
@if(isset($offlineCartItems) && $offlineCartItems->count())
    @foreach($offlineCartItems as $item)
        window.cart.push({
            id: {{ $item['product_id'] }},
            name: "{{ $item['name'] }}",
            price: {{ $item['price'] }},
            quantity: {{ $item['quantity'] }},
            stock: {{ $item['stock'] ?? 1000 }}
        });
    @endforeach
    renderCart();
@endif

// Barcode autofocus stay-ready
const productSearch = document.getElementById('productSearch');
function keepFocus() {
    if(productSearch){
        productSearch.focus();
        productSearch.select();
    }
}
document.getElementById('addProductBtn')?.addEventListener('click', ()=> setTimeout(keepFocus,50));
productSearch?.addEventListener('keydown', (e)=> {
    if(e.key==='Enter'){ setTimeout(keepFocus,50); }
});
</script>

@vite('resources/js/pos.js')
@endpush

@endif

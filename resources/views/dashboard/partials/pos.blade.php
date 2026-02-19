
<div class="bg-white shadow-xl rounded-2xl p-6">

    <h3 class="text-2xl font-bold mb-6 text-gray-800">🛒 Smart POS</h3>

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
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}">
                    {{ $customer->name }} @if($customer->phone) ({{ $customer->phone }}) {{$customer->credit}} @endif
                </option>
            @endforeach
        </select>


        <!-- QUICK ADD BUTTON -->
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

        <!-- Live suggestions dropdown -->
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
            <tbody id="cartBody"></tbody>
        </table>
    </div>

    <!-- POS GRID (Totals / Cash / Change) -->
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

    <!-- PAYMENT FORM -->
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
@endpush
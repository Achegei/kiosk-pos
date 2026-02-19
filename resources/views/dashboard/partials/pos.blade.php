
<div class="bg-white shadow rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-700 mb-4">Point of Sale</h3>
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


    {{-- Customer Selection --}}
    <div class="mb-4">
        <label for="customer" class="block text-sm font-medium text-gray-700">Customer (optional)</label>
        <select id="customer" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
            <option value="">Walk-in</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}">
                    {{ $customer->name }} (Credit: ${{ number_format($customer->customerProfile->credit_limit ?? 0, 2) }})
                </option>
            @endforeach
        </select>
    </div>

    {{-- Product Search --}}
    <div class="mb-4 flex gap-2 relative">
        <input type="text" id="productSearch" placeholder="Search by name or barcode" class="flex-1 rounded-md border-gray-300 shadow-sm sm:text-sm">
        <button id="addProductBtn" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Add</button>

        <!-- Live suggestions dropdown -->
        <ul id="suggestions" class="absolute top-full left-0 right-0 bg-white border border-gray-300 rounded shadow z-10 hidden max-h-60 overflow-y-auto">
        </ul>
    </div>

    {{-- Cart Table --}}
    <div class="overflow-x-auto mb-4">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="cartBody" class="bg-white divide-y divide-gray-200"></tbody>
        </table>
    </div>

    {{-- Cart Totals --}}
    <div class="flex justify-end space-x-6 mb-4">
        <div class="text-right">
            <p>Subtotal: KES <span id="subtotal">0.00</span></p>
            <p>Tax (0%): KES <span id="tax">0.00</span></p>
            <p class="text-2xl font-bold text-indigo-600">Total: KES <span id="total">0.00</span></p>
        </div>
    </div>

    {{-- Payment Form --}}
    <form id="checkoutForm" action="{{ url('/transactions/pos-checkout') }}" method="POST">
        @csrf
        <input type="hidden" name="customer_id" id="customer_id">
        <input type="hidden" name="products" id="products">
        <div class="flex items-center gap-4 mb-4">
            <label>Payment Method:</label>
            <select name="payment_method" id="payment_method" class="rounded-md border-gray-300 shadow-sm sm:text-sm">
                <option value="Cash">Cash</option>
                <option value="Mpesa">Mpesa</option>
                <option value="Credit">Credit</option>
            </select>
        </div>
        <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded hover:bg-indigo-700 font-semibold">
            Checkout
        </button>
    </form>
</div>
<!-- DEVICE UUID (ensure every tablet has one) -->
<script>
if (!localStorage.getItem('device_uuid')) {
    localStorage.setItem('device_uuid', crypto.randomUUID());
}
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    let cart = [];
    const cartBody = document.getElementById('cartBody');
    const subtotalEl = document.getElementById('subtotal');
    const totalEl = document.getElementById('total');
    const searchInput = document.getElementById('productSearch');
    const addBtn = document.getElementById('addProductBtn');
    const suggestions = document.getElementById('suggestions');
    let debounceTimeout = null;

    const deviceId = localStorage.getItem('device_uuid');

    // ===== HELPER: fetch product by query (name) or barcode =====
    async function fetchProduct(query) {
        try {
            // First: live search by name
            let res = await fetch(`/products/search?query=${encodeURIComponent(query)}`, {
                headers: { 'X-DEVICE-ID': deviceId }
            });
            let data = await res.json();
            if (data.length > 0) return data[0];

            // Second: search by barcode if no name match
            res = await fetch(`/products/barcode/${encodeURIComponent(query)}`, {
                headers: { 'X-DEVICE-ID': deviceId }
            });
            let product = await res.json();
            if (product && !Array.isArray(product)) product = [product];
            return product.length ? product[0] : null;

        } catch (err) {
            console.error(err);
            return null;
        }
    }

    // ===== RENDER LIVE SEARCH SUGGESTIONS =====
    searchInput.addEventListener('input', function () {
        const query = this.value.trim();
        if (!query) {
            suggestions.innerHTML = '';
            suggestions.classList.add('hidden');
            return;
        }

        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(async () => {
            const res = await fetch(`/products/search?query=${encodeURIComponent(query)}`, {
                headers: { 'X-DEVICE-ID': deviceId }
            });
            const data = await res.json();

            suggestions.innerHTML = '';
            if (!data.length) {
                suggestions.classList.add('hidden');
                return;
            }

            data.forEach(product => {
                const li = document.createElement('li');
                li.textContent = `${product.name} (KES ${parseFloat(product.price).toFixed(2)})`;
                li.className = 'px-4 py-2 hover:bg-indigo-100 cursor-pointer';
                li.addEventListener('click', () => {
                    addToCart(product);
                    searchInput.value = '';
                    suggestions.innerHTML = '';
                    suggestions.classList.add('hidden');
                });
                suggestions.appendChild(li);
            });
            suggestions.classList.remove('hidden');

        }, 250); // 250ms debounce
    });

    // ===== ENTER KEY (name or barcode) =====
    searchInput.addEventListener("keypress", async function (e) {
        if (e.key !== "Enter") return;
        e.preventDefault();
        const query = this.value.trim();
        if (!query) return;

        const product = await fetchProduct(query);
        if (!product || !product.id) {
            alert("Product not found");
            return;
        }

        addToCart(product);
        searchInput.value = '';
        suggestions.innerHTML = '';
        suggestions.classList.add('hidden');
    });

    // ===== ADD BUTTON CLICK =====
    addBtn.addEventListener('click', () => {
        searchInput.dispatchEvent(new KeyboardEvent('keypress', { key: 'Enter' }));
    });

    // ===== CART FUNCTIONS =====
    function addToCart(product) {
        const existing = cart.find(i => i.id == product.id);
        if (existing) existing.quantity++;
        else cart.push({ id: product.id, name: product.name, price: parseFloat(product.price), quantity: 1 });
        renderCart();
    }

    function renderCart() {
        cartBody.innerHTML = '';
        let subtotal = 0;

        cart.forEach((item, index) => {
            const total = item.price * item.quantity;
            subtotal += total;
            cartBody.innerHTML += `
            <tr>
                <td>${item.name}</td>
                <td>KES ${item.price.toFixed(2)}</td>
                <td>
                    <input type="number" min="1" value="${item.quantity}"
                    onchange="changeQty(${index},this.value)"
                    class="w-16 border rounded px-2">
                </td>
                <td>KES ${total.toFixed(2)}</td>
                <td>
                    <button onclick="removeItem(${index})"
                    class="bg-red-500 text-white px-2 py-1 rounded">
                    Remove
                    </button>
                </td>
            </tr>`;
        });

        subtotalEl.innerText = subtotal.toFixed(2);
        totalEl.innerText = subtotal.toFixed(2);
        document.getElementById('products').value = JSON.stringify(cart);
    }

    window.removeItem = function (i) { cart.splice(i, 1); renderCart(); }
    window.changeQty = function (i, val) { cart[i].quantity = parseInt(val); renderCart(); }

    // ===== CUSTOMER SELECTION =====
    document.getElementById('customer').addEventListener('change', function () {
        document.getElementById('customer_id').value = this.value;
    });

    // ===== CHECKOUT =====
    const checkoutForm = document.getElementById('checkoutForm');
    checkoutForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (!cart.length) return alert("Cart is empty!");

        const formData = new FormData(this);
        formData.set('products', JSON.stringify(cart));

        try {
            const res = await fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json',
                    'X-DEVICE-ID': deviceId
                },
                body: formData
            });
            const data = await res.json();

            if (res.ok) {
                alert("✅ Sale Successful!");
                console.log("Receipt:", data);
                cart = [];
                renderCart();
                document.getElementById('customer').value = '';
                document.getElementById('customer_id').value = '';
                document.getElementById('payment_method').value = 'Cash';
            } else {
                alert(data.message || "Checkout failed");
            }

        } catch (err) {
            console.error(err);
            alert("Checkout error");
        }
    });

});
</script>

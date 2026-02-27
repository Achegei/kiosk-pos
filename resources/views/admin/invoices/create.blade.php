@extends('layouts.admin')
@section('title','Create Invoice')

@section('content')
<div class="p-6 max-w-5xl mx-auto bg-white shadow rounded">

    <h1 class="text-2xl font-bold mb-6">Create Invoice</h1>

    {{-- Success / Error messages --}}
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form id="invoiceForm" action="{{ route('invoices.store') }}" method="POST">
        @csrf

        {{-- CUSTOMER SELECT --}}
        <div class="mb-4">
            <select id="customerSelect" name="customer_id" class="w-full border rounded p-2">
                <option value="">-- New Customer --</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}"
                        data-name="{{ e($customer->name) }}"
                        data-email="{{ e($customer->email) }}"
                        data-phone="{{ e($customer->phone) }}"
                        data-address="{{ e($customer->building_name) }} {{ e($customer->street_address) }}">
                        {{ $customer->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- CLIENT INFO --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-4">
            <input type="text" id="client_name" name="client_name" placeholder="Client Name" class="border p-2 rounded" required>
            <input type="email" id="client_email" name="client_email" placeholder="Client Email" class="border p-2 rounded">
            <input type="text" id="client_phone" name="client_phone" placeholder="Client Phone" class="border p-2 rounded">
            <input type="text" id="client_address" name="client_address" placeholder="Client Address" class="border p-2 rounded">
        </div>

        {{-- PRODUCT SEARCH --}}
        <input type="text" id="productSearch" placeholder="Search products..." class="border p-2 rounded mb-2 w-full">
        <div id="productResults" class="border rounded max-h-60 overflow-y-auto mb-4"></div>

        {{-- CART TABLE --}}
        <table class="w-full text-sm mb-4 border-collapse border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border p-2">Product</th>
                    <th class="border p-2">Qty</th>
                    <th class="border p-2">Price</th>
                    <th class="border p-2">Total</th>
                    <th class="border p-2">Action</th>
                </tr>
            </thead>
            <tbody id="cart"></tbody>
        </table>

        {{-- TAX & DISCOUNT --}}
        <div class="flex gap-4 mb-4">
            <input type="number" id="tax" placeholder="Tax %" value="0" min="0" step="0.01" class="border p-2 w-32 rounded">
            <input type="number" id="discount" placeholder="Discount" value="0" min="0" step="0.01" class="border p-2 w-32 rounded">
        </div>

        {{-- TOTAL --}}
        <div class="text-right font-bold text-xl mb-4">
            Total: <span id="total">0.00</span>
        </div>

        {{-- HIDDEN INPUTS --}}
        <input type="hidden" name="tax_percent" id="tax_input">
        <input type="hidden" name="discount" id="discount_input">
        <input type="hidden" name="total_amount" id="total_input">

        {{-- STATUS --}}
        <div class="mb-4">
            <label class="block font-semibold mb-2">Invoice Status</label>
            <select name="status" class="w-full border rounded p-2" required>
                <option value="Pending">Pending</option>
                <option value="Paid">Paid</option>
                <option value="On Credit">On Credit</option>
            </select>
        </div>
        {{-- BUTTONS --}}
        <button type="submit" class="bg-indigo-600 text-white py-3 rounded w-full mb-2">
            Save Invoice
        </button>

        <button type="button" onclick="printInvoicePDF()" class="bg-gray-600 text-white py-3 rounded w-full">
            Print Invoice (PDF)
        </button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // ===================== VARIABLES =====================
    const cart = [];
    let productDebounce;

    const productSearch   = document.getElementById('productSearch');
    const productResults  = document.getElementById('productResults');
    const customerSelect  = document.getElementById('customerSelect');
    const taxInput        = document.getElementById('tax');
    const discountInput   = document.getElementById('discount');
    const totalDisplay    = document.getElementById('total');
    const taxHidden       = document.getElementById('tax_input');
    const discountHidden  = document.getElementById('discount_input');
    const totalHidden     = document.getElementById('total_input');
    const invoiceForm     = document.getElementById('invoiceForm');

    // ===================== PRODUCT SEARCH =====================
    productSearch.addEventListener('input', () => {
        const query = productSearch.value.trim();
        if (!query) {
            productResults.innerHTML = '';
            return;
        }

        clearTimeout(productDebounce);
        productDebounce = setTimeout(async () => {
            try {
                const res = await fetch(`/products/search?q=${encodeURIComponent(query)}`);
                if (!res.ok) return;
                const data = await res.json();

                productResults.innerHTML = '';
                data.forEach(p => {
                    const div = document.createElement('div');
                    div.className = 'p-2 border cursor-pointer hover:bg-indigo-50 flex justify-between';
                    div.innerHTML = `<span>${p.name}</span><span>${parseFloat(p.price).toFixed(2)}</span>`;
                    div.addEventListener('click', () => {
                        addToCart(p.id, p.name, parseFloat(p.price));
                        productResults.innerHTML = '';
                        productSearch.value = '';
                    });
                    productResults.appendChild(div);
                });
            } catch (e) {
                console.error('Product search error:', e);
            }
        }, 300);
    });

    // ===================== CUSTOMER AUTO-FILL =====================
    customerSelect.addEventListener('change', () => {
        const option = customerSelect.selectedOptions[0];
        if (!option || !option.value) {
            fillClientInfo({});
            return;
        }

        fillClientInfo({
            name: option.dataset.name,
            email: option.dataset.email,
            phone: option.dataset.phone,
            address: option.dataset.address
        });
    });

    function fillClientInfo(customer) {
        document.getElementById('client_name').value = customer.name || '';
        document.getElementById('client_email').value = customer.email || '';
        document.getElementById('client_phone').value = customer.phone || '';
        document.getElementById('client_address').value = customer.address || '';
    }

    // ===================== CART FUNCTIONS =====================
    function addToCart(id, name, price) {
        const existing = cart.find(x => x.id === id);
        if (existing) existing.qty++;
        else cart.push({ id, name, price, qty: 1 });
        renderCart();
    }

    function removeFromCart(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function updateQty(index, qty) {
        cart[index].qty = Math.max(1, parseInt(qty) || 1);
        renderCart();
    }

    function renderCart() {
        let html = '';
        let subtotal = 0;

        cart.forEach((p, i) => {
            const lineTotal = p.qty * p.price;
            subtotal += lineTotal;

            html += `
            <tr>
                <td>${p.name}</td>
                <td><input type="number" value="${p.qty}" min="1" class="border w-16 p-1" data-index="${i}" data-action="updateQty"></td>
                <td>${p.price.toFixed(2)}</td>
                <td>${lineTotal.toFixed(2)}</td>
                <td><button type="button" class="text-red-600 font-bold" data-index="${i}" data-action="remove">X</button></td>
                <input type="hidden" name="items[${i}][product_id]" value="${p.id}">
                <input type="hidden" name="items[${i}][qty]" value="${p.qty}">
            </tr>`;
        });

        document.getElementById('cart').innerHTML = html;

        const tax = parseFloat(taxInput.value) || 0;
        const discount = parseFloat(discountInput.value) || 0;
        const total = Math.max(subtotal + subtotal * tax / 100 - discount, 0);

        totalDisplay.innerText = total.toFixed(2);
        taxHidden.value = tax;
        discountHidden.value = discount;
        totalHidden.value = total;

        // Attach event listeners to dynamic buttons and inputs
        document.querySelectorAll('[data-action="remove"]').forEach(btn => {
            btn.addEventListener('click', () => removeFromCart(btn.dataset.index));
        });

        document.querySelectorAll('[data-action="updateQty"]').forEach(input => {
            input.addEventListener('change', () => updateQty(input.dataset.index, input.value));
        });
    }

    // ===================== TAX & DISCOUNT =====================
    taxInput.addEventListener('change', renderCart);
    discountInput.addEventListener('change', renderCart);

    // ===================== FORM VALIDATION =====================
    invoiceForm.addEventListener('submit', e => {
        if (!cart.length) {
            e.preventDefault();
            alert('Add at least one product.');
        }
    });

    // ===================== PRINT FUNCTION =====================
    window.printInvoicePDF = function() {
        if (!cart.length) {
            alert('Add some products first!');
            return;
        }

        const tenantName = "{{ $tenant->name ?? '' }}";
        const tenantPhone = "{{ $tenant->phone ?? '' }}";
        const tenantLogo = "{{ $tenant->logo ? asset('storage/' . $tenant->logo) : '' }}";
        const tenantAddress = "{{ ($tenant->street_address ?? '') }} {{ ($tenant->building_name ?? '') }}";

        const subtotal = cart.reduce((sum, p) => sum + p.qty * p.price, 0);
        const tax = parseFloat(taxInput.value) || 0;
        const discount = parseFloat(discountInput.value) || 0;
        const total = Math.max(subtotal + subtotal * tax / 100 - discount, 0);

        let html = `
        <div style="font-family:sans-serif;max-width:800px;margin:auto;">
            <div style="display:flex;justify-content:space-between;">
                <div>
                    <h2>${tenantName}</h2>
                    <p>${tenantAddress}</p>
                    <p>Phone: ${tenantPhone}</p>
                </div>
                ${tenantLogo ? `<img src="${tenantLogo}" style="max-height:80px;">` : ''}
            </div>
            <hr>
            <table border="1" width="100%" cellpadding="5" cellspacing="0">
                <tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr>`;

        cart.forEach(p => {
            html += `<tr>
                <td>${p.name}</td>
                <td>${p.qty}</td>
                <td>${p.price.toFixed(2)}</td>
                <td>${(p.qty * p.price).toFixed(2)}</td>
            </tr>`;
        });

        html += `</table>
            <p><strong>Total: ${total.toFixed(2)}</strong></p>
        </div>`;

        const w = window.open('', '_blank');
        w.document.write(html);
        w.document.close();
        w.print();
    };

    // ===================== INITIAL RENDER =====================
    renderCart();
});
</script>
@endsection
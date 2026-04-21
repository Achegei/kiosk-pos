console.log("Cart module initialized");

// =============================
// SAFE GLOBAL
// =============================
window.POS = window.POS || {};
window.POS.cart = window.POS.cart || {};

// =============================
// USE CENTRAL STORE (NEW)
// =============================
window.POS.store = window.POS.store || {};
window.POS.store.cart = window.POS.store.cart || [];

// =============================
// DOM REFERENCES (SAFE)
// =============================
const cartBody = document.getElementById('cartBody');
const subtotalEl = document.getElementById('subtotal');
const taxEl = document.getElementById('tax');
const cashInput = document.getElementById('cashGiven');
const changeEl = document.getElementById('change');
const productsInput = document.getElementById('products');

// =============================
// ADD TO CART (STATE BASED)
// =============================
function addToCart(product) {

    const cart = window.POS.store.cart;

    const existing = cart.find(i => i.id == product.id);

    if (existing) {
        if (existing.quantity + 1 > product.stock) {
            Swal.fire('Stock limit', 'Cannot exceed stock', 'warning');
            return;
        }
        existing.quantity++;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: parseFloat(product.price),
            quantity: 1,
            stock: product.stock ?? 0
        });
    }

    window.dispatchEvent(new Event("cartUpdated"));
}

// =============================
// REMOVE ITEM
// =============================
function removeItem(index) {
    window.POS.store.cart.splice(index, 1);
    window.dispatchEvent(new Event("cartUpdated"));
}

// =============================
// UPDATE QUANTITY
// =============================
function updateQty(index, value) {

    const item = window.POS.store.cart[index];

    let q = parseInt(value) || 1;

    if (q > item.stock) {
        Swal.fire('Stock limit', 'Cannot exceed stock', 'warning');
        q = item.stock;
    }

    item.quantity = q;

    window.dispatchEvent(new Event("cartUpdated"));
}

// =============================
// RENDER CART (UI ONLY)
// =============================
function renderCart() {

    const cart = window.POS.store.cart;

    if (!cartBody) return;

    cartBody.innerHTML = '';

    if (!cart.length) {
        cartBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4 text-gray-400">
                    Cart is empty
                </td>
            </tr>
        `;

        if (subtotalEl) subtotalEl.innerText = '0.00';
        if (taxEl) taxEl.innerText = '0.00';
        if (changeEl) changeEl.innerText = '0.00';
        if (productsInput) productsInput.value = '';

        return;
    }

    cart.forEach((item, i) => {

        const total = item.price * item.quantity;

        const row = document.createElement('tr');

        row.innerHTML = `
            <td>${item.name}</td>
            <td>KES ${item.price.toFixed(2)}</td>
            <td>
                <input type="number" min="1" max="${item.stock}" value="${item.quantity}" class="w-16 border rounded px-2 qtyInput">
            </td>
            <td>KES ${total.toFixed(2)}</td>
            <td>
                <button class="bg-red-500 text-white px-2 py-1 rounded removeBtn">
                    Remove
                </button>
            </td>
        `;

        // REMOVE
        row.querySelector('.removeBtn').onclick = () => removeItem(i);

        // UPDATE QTY
        row.querySelector('.qtyInput').onchange = (e) => updateQty(i, e.target.value);

        cartBody.appendChild(row);
    });

    calculateTotals();
}

// =============================
// TOTALS
// =============================
function calculateTotals() {

    const cart = window.POS.store.cart;

    const subtotal = cart.reduce((sum, item) =>
        sum + item.price * item.quantity, 0
    );

    const tax = 0;

    if (subtotalEl) subtotalEl.innerText = subtotal.toFixed(2);
    if (taxEl) taxEl.innerText = tax.toFixed(2);

    if (productsInput) {
        productsInput.value = JSON.stringify(window.POS.store.cart);
    }

    calculateChange();
}

// =============================
// CHANGE CALCULATION
// =============================
function calculateChange() {

    if (!cashInput || !subtotalEl || !changeEl) return;

    const cash = parseFloat(cashInput.value) || 0;
    const subtotal = parseFloat(subtotalEl.innerText) || 0;
    const tax = parseFloat(taxEl.innerText) || 0;

    const total = subtotal + tax;
    const change = cash - total;

    changeEl.innerText = change >= 0 ? change.toFixed(2) : '0.00';
}

// =============================
// LISTEN TO STORE EVENTS (NEW)
// =============================
window.addEventListener("cartUpdated", renderCart);

// =============================
// CASH INPUT
// =============================
if (cashInput) {
    cashInput.addEventListener('input', calculateChange);
}

// =============================
// PUBLIC API
// =============================
window.POS.cart = {
    add: addToCart,
    render: renderCart,
    get: () => window.POS.store.cart
};
console.log("Cart module initialized");

// =============================
// GLOBAL CART STATE
// =============================
window.cart = window.cart || [];

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
// ADD TO CART (CORE FUNCTION)
// =============================
function addToCart(product) {

    const existing = window.cart.find(i => i.id == product.id);

    if (existing) {
        if (existing.quantity + 1 > product.stock) {
            Swal.fire('Stock limit', 'Cannot exceed stock', 'warning');
            return;
        }
        existing.quantity++;
    } else {
        window.cart.push({
            id: product.id,
            name: product.name,
            price: parseFloat(product.price),
            quantity: 1,
            stock: product.stock ?? 0
        });
    }

    renderCart();
}

// =============================
// RENDER CART
// =============================
function renderCart() {

    if (!cartBody) return;

    cartBody.innerHTML = '';

    if (!window.cart.length) {
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

    window.cart.forEach((item, i) => {

        const total = item.price * item.quantity;

        const row = document.createElement('tr');

        row.innerHTML = `
            <td>${item.name}</td>
            <td>KES ${item.price.toFixed(2)}</td>
            <td>
                <input type="number" min="1" max="${item.stock}" value="${item.quantity}" class="w-16 border rounded px-2">
            </td>
            <td>KES ${total.toFixed(2)}</td>
            <td>
                <button class="bg-red-500 text-white px-2 py-1 rounded">
                    Remove
                </button>
            </td>
        `;

        // REMOVE ITEM
        row.querySelector('button').onclick = () => {
            window.cart.splice(i, 1);
            renderCart();
        };

        // UPDATE QUANTITY
        row.querySelector('input').onchange = (e) => {

            let q = parseInt(e.target.value) || 1;

            if (q > item.stock) {
                Swal.fire('Stock limit', 'Cannot exceed stock', 'warning');
                q = item.stock;
            }

            item.quantity = q;
            renderCart();
        };

        cartBody.appendChild(row);
    });

    calculateTotals();
}

// =============================
// CALCULATE TOTALS
// =============================
function calculateTotals() {

    const TAX_RATE = 0;

    const subtotal = window.cart.reduce((sum, item) =>
        sum + item.price * item.quantity, 0
    );

    const tax = subtotal * TAX_RATE;

    if (subtotalEl) subtotalEl.innerText = subtotal.toFixed(2);
    if (taxEl) taxEl.innerText = tax.toFixed(2);

    if (productsInput) {
        productsInput.value = JSON.stringify(window.cart);
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
// CASH INPUT LISTENER
// =============================
if (cashInput) {
    cashInput.addEventListener('input', calculateChange);
}

// =============================
// GLOBAL EXPORT (MODULE API)
// =============================
window.POS = window.POS || {};

window.POS.cart = {
    add: addToCart,
    render: renderCart,
    get: () => window.cart
};
// resources/js/pos/products/search.js

console.log("Product search module loaded");

// =============================
// ELEMENTS
// =============================
const searchInput = document.getElementById('productSearch');
const addBtn = document.getElementById('addProductBtn');
const suggestions = document.getElementById('suggestions');

// =============================
// STATE
// =============================
let debounceTimeout = null;
let selectedIndex = -1;
let currentResults = [];

// =============================
// DEVICE ID (ONLY FROM CORE MODULE)
// =============================
const deviceId = window.POS?.device?.getId?.() || window.deviceId;

// =============================
// FETCH PRODUCTS
// =============================
async function fetchProducts(query) {
   
    if (!query) return [];

    // =============================
    // 1. ALWAYS TRY LOCAL FIRST (FAST UI)
    // =============================
    let localResults = [];

    if (window.POS?.db?.searchProducts) {
        localResults = await window.POS.db.searchProducts(query);
    }

    // If offline → return instantly
    if (!navigator.onLine) {
        console.log("📴 Offline → using IndexedDB");
        return localResults;
    }

    // =============================
    // 2. TRY NETWORK (REFRESH CACHE)
    // =============================
    try {

        let res = await fetch(`/fetch/products?query=${encodeURIComponent(query)}`, {
            headers: {
                'X-DEVICE-ID': deviceId,
                'Accept': 'application/json'
            }
        });

        if (res.ok) {
            const data = await res.json();

            if (Array.isArray(data) && data.length) {

                // ✅ Update cache
                await window.POS.db.saveProducts(data);

                return data;
            }
        }

        // =============================
        // 3. FALLBACK (BARCODE / EXACT)
        // =============================
        res = await fetch(`/fetch/products/${encodeURIComponent(query)}`, {
            headers: {
                'X-DEVICE-ID': deviceId,
                'Accept': 'application/json'
            }
        });

        let product = await res.json();

        if (product && !Array.isArray(product)) {
            product = [product];
        }

        if (product?.length) {
            await window.POS.db.saveProducts(product);
            return product;
        }

    } catch (err) {

        console.warn("⚠ Network failed → fallback to cache");
    }

    // =============================
    // 4. FINAL FALLBACK (CACHE)
    // =============================
    return localResults;
}
// =============================
// RENDER SUGGESTIONS
// =============================
function renderSuggestions(list) {

    suggestions.innerHTML = '';
    selectedIndex = -1;
    currentResults = list;

    if (!list.length) {
        suggestions.classList.add('hidden');
        return;
    }

    list.forEach((p) => {

        const li = document.createElement('li');

        li.textContent = `${p.name} — ${p.stock ?? 0} in stock — KES ${parseFloat(p.price).toFixed(2)}`;

        li.className = 'px-4 py-2 cursor-pointer hover:bg-indigo-100';

        li.onclick = () => {
            addToCart(p);
            closeSuggestions();
        };

        suggestions.appendChild(li);
    });

    suggestions.classList.remove('hidden');
}

// =============================
// CLOSE
// =============================
function closeSuggestions() {
    suggestions.innerHTML = '';
    suggestions.classList.add('hidden');

    if (searchInput) searchInput.value = '';

    selectedIndex = -1;
    currentResults = [];
}

// =============================
// INPUT SEARCH
// =============================
searchInput?.addEventListener('input', function () {

    const query = this.value.trim();

    if (!query) {
        closeSuggestions();
        return;
    }

    clearTimeout(debounceTimeout);

    debounceTimeout = setTimeout(async () => {
        const data = await fetchProducts(query);
        renderSuggestions(data);
    }, 200);
});

// =============================
// KEYBOARD NAV
// =============================
searchInput?.addEventListener('keydown', function (e) {

    const items = suggestions.querySelectorAll('li');

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (!items.length) return;
        selectedIndex = (selectedIndex + 1) % items.length;
        updateHighlight(items);
    }

    if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (!items.length) return;
        selectedIndex = (selectedIndex - 1 + items.length) % items.length;
        updateHighlight(items);
    }

    if (e.key === 'Enter') {
        e.preventDefault();

        if (selectedIndex >= 0 && currentResults[selectedIndex]) {
            addToCart(currentResults[selectedIndex]);
            closeSuggestions();
        } else {
            addBtn?.click();
        }
    }

    if (e.key === 'Escape') {
        closeSuggestions();
    }
});

// =============================
// HIGHLIGHT
// =============================
function updateHighlight(items) {

    items.forEach(li => li.classList.remove('bg-indigo-200'));

    if (selectedIndex >= 0) {
        items[selectedIndex].classList.add('bg-indigo-200');
        items[selectedIndex].scrollIntoView({ block: 'nearest' });
    }
}

// =============================
// ADD BUTTON
// =============================
addBtn?.addEventListener('click', async () => {

    const query = searchInput.value.trim();
    if (!query) return;

    const products = await fetchProducts(query);

    if (!products.length) {
        Swal.fire('Not found', 'Product not found', 'warning');
        return;
    }

    addToCart(products[0]);
    closeSuggestions();
});

// =============================
// CART INTEGRATION
// =============================
function addToCart(product) {

    if (window.POS?.cart?.add) {
        window.POS.cart.add(product);
    } else {
        console.warn("Cart module not ready");
    }
}

// =============================
// OUTSIDE CLICK
// =============================
document.addEventListener('click', e => {

    if (!searchInput?.contains(e.target) && !suggestions?.contains(e.target)) {
        closeSuggestions();
    }
});
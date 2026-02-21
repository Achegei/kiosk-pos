document.addEventListener("DOMContentLoaded", function () {

    // ---------------- DEVICE UUID ----------------
    if (!localStorage.getItem('device_uuid'))
        localStorage.setItem('device_uuid', crypto.randomUUID());

    const deviceId = localStorage.getItem('device_uuid');

    // ---------------- ELEMENTS ----------------
    const cartBody = document.getElementById('cartBody');
    const subtotalEl = document.getElementById('subtotal');
    const taxEl = document.getElementById('tax');
    const cashInput = document.getElementById('cashGiven');
    const changeEl = document.getElementById('change');
    const searchInput = document.getElementById('productSearch');
    const addBtn = document.getElementById('addProductBtn');
    const suggestions = document.getElementById('suggestions');
    const form = document.getElementById('checkoutForm');
    const productsInput = document.getElementById('products');
    const checkoutBtn = form.querySelector('button[type="submit"]');

    // ---------------- GLOBAL CART ----------------
    window.cart = [];
    let debounceTimeout=null, selectedIndex=-1, currentResults=[],checkoutPending = false;


    // ---------------- FETCH PRODUCTS ----------------
    async function fetchProducts(query){

        if(!query) return [];

        try{

            let res = await fetch(`/products/search?query=${encodeURIComponent(query)}`,{
                headers:{'X-DEVICE-ID':deviceId,'Accept':'application/json'}
            });

            if(res.ok){
                let data = await res.json();
                if(data.length) return data;
            }

            res = await fetch(`/products/barcode/${encodeURIComponent(query)}`,{
                headers:{'X-DEVICE-ID':deviceId,'Accept':'application/json'}
            });

            let product = await res.json();

            if(product && !Array.isArray(product))
                product=[product];

            return product.length ? product : [];

        }catch(e){

            console.error("Fetch error:",e);
            return [];

        }
    }


    // ---------------- SUGGESTIONS ----------------
    function renderSuggestions(list){

        suggestions.innerHTML='';
        selectedIndex=-1;
        currentResults=list;

        if(!list.length){
            suggestions.classList.add('hidden');
            return;
        }

        list.forEach((p,i)=>{

            const li=document.createElement('li');

            li.textContent =
            `${p.name} — ${p.stock ?? 0} in stock — KES ${parseFloat(p.price).toFixed(2)}`;

            li.className='px-4 py-2 cursor-pointer hover:bg-indigo-100';

            li.onclick=()=>{
                addToCart(p);
                closeSuggestions();
            };

            suggestions.appendChild(li);

        });

        suggestions.classList.remove('hidden');
    }


    function closeSuggestions(){

        suggestions.innerHTML='';
        suggestions.classList.add('hidden');

        searchInput.value='';
        selectedIndex=-1;
        currentResults=[];
    }


    // ---------------- SEARCH ----------------
    searchInput.addEventListener('input',function(){

        const query=this.value.trim();

        if(!query){
            closeSuggestions();
            return;
        }

        clearTimeout(debounceTimeout);

        debounceTimeout=setTimeout(async ()=>{

            const data = await fetchProducts(query);
            renderSuggestions(data);

        },200);
    });


    // ---------------- KEYBOARD NAV ----------------
    searchInput.addEventListener('keydown',function(e){

        const items=suggestions.querySelectorAll('li');

        if(e.key==='ArrowDown'){
            e.preventDefault();
            if(!items.length) return;
            selectedIndex=(selectedIndex+1)%items.length;
            updateHighlight(items);
        }

        else if(e.key==='ArrowUp'){
            e.preventDefault();
            if(!items.length) return;
            selectedIndex=(selectedIndex-1+items.length)%items.length;
            updateHighlight(items);
        }

        else if(e.key==='Enter'){
            e.preventDefault();

            if(selectedIndex>=0 && currentResults[selectedIndex]){
                addToCart(currentResults[selectedIndex]);
                closeSuggestions();
            }else{
                addBtn.click();
            }
        }

        else if(e.key==='Escape'){
            closeSuggestions();
        }
    });


    function updateHighlight(items){

        items.forEach(li=>li.classList.remove('bg-indigo-200'));

        if(selectedIndex>=0){
            items[selectedIndex].classList.add('bg-indigo-200');
            items[selectedIndex].scrollIntoView({block:'nearest'});
        }
    }


    // ---------------- ADD BUTTON ----------------
    addBtn.addEventListener('click', async ()=>{

        const query=searchInput.value.trim();
        if(!query) return;

        const products = await fetchProducts(query);

        if(!products.length){
            Swal.fire('Not found','Product not found','warning');
            return;
        }

        addToCart(products[0]);
        closeSuggestions();
    });


    // ---------------- ADD TO CART ----------------
    function addToCart(product){

        const existing = window.cart.find(i=>i.id==product.id);

        if(existing){

            if(existing.quantity+1>product.stock){
                Swal.fire('Stock limit','Cannot exceed stock','warning');
                return;
            }

            existing.quantity++;

        }else{

            window.cart.push({
                id:product.id,
                name:product.name,
                price:parseFloat(product.price),
                quantity:  1,
                stock:product.stock ?? 0
            });
        }

        renderCart();
    }


            // ---------------- RENDER CART ----------------
        function renderCart() {
            cartBody.innerHTML = '';

            if (!window.cart.length) {
                cartBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-4 text-gray-400">Cart is empty</td>
                    </tr>
                `;
                subtotalEl.innerText = '0.00';
                taxEl.innerText = '0.00';
                changeEl.innerText = '0.00';
                productsInput.value = '';
                return;
            }

            // Build rows
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
                        <button class="bg-red-500 text-white px-2 py-1 rounded">Remove</button>
                    </td>
                `;

                // Remove item
                row.querySelector('button').onclick = () => {
                    window.cart.splice(i, 1);
                    renderCart();
                };

                // Quantity change
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

            // ===== ENRICH: Totals include offline items =====
            const subtotal = window.cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
            subtotalEl.innerText = subtotal.toFixed(2);
            taxEl.innerText = (subtotal * 0).toFixed(2); // currently 0%
            productsInput.value = JSON.stringify(window.cart);

            calculateChange();
        }


    // ---------------- CHANGE ----------------
    function calculateChange(){

        const cash=parseFloat(cashInput.value)||0;
        const subtotal=parseFloat(subtotalEl.innerText)||0;

        const change=cash-subtotal;

        changeEl.innerText=change>=0?change.toFixed(2):'0.00';
    }

    cashInput.addEventListener('input',calculateChange);


    // ---------------- CHECKOUT ----------------
form.addEventListener('submit', async function(e){
    e.preventDefault();

    try{
        if(!window.cart.length){
            Swal.fire('Cart empty','','warning');
            return;
        }

        if (checkoutPending) {
            Swal.fire('Processing...','Please wait for the current sale to complete.','info');
            return;
        }

        checkoutPending = true;

        const method=document.getElementById('payment_method').value;
        const subtotal=parseFloat(subtotalEl.innerText);
        let mpesaCode=null;
        let cashGiven = parseFloat(cashInput.value) || 0;

        /* ================= CASH ================= */
        if(method==="Cash"){
            if(!cashGiven || cashGiven<subtotal){
                Swal.fire({icon:'error', title:'Cash required', text:'Customer must give enough cash'});
                checkoutPending = false;
                return;
            }
        }

        /* ================= MPESA ================= */
        if(method==="Mpesa"){
            const result = await Swal.fire({
                title:'Enter Mpesa Confirmation Code',
                input:'text',
                inputPlaceholder:'Example: QWE123ABC',
                confirmButtonText:'Confirm Payment',
                showCancelButton:true
            });

            if(!result.value){
                Swal.fire('Mpesa code required','','error');
                checkoutPending = false;
                return;
            }

            mpesaCode=result.value;
            cashGiven = subtotal; // treat MPESA as fully paid
        }

        /* ================= CREDIT ================= */
        if(method==="Credit"){
            const customer=document.getElementById('customer').value;
            if(!customer){
                Swal.fire({icon:'error', title:'Select customer for credit sale'});
                checkoutPending = false;
                return;
            }
            cashGiven = 0; // credit sale, nothing collected
        }

        /* ================= SEND ================= */
        const payload={
            _token:document.querySelector('input[name=_token]').value,
            customer_id:document.getElementById('customer').value,
            payment_method:method,
            mpesa_code:mpesaCode,
            products:window.cart.map(p=>({id:p.id, quantity:p.quantity}))
        };

        const res=await fetch(form.action,{
            method:'POST',
            headers:{'Content-Type':'application/json','Accept':'application/json'},
            body:JSON.stringify(payload)
        });

        const data=await res.json();

        if(!data.success){
            Swal.fire('Checkout failed',JSON.stringify(data.message),'error');
            checkoutPending = false;
            return;
        }

        /* 🔥 ADD THIS LINE RIGHT HERE */
        window.dispatchEvent(new Event('transactionCompleted'));

        /* ================= AUTO-PRINT RECEIPT ================= */
        const receiptData = {
            ...data.receipt,
            user: window.currentUserName || "Staff", // ensure staff name is included
            cash: cashGiven,
            change: Math.max(0, cashGiven - subtotal)
        };

        // ✅ AUTO PRINT immediately
        window.printReceipt(receiptData);

        // ✅ Optional: show quick success alert (non-blocking)
        Swal.fire({
            icon:'success',
            title:'SALE COMPLETED',
            html:`
                <div style="font-size:18px">
                    🧾 Receipt: <b>#${receiptData.id}</b><br>
                    💰 Total: <b>KES ${Number(receiptData.total).toFixed(2)}</b><br>
                    💵 Paid: <b>KES ${Number(receiptData.cash).toFixed(2)}</b><br>
                    🔁 Change: <b>KES ${Number(receiptData.change).toFixed(2)}</b>
                </div>
            `,
            timer:2000,
            showConfirmButton:false,
            background:'#f0fdf4'
        });

                /* ================= REGISTER TRACKING FIX ================= */

        // calculate subtotal again
        const subtotalValue = parseFloat(subtotalEl.innerText) || 0;

        // store sale for register totals
        let queue = JSON.parse(localStorage.getItem('offline_sales_queue') || '[]');

        queue.push({
            subtotal: subtotalValue,
            payment_method: method,
            customer_id: document.getElementById('customer').value || null,
            customer_name: document.querySelector('#customer option:checked')?.text || 'Walk-in'
        });

        localStorage.setItem('offline_sales_queue', JSON.stringify(queue));

        // 🔥 notify register modal to refresh totals
        window.dispatchEvent(new Event('transactionCompleted'));


        /* ================= NORMAL RESET ================= */
        window.cart=[];
        renderCart();
        cashInput.value='';


        checkoutPending = false;
        if (checkoutBtn) checkoutBtn.disabled = false;

    } catch(err){
        console.error(err);
        Swal.fire('Checkout crashed','See console','error');
        checkoutPending = false;
        if (checkoutBtn) checkoutBtn.disabled = false;
    }
});

// ---------------- PRINT RECEIPT ----------------
window.printReceipt = function(receipt){

const STORE = {
    name: "SMART SHOP SUPERMARKET",
    address: "Kiambu Road, Nairobi",
    phone: "0712345678",
    footer: "Thank you for shopping with us!"
};

// ✅ FORCE STAFF NAME FROM GLOBAL (fallback safe)
const staffName = receipt.user || window.currentUserName || "Staff";

// ✅ CASH + CHANGE SAFE VALUES
const cashTaken = receipt.cash ?? document.getElementById('cashGiven')?.value ?? 0;
const changeGiven = receipt.change ?? document.getElementById('change')?.innerText ?? 0;

let items = '';

receipt.items?.forEach(item=>{
    items += `
        <div class="row">
            <div>${item.name}</div>
            <div>${item.qty} x ${item.price}</div>
        </div>
        <div class="row total">
            <div></div>
            <div>KES ${item.total}</div>
        </div>
    `;
});

const html = `
<html>
<head>
<title>Receipt</title>

<style>
body{
    font-family: monospace;
    width:80mm;
    margin:auto;
    padding:10px;
}
.center{text-align:center;}
.row{display:flex;justify-content:space-between;font-size:13px;}
.total{font-weight:bold;}
hr{border-top:1px dashed #000;}
.big{
    font-size:18px;
    font-weight:bold;
    text-align:center;
    margin-top:10px;
}
</style>

</head>

<body>

<div class="center">
    <div style="font-size:20px;font-weight:bold">${STORE.name}</div>
    ${STORE.address}<br>
    Tel: ${STORE.phone}
</div>

<hr>

Receipt #: ${receipt.id}<br>
Date: ${new Date().toLocaleString('en-KE',{timeZone:'Africa/Nairobi'})}<br>

<!-- ✅ NEW PROFESSIONAL TEXT -->
You were served by: ${staffName}

<hr>

${items}

<hr>

<div class="big">
TOTAL: KES ${receipt.total}
</div>

Paid via: ${receipt.payment ?? 'Cash'}<br>
Cash received: KES ${cashTaken}<br>
Change given: KES ${changeGiven}

<hr>

<div class="center">
${STORE.footer}
</div>

</body>
</html>
`;

const w = window.open('', '', 'width=340,height=700');

w.document.write(html);
w.document.close();

setTimeout(()=>{
    w.focus();
    w.print();
},400);

};


    // ---------------- OUTSIDE CLICK ----------------
    document.addEventListener('click',e=>{
        if(!searchInput.contains(e.target) && !suggestions.contains(e.target))
            closeSuggestions();
    });

});

// QUICK CREATE CUSTOMER FROM POS
const newCustomerBtn = document.getElementById('newCustomerBtn');

if(newCustomerBtn){

newCustomerBtn.onclick = async ()=>{

    const result = await Swal.fire({

        title:'New Customer',

        html:`
            <input id="cname" class="swal2-input" placeholder="Customer name">
            <input id="cphone" class="swal2-input" placeholder="Phone">
            <input id="cemail" class="swal2-input" placeholder="Email optional">
        `,

        confirmButtonText:'Create',

        preConfirm:()=>({
            name:document.getElementById('cname').value,
            phone:document.getElementById('cphone').value,
            email:document.getElementById('cemail').value
        })

    });

    if(!result.value) return;

    const res = await fetch('/pos/customer-quick-create',{
        method:'POST',
        headers:{
            'Content-Type':'application/json',
            'X-CSRF-TOKEN':document.querySelector('input[name=_token]').value
        },
        body:JSON.stringify(result.value)
    });

    const customer = await res.json();

    const select=document.getElementById('customer');

    const opt=new Option(
        customer.name+" ("+(customer.phone??'')+")",
        customer.id,
        true,
        true
    );

    select.appendChild(opt);

    Swal.fire('Customer added','','success');

};

}

// ---------------- OFFLINE SUPPORT ----------------
const offlineQueueKey = 'offline_sales_queue';

/**
 * Save sale locally if offline with type-safety and logging
 */
function saveOfflineSale(sale) {
    if (!sale || !Array.isArray(sale.items) || sale.items.length === 0) {
        console.error('Invalid sale object, cannot save offline', sale);
        return;
    }

    let queue = JSON.parse(localStorage.getItem(offlineQueueKey) || '[]');

    // Add unique local ID and synced flag
    const enrichedSale = {
        ...sale,
        local_id: crypto.randomUUID(),
        synced: false,
        timestamp: new Date().toISOString(),
        device_uuid: deviceId
    };

    queue.push(enrichedSale);
    localStorage.setItem(offlineQueueKey, JSON.stringify(queue));

    console.log('Offline sale saved:', enrichedSale);
}

/**
 * Try to sync offline queue when online
 * Partial success handling & logging added
 */
async function syncOfflineSales() {
    let queue = JSON.parse(localStorage.getItem(offlineQueueKey) || '[]');
    if (!queue.length) return;

    const unsynced = [];

    for (let sale of queue) {
        if (sale.synced) continue;

        try {
            const res = await fetch('/api/offline-sync', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({sales: [sale]})
            });

            const data = await res.json();
            if (data.status === 'success') {
                sale.synced = true;
                console.log('Sale synced:', sale.local_id);
            } else {
                unsynced.push(sale);
                console.warn('Sale not synced, server returned error:', data);
            }
        } catch (err) {
            unsynced.push(sale);
            console.error('Sync failed for sale', sale.local_id, err);
        }
    }

    localStorage.setItem(offlineQueueKey, JSON.stringify(unsynced));

    if (unsynced.length === 0 && queue.length > 0) {
        alert('Offline sales synced successfully!');
    }
}

// Trigger sync on network reconnect
window.addEventListener('online', syncOfflineSales);


/**
 * Complete sale with offline fallback
 * Enrich payload with type-safety and device info
 */
function completeSale(cart, customerId = null, paymentMethod = 'Cash', mpesaCode = null) {
    const salePayload = {
        customer_id: customerId,
        payment_method: paymentMethod,
        mpesa_code: mpesaCode,
        items: cart.map(p => ({product_id: p.id, quantity: p.quantity, price: p.price})),
        subtotal: cart.reduce((sum, p) => sum + p.price * p.quantity, 0),
        timestamp: new Date().toISOString(),
        device_uuid: deviceId
    };

    if (navigator.onLine) {
        fetch('/api/checkout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value
            },
            body: JSON.stringify(salePayload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                console.log('Sale completed online:', data.receipt.id);
                Swal.fire('Sale completed online!', `Receipt: #${data.receipt.id}`, 'success');
            } else {
                console.warn('Server rejected sale, saving offline', data);
                saveOfflineSale(salePayload);
            }
        })
        .catch(err => {
            console.error('Online sale failed, saving offline', err);
            saveOfflineSale(salePayload);
        });
    } else {
        saveOfflineSale(salePayload);
        console.log('Offline, sale queued:', salePayload.local_id);
        Swal.fire('Offline Sale', 'Sale saved offline! Will sync when online.', 'info');
    }
}

/**
 * Optional: show offline/online status & unsynced count in POS UI
 */
function updateOfflineStatus() {
    const queue = JSON.parse(localStorage.getItem(offlineQueueKey) || '[]');
    const statusEl = document.getElementById('offlineStatus'); // create <span id="offlineStatus"></span> in HTML

    if (!statusEl) return;

    const unsyncedCount = queue.filter(s => !s.synced).length;

    if (navigator.onLine) {
        statusEl.textContent = `Online | Unsynced: ${unsyncedCount}`;
        statusEl.classList.add('text-green-600');
        statusEl.classList.remove('text-red-600');
    } else {
        statusEl.textContent = `Offline | Unsynced: ${unsyncedCount}`;
        statusEl.classList.add('text-red-600');
        statusEl.classList.remove('text-green-600');
    }
}

window.addEventListener('online', updateOfflineStatus);
window.addEventListener('offline', updateOfflineStatus);
setInterval(updateOfflineStatus, 5000);

//network check
const networkStatus = document.getElementById('networkStatus');

function updateNetworkStatus() {
    if (!networkStatus) return; // safety

    if (navigator.onLine) {
        networkStatus.innerText = 'ONLINE';
        networkStatus.classList.remove('bg-red-500');
        networkStatus.classList.add('bg-green-500');
    } else {
        networkStatus.innerText = 'OFFLINE';
        networkStatus.classList.remove('bg-green-500');
        networkStatus.classList.add('bg-red-500');
    }
}

// Initial check
updateNetworkStatus();

// Listen for network changes
window.addEventListener('online', updateNetworkStatus);
window.addEventListener('offline', updateNetworkStatus);

//barcode scanner support
// ---------------- BARCODE AUTO-FOCUS & STAY-READY ----------------
const productSearch = document.getElementById('productSearch');

// Auto-focus on page load
window.addEventListener('load', () => {
    if (productSearch) {
        productSearch.focus();
    }
});

// Stay-ready mode: after adding product, cursor stays in search box
function focusBarcodeInput() {
    if (productSearch) {
        productSearch.focus();
        productSearch.select(); // highlight in case cashier wants to rescan
    }
}

// Call focusBarcodeInput() after product is added to cart
document.getElementById('addProductBtn')?.addEventListener('click', () => {
    // existing add product logic runs...
    // then:
    setTimeout(focusBarcodeInput, 50); // tiny delay ensures UI updates first
});

// Also call after scanning via "Enter" or "Barcode scan" event
productSearch?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();

        // Trigger add product logic
        document.getElementById('addProductBtn')?.click();

        // Re-focus immediately
        setTimeout(focusBarcodeInput, 50);
    }
});


//calculator support
document.querySelectorAll('.calcBtn').forEach(btn=>{
    btn.addEventListener('click', ()=>{
        const input = document.getElementById('quickCalcInput');
        const val = btn.textContent;

        if(val === 'C'){
            input.value = '';
            return;
        }

        if(val === '='){
            try {
                // Safe eval: only digits, operators, dot
                const safe = input.value.replace(/[^0-9+\-*/.]/g,'');
                input.value = safe ? eval(safe) : '';
            } catch {
                input.value = 'Error';
            }
            return;
        }

        input.value += val;
    });
});

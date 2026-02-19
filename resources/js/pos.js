document.addEventListener("DOMContentLoaded", function () {

    // ---------------- DEVICE UUID ----------------
    if (!localStorage.getItem('device_uuid')) localStorage.setItem('device_uuid', crypto.randomUUID());
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
    const checkoutForm = document.getElementById('checkoutForm');
    const hiddenCustomer = document.getElementById('customer_id');
    const customerSelect = document.getElementById('customer');
    const productsInput = document.getElementById('products');

    let cart = [], debounceTimeout=null, selectedIndex=-1, currentResults=[];

    // ---------------- FETCH PRODUCTS ----------------
    async function fetchProducts(query){
        if(!query) return [];
        try {
            let res = await fetch(`/products/search?query=${encodeURIComponent(query)}`, {
                headers: { 'X-DEVICE-ID': deviceId, 'Accept': 'application/json' }
            });
            if(res.ok){ let data = await res.json(); if(data.length) return data; }
            res = await fetch(`/products/barcode/${encodeURIComponent(query)}`, {
                headers: { 'X-DEVICE-ID': deviceId, 'Accept': 'application/json' }
            });
            let product = await res.json();
            if(product && !Array.isArray(product)) product=[product];
            return product.length ? product : [];
        } catch(e){ console.error("Fetch error:", e); return []; }
    }

    // ---------------- SUGGESTIONS ----------------
    function renderSuggestions(list){
        suggestions.innerHTML=''; selectedIndex=-1; currentResults=list;
        if(!list.length){ suggestions.classList.add('hidden'); return; }
        list.forEach((p,i)=>{
            const li=document.createElement('li');
            li.textContent = `${p.name} — ${p.stock ?? 0} in stock — KES ${parseFloat(p.price).toFixed(2)}`;
            li.className='px-4 py-2 cursor-pointer hover:bg-indigo-100';
            li.addEventListener('click',()=>{ addToCart(p); closeSuggestions(); });
            suggestions.appendChild(li);
        });
        suggestions.classList.remove('hidden');
    }

    function closeSuggestions(){
        suggestions.innerHTML=''; suggestions.classList.add('hidden');
        searchInput.value=''; selectedIndex=-1; currentResults=[];
    }

    // ---------------- SEARCH INPUT ----------------
    searchInput.addEventListener('input', function(){
        const query=this.value.trim(); if(!query){ closeSuggestions(); return; }
        clearTimeout(debounceTimeout);
        debounceTimeout=setTimeout(async ()=>{
            const data = await fetchProducts(query);
            renderSuggestions(data);
        },200);
    });

    // ---------------- KEYBOARD NAV ----------------
    searchInput.addEventListener('keydown', function(e){
        const items = suggestions.querySelectorAll('li');
        if(e.key==='ArrowDown'){ e.preventDefault(); if(!items.length) return; selectedIndex=(selectedIndex+1)%items.length; updateHighlight(items); }
        else if(e.key==='ArrowUp'){ e.preventDefault(); if(!items.length) return; selectedIndex=(selectedIndex-1+items.length)%items.length; updateHighlight(items); }
        else if(e.key==='Enter'){ e.preventDefault(); if(selectedIndex>=0 && currentResults[selectedIndex]){ addToCart(currentResults[selectedIndex]); closeSuggestions(); } else { addBtn.click(); } }
        else if(e.key==='Escape'){ closeSuggestions(); }
    });

    function updateHighlight(items){
        items.forEach(li=>li.classList.remove('bg-indigo-200'));
        if(selectedIndex>=0){ items[selectedIndex].classList.add('bg-indigo-200'); items[selectedIndex].scrollIntoView({block:'nearest'}); }
    }

    // ---------------- ADD BUTTON ----------------
    addBtn.addEventListener('click', async ()=>{
        const query=searchInput.value.trim(); if(!query) return;
        const products = await fetchProducts(query);
        if(!products.length){ alert("Product not found"); return; }
        addToCart(products[0]); closeSuggestions();
    });

    // ---------------- CART ----------------
    function addToCart(product){
        const existing=cart.find(i=>i.id==product.id);
        if(existing){ if(existing.quantity+1>product.stock){ alert("Cannot exceed stock"); return; } existing.quantity++; }
        else{ cart.push({id:product.id,name:product.name,price:parseFloat(product.price),quantity:1,stock:product.stock??0}); }
        renderCart();
    }

    function renderCart(){
        cartBody.innerHTML='';
        if(!cart.length){
            cartBody.innerHTML=`<tr><td colspan="5" class="text-center py-4 text-gray-400">Cart is empty</td></tr>`;
            subtotalEl.innerText='0.00'; taxEl.innerText='0.00'; changeEl.innerText='0.00'; productsInput.value=''; return;
        }

        let subtotal=0;
        cart.forEach((item,i)=>{
            const total=item.price*item.quantity; subtotal+=total;
            const row=document.createElement('tr');
            row.innerHTML=`
                <td>${item.name}</td>
                <td>KES ${item.price.toFixed(2)}</td>
                <td><input type="number" min="1" max="${item.stock}" value="${item.quantity}" class="w-16 border rounded px-2"></td>
                <td>KES ${total.toFixed(2)}</td>
                <td><button class="bg-red-500 text-white px-2 py-1 rounded">Remove</button></td>
            `;
            const removeBtn=row.querySelector('button');
            const qtyInput=row.querySelector('input');
            removeBtn.onclick=()=>{ cart.splice(i,1); renderCart(); };
            qtyInput.onchange=()=>{ let q=parseInt(qtyInput.value); if(q>item.stock){ alert("Cannot exceed stock"); q=item.stock; } item.quantity=q; renderCart(); };
            cartBody.appendChild(row);
        });

        subtotalEl.innerText=subtotal.toFixed(2); taxEl.innerText='0.00';
        productsInput.value=JSON.stringify(cart);
        calculateChange();
    }

    // ---------------- CASH → CHANGE ----------------
    function calculateChange(){
        const cash=parseFloat(cashInput.value)||0;
        const subtotal=parseFloat(subtotalEl.innerText)||0;
        const change=cash-subtotal;
        changeEl.innerText=change>=0 ? change.toFixed(2) : '0.00';
    }
    cashInput.addEventListener('input', calculateChange);

    // ---------------- CHECKOUT ----------------
    checkoutForm.addEventListener('submit', async function(e){
        e.preventDefault();
        if(!cart.length){ alert("Cart empty"); return; }
        hiddenCustomer.value=customerSelect.value||null;
        const payload={customer_id:hiddenCustomer.value,payment_method:document.getElementById('payment_method').value,products:cart.map(c=>({id:c.id,quantity:c.quantity}))};
        try{
            const res=await fetch(this.action,{
                method:'POST',
                headers:{
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN':document.querySelector('input[name="_token"]').value,
                    'Accept':'application/json',
                    'X-DEVICE-ID':deviceId
                },
                body:JSON.stringify(payload)
            });
            const data=await res.json();
            if(res.ok && data.success){
                alert("Sale completed"); cart=[]; renderCart(); customerSelect.value=''; hiddenCustomer.value=''; cashInput.value=''; document.getElementById('payment_method').value='Cash';
            } else { alert(data.message||"Checkout failed"); }
        } catch(err){ console.error(err); alert("Checkout error"); }
    });

    // ---------------- OUTSIDE CLICK ----------------
    document.addEventListener('click', e=>{
        if(!searchInput.contains(e.target) && !suggestions.contains(e.target)) closeSuggestions();
    });

});


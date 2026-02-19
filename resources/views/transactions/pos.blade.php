@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6">

    <h1 class="text-2xl font-bold mb-4">Point of Sale (POS)</h1>

    {{-- CUSTOMER --}}
    <div class="mb-4">
        <label class="block font-medium">Customer (Optional)</label>
        <select id="customer_id" class="border rounded px-3 py-2 w-full">
            <option value="">Walk-in</option>
            @foreach(App\Models\Customer::all() as $customer)
                <option value="{{ $customer->id }}">
                    {{ $customer->name }} (Credit: KSh {{ number_format($customer->credit,2) }})
                </option>
            @endforeach
        </select>
    </div>


    {{-- BARCODE INPUT --}}
    <input
        type="text"
        id="barcode_input"
        placeholder="Scan barcode then press Enter..."
        class="border rounded px-3 py-2 w-full mb-2">


    {{-- SEARCH INPUT --}}
    <div class="relative mb-4">
        <input
            type="text"
            id="product_search"
            placeholder="Search product name..."
            class="border rounded px-3 py-2 w-full">

        <ul id="search_results"
            class="absolute z-50 bg-white border w-full mt-1 rounded shadow max-h-60 overflow-auto hidden">
        </ul>
    </div>


    {{-- CART --}}
    <table class="min-w-full bg-white border mb-4">
        <thead class="bg-gray-100">
        <tr>
            <th class="border px-4 py-2">Product</th>
            <th class="border px-4 py-2">Price</th>
            <th class="border px-4 py-2">Qty</th>
            <th class="border px-4 py-2">Total</th>
            <th class="border px-4 py-2">Action</th>
        </tr>
        </thead>
        <tbody id="cart_body"></tbody>
    </table>


    <div class="flex justify-end mb-4">
        <h2 class="text-xl font-bold">
            Total: KSh <span id="cart_total">0.00</span>
        </h2>
    </div>


    <div class="flex gap-4">
        <button id="checkout_btn"
            class="bg-green-600 text-white px-6 py-2 rounded">
            Checkout Cash
        </button>

        <button id="checkout_mpesa_btn"
            class="bg-yellow-500 text-white px-6 py-2 rounded">
            Checkout Mpesa
        </button>
    </div>

</div>



<script>

const CSRF_TOKEN='{{ csrf_token() }}';
let cart=[];

const cartBody=document.getElementById('cart_body');
const searchInput=document.getElementById('product_search');
const searchResults=document.getElementById('search_results');
const barcodeInput=document.getElementById('barcode_input');



/* ================= RENDER CART ================= */

function renderCart(){

    cartBody.innerHTML='';
    let total=0;

    cart.forEach((item,index)=>{

        const line=item.price*item.quantity;
        total+=line;

        const color=item.stock===0?'red':item.stock<=5?'orange':'green';

        cartBody.innerHTML+=`
        <tr>
            <td>${item.name}<br>
            <small style="color:${color}">Stock: ${item.stock}</small></td>

            <td>${item.price.toFixed(2)}</td>

            <td>
            <input type="number"
                   class="qty border w-16 text-center"
                   value="${item.quantity}"
                   data-i="${index}">
            </td>

            <td>${line.toFixed(2)}</td>

            <td>
            <button class="remove bg-red-500 text-white px-2"
                    data-i="${index}">X</button>
            </td>
        </tr>`;
    });

    document.getElementById('cart_total').innerText=total.toFixed(2);


    document.querySelectorAll('.remove').forEach(btn=>{
        btn.onclick=e=>{
            cart.splice(e.target.dataset.i,1);
            renderCart();
        }
    });


    document.querySelectorAll('.qty').forEach(inp=>{
        inp.onchange=e=>{
            let i=e.target.dataset.i;
            let val=parseInt(e.target.value)||1;

            if(val>cart[i].stock) val=cart[i].stock;

            cart[i].quantity=val;
            renderCart();
        }
    });

}



/* ================= ADD TO CART ================= */

function addToCart(product){

    if(product.stock<=0) return alert('OUT OF STOCK');

    const exist=cart.find(x=>x.id==product.id);

    if(exist){
        if(exist.quantity+1>product.stock)
            return alert(`Only ${product.stock} left`);

        exist.quantity++;
    }else{
        cart.push({...product,quantity:1});
    }

    renderCart();
}



/* ================= SEARCH AUTOCOMPLETE ================= */

searchInput.addEventListener('input',async e=>{

    const q=e.target.value.trim();

    searchResults.innerHTML='';
    searchResults.style.display='none';

    if(!q) return;

    const res=await fetch(`/products/search?query=${q}`);
    const data=await res.json();

    if(!data.length) return;

    data.forEach(p=>{

        const li=document.createElement('li');

        li.className='px-3 py-2 hover:bg-gray-200 cursor-pointer';

        li.textContent=
        `${p.name} (KSh ${parseFloat(p.price).toFixed(2)} | Stock:${p.stock})`;

        li.onclick=()=>{
            addToCart({
                id:parseInt(p.id),
                name:p.name,
                price:parseFloat(p.price),
                stock:parseInt(p.stock)
            });

            searchInput.value='';
            searchResults.style.display='none';
        };

        searchResults.appendChild(li);
    });

    searchResults.style.display='block';

});



/* ================= BARCODE ================= */

barcodeInput.addEventListener('keypress',async e=>{

    if(e.key!=='Enter') return;

    const code=e.target.value.trim();
    if(!code) return;

    const res=await fetch(`/products/barcode/${code}`);
    const p=await res.json();

    if(p?.id){
        addToCart({
            id:parseInt(p.id),
            name:p.name,
            price:parseFloat(p.price),
            stock:parseInt(p.stock)
        });
    }else{
        alert('Product not found');
    }

    barcodeInput.value='';
});



/* ================= CHECKOUT ================= */

async function checkout(method){

    if(cart.length===0) return alert('Cart empty');

    const customer_id=document.getElementById('customer_id').value;

    const res=await fetch(`/transactions/pos-checkout`,{

        method:'POST',

        headers:{
            'Content-Type':'application/json',
            'X-CSRF-TOKEN':CSRF_TOKEN
        },

        body:JSON.stringify({cart,customer_id,method})
    });

    const data=await res.json();

    if(data.success){
        alert('SALE COMPLETE');
        cart=[];
        renderCart();
    }else{
        alert(data.message||'Error');
    }
}

document.getElementById('checkout_btn').onclick=()=>checkout('cash');
document.getElementById('checkout_mpesa_btn').onclick=()=>checkout('mpesa');

</script>

@endsection

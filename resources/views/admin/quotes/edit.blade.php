@extends('layouts.admin')

@section('content')
<div class="max-w-6xl mx-auto p-6 bg-white rounded shadow">
    <h1 class="text-xl font-bold mb-4">Edit Quote #{{ $quote->id }}</h1>

    @if(session('error'))
        <div class="bg-red-100 text-red-800 p-2 mb-4 rounded">{{ session('error') }}</div>
    @endif

    <form action="{{ route('quotes.update', $quote->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="font-semibold">Customer</label>
            <select name="customer_id" class="w-full border p-2 rounded">
                <option value="">Select Customer</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" 
                        {{ $quote->customer_id == $customer->id ? 'selected' : '' }}>
                        {{ $customer->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="font-semibold">Client Name</label>
            <input type="text" name="client_name" class="w-full border p-2 rounded" 
                value="{{ $quote->client_name }}">
        </div>

        <div class="mb-4">
            <label class="font-semibold">Tax %</label>
            <input type="number" id="tax" name="tax_percent" class="w-32 border p-1 rounded" 
                value="{{ $quote->tax_percent }}">
        </div>

        <div class="mb-4">
            <label class="font-semibold">Discount</label>
            <input type="number" id="discount" name="discount" class="w-32 border p-1 rounded" 
                value="{{ $quote->discount }}">
        </div>

        <div class="mb-4">
            <label class="font-semibold">Search Products</label>
            <input type="text" id="search" class="w-full border p-2 rounded" placeholder="Search products...">
            <div id="results" class="bg-white border rounded mt-1 max-h-60 overflow-y-auto"></div>
        </div>

        <div class="mb-4">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="border-b">
                        <th class="text-left p-2">Product</th>
                        <th class="text-left p-2">Qty</th>
                        <th class="text-left p-2">Price</th>
                        <th class="text-left p-2">Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="cart">
                    @foreach($quote->items as $i => $item)
                    <tr class="border-b">
                        <td>{{ $item->product->name }}</td>
                        <td><input type="number" value="{{ $item->quantity }}" min="1" 
                            onchange="cart[{{ $i }}].qty=parseInt(this.value)||1; renderCart()" class="border w-16 p-1"></td>
                        <td>{{ number_format($item->price,2) }}</td>
                        <td>{{ number_format($item->total,2) }}</td>
                        <td><button type="button" onclick="cart.splice({{ $i }},1); renderCart()" 
                            class="text-red-600 font-bold">X</button></td>
                        <input type="hidden" name="items[{{ $i }}][product_id]" value="{{ $item->product_id }}">
                        <input type="hidden" name="items[{{ $i }}][qty]" value="{{ $item->quantity }}">
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mb-4 text-right font-bold">
            Total: <span id="total">{{ number_format($quote->total_amount,2) }}</span>
        </div>

        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">Update Quote</button>
    </form>
</div>

@php
    $cartArray = $quote->items->map(function($item){
        return [
            'id' => $item->product_id,
            'name' => $item->product->name,
            'price' => (float) $item->price,
            'qty' => (int) $item->quantity,
        ];
    })->values();
@endphp

<script>
let cart = @json($cartArray);

const searchInput = document.getElementById('search');
const results = document.getElementById('results');
let debounceTimeout;

// ---------------- SEARCH ----------------
searchInput.addEventListener('input', () => {
    const query = searchInput.value.trim();
    if (!query) { 
        results.innerHTML = ''; 
        return; 
    }

    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(async () => {
        try {
            const res = await fetch(`/products/search?q=${encodeURIComponent(query)}`, {
                headers: { 'Accept':'application/json' }
            });
            if (!res.ok) return;
            const data = await res.json();
            renderSuggestions(data);
        } catch(err){ console.error(err); }
    },300);
});

function renderSuggestions(products) {
    results.innerHTML = '';
    products.forEach(p=>{
        const div = document.createElement('div');
        div.className = 'border p-2 rounded cursor-pointer hover:bg-indigo-50 flex justify-between';
        div.innerHTML = `<span>${p.name}</span><span>${parseFloat(p.price).toFixed(2)}</span>`;
        div.onclick = ()=>{ addToCart(p.id,p.name,p.price); results.innerHTML=''; searchInput.value=''; };
        results.appendChild(div);
    });
}

function addToCart(id,name,price){
    const existing = cart.find(x=>x.id==id);
    if(existing) existing.qty++; else cart.push({id,name,price,qty:1});
    renderCart();
}

function renderCart(){
    const cartBody = document.getElementById('cart');
    let html='', subtotal=0;
    cart.forEach((p,i)=>{
        const lineTotal = p.qty*p.price;
        subtotal+=lineTotal;
        html+=`<tr class="border-b">
            <td>${p.name}</td>
            <td><input type="number" value="${p.qty}" min="1" onchange="cart[${i}].qty=parseInt(this.value)||1; renderCart()" class="border w-16 p-1"></td>
            <td>${p.price.toFixed(2)}</td>
            <td>${lineTotal.toFixed(2)}</td>
            <td><button type="button" onclick="cart.splice(${i},1); renderCart()" class="text-red-600 font-bold">X</button></td>
            <input type="hidden" name="items[${i}][product_id]" value="${p.id}">
            <input type="hidden" name="items[${i}][qty]" value="${p.qty}">
        </tr>`;
    });
    const tax = parseFloat(document.getElementById('tax').value)||0;
    const discount = parseFloat(document.getElementById('discount').value)||0;
    const total = Math.max(subtotal+(subtotal*tax/100)-discount,0);
    cartBody.innerHTML = html;
    document.getElementById('total').innerText = total.toFixed(2);
}

document.getElementById('tax').addEventListener('change', renderCart);
document.getElementById('discount').addEventListener('change', renderCart);

renderCart();
</script>
@endsection
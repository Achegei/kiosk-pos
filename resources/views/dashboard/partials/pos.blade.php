@php
$store = config('store');
@endphp

@if(auth()->check() && auth()->user()->role === 'staff')

@php
    $openRegister = auth()->user()->openRegister;
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- OPEN REGISTER MODAL --}}
<div id="openRegisterModal" class="fixed inset-0 bg-gray-100 bg-opacity-20 backdrop-blur-sm flex items-center justify-center z-50 {{ $openRegister ? 'hidden' : '' }}">
    <div class="bg-white p-6 rounded-2xl w-full max-w-md shadow-xl">
        <h2 class="text-xl font-bold mb-4">🧾 Open Register</h2>
        <form id="openRegisterForm">
            @csrf
            <div class="mb-4">
                <label class="block font-semibold mb-1">Opening Cash</label>
                <input type="number" name="opening_cash" id="openingCashInput" required class="w-full rounded-lg border p-2">
            </div>
            <button type="submit" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700">
                Open Register
            </button>
        </form>
    </div>
</div>

{{-- POS MAIN LAYOUT --}}
<div class="flex flex-col md:flex-row gap-6 p-6 bg-gray-50 min-h-screen">

    {{-- LEFT PANEL --}}
    <div class="flex-1 bg-white shadow-xl rounded-2xl p-6 flex flex-col">

        <div class="flex justify-between items-center mb-6">
            @if($openRegister)
                <div class="px-3 py-1 rounded font-bold text-white bg-green-500">
                    Register Open: KES {{ number_format($openRegister->opening_cash,2) }}
                </div>

                <button id="triggerCloseRegisterBtn" class="px-4 py-2 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700">
                    Close Register
                </button>
            @endif

            <h3 class="text-2xl font-bold text-gray-800">🛒 Smart POS</h3>
            <div id="networkStatus" class="px-3 py-1 rounded font-bold text-white">Checking...</div>
        </div>

        {{-- Customer Selection --}}
        <div class="mb-4">
            <label class="font-semibold block mb-1">Customer</label>
            <div class="flex gap-2">
                <select id="customer" name="customer_id" class="w-full rounded-lg border-gray-300 shadow-sm">
                    <option value="" selected>Walk-in Customer</option>
                    @foreach($customers ?? [] as $customer)
                        <option value="{{ $customer->id }}">
                            {{ $customer->name }}
                            @if($customer->phone) ({{ $customer->phone }}) @endif
                        </option>
                    @endforeach
                </select>
                <button type="button" id="newCustomerBtn" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 rounded-lg shadow">
                    + New
                </button>
            </div>
        </div>

        {{-- Cart Table --}}
        <div class="overflow-x-auto mb-4 flex-1">
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

        {{-- Totals --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-gray-50 p-4 rounded-xl mb-4">
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
                <div class="text-2xl font-extrabold text-green-600">KES <span id="change">0.00</span></div>
            </div>
        </div>

        {{-- Checkout --}}
        <form id="checkoutForm" action="{{ route('transactions.pos_checkout') }}" method="POST" class="flex gap-4 items-center">
            @csrf
            <input type="hidden" name="customer_id" id="customer_id">
            <input type="hidden" name="products" id="products">
            <input type="hidden" name="register_session_id" value="{{ $openRegister->id ?? '' }}">

            <select name="payment_method" id="payment_method" class="rounded-lg border-gray-300 p-3">
                <option value="Cash">Cash</option>
                <option value="Mpesa">Mpesa</option>
                <option value="Credit">Credit</option>
            </select>

            <button type="submit" class="px-10 py-4 bg-indigo-600 text-white rounded-xl text-lg font-bold hover:bg-indigo-700">
                💳 CHECKOUT
            </button>
        </form>
    </div>

    {{-- RIGHT PANEL --}}
    <div class="w-full md:w-1/3 flex flex-col gap-4">

        {{-- Product Search --}}
        <div class="bg-white shadow-xl rounded-2xl p-4">
            <input id="productSearch" placeholder="Scan barcode or search product..." class="w-full p-4 text-lg rounded-xl border-2 border-indigo-200 focus:border-indigo-500">
            <button id="addProductBtn" class="w-full mt-2 px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 font-semibold">Add Product</button>
            <ul id="suggestions" class="hidden max-h-72 overflow-auto mt-2 bg-white rounded-xl shadow-xl border"></ul>
        </div>

        {{-- Quick Calculator --}}
        <div class="bg-white shadow-xl rounded-2xl p-4">
            <div class="text-gray-600 font-semibold mb-2">Calculator</div>
            <input type="text" id="quickCalcInput" placeholder="0" class="w-full p-3 rounded-xl border text-right text-lg font-mono mb-2">
            <div class="grid grid-cols-4 gap-2">
                @foreach(['7','8','9','C','4','5','6','/','1','2','3','*','0','.','=','-','+'] as $btn)
                    <button class="calcBtn p-2 bg-gray-200 rounded @if(in_array($btn,['C','/','*','-','=','+'])) bg-indigo-600 text-white @endif">{{ $btn }}</button>
                @endforeach
            </div>
        </div>

        {{-- Offline Banner --}}
        <div id="offlineBanner" class="hidden p-3 bg-yellow-100 border-l-4 border-yellow-500 rounded font-semibold">
            ⚠ 0 sales pending sync
        </div>
    </div>
</div>

{{-- CLOSE REGISTER MODAL --}}
@if($openRegister && $openRegister->status === 'open')
<div id="closeRegisterModal" class="hidden fixed inset-0 bg-gray-100 bg-opacity-20 backdrop-blur-sm flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-2xl w-full max-w-md shadow-xl">
        <h2 class="text-xl font-bold mb-4">🧾 Close Register</h2>
        <form id="closeRegisterForm" method="POST" action="{{ route('register.close') }}">
            @csrf

            <div class="mb-2">Opening Cash: KES <span id="openingCash">{{ number_format($openRegister->opening_cash, 2) }}</span></div>
            <div class="mb-2">Cash Sales: KES <span id="cashTotal">0.00</span></div>
            <div class="mb-2">Mpesa Sales: KES <span id="mpesaTotal">0.00</span></div>
            <div class="mb-2">Credit Sales: KES <span id="creditTotal">0.00</span></div>

            <div id="creditCustomersContainer" class="mb-2 hidden">
                <div class="font-semibold">Credit Customers:</div>
                <ul id="creditCustomersList" class="mb-2 list-disc list-inside text-gray-700"></ul>
            </div>

            <div class="mb-2 font-bold">Expected Cash: KES <span id="expectedCash">0.00</span></div>

            <div class="mb-4">
                <label class="block font-semibold mb-1">Closing Cash</label>
                <input type="number" name="closing_cash" required class="w-full rounded-lg border p-2">
            </div>

            <button type="submit" class="w-full py-3 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700">
                Close Register
            </button>
        </form>
    </div>
</div>
@endif

{{-- JS --}}
<script>
document.addEventListener('DOMContentLoaded', ()=>{

    const formOpen = document.getElementById('openRegisterForm');
    formOpen?.addEventListener('submit', async e=>{
        e.preventDefault();
        const formData = new FormData(formOpen);
        try{
            const res = await fetch("{{ route('register.open') }}", {
                method:"POST",
                headers:{
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept':'application/json'
                },
                body: formData
            });
            const data = await res.json();
            if(res.ok && data.success){
                document.getElementById('openRegisterModal').classList.add('hidden');
                location.reload();
            } else alert(data.message || "Failed to open register");
        }catch(err){
            console.error(err);
            alert("Something went wrong, check console");
        }
    });

    document.getElementById('checkoutForm')?.addEventListener('submit', function(e){
        @if(!$openRegister)
            e.preventDefault();
            document.getElementById('openRegisterModal').classList.remove('hidden');
            document.getElementById('openingCashInput').focus();
        @endif
    });

    window.currentUserName = @json(auth()->user()->name ?? 'Staff');
});

// Close Register Modal Logic
document.getElementById('triggerCloseRegisterBtn')?.addEventListener('click', ()=>{
    document.getElementById('closeRegisterModal').classList.remove('hidden');
    updateRegisterModal();
});

window.getCurrentRegisterTotals = function(){
    let cash=0, mpesa=0, credit=0;
    let creditCustomers=[];
    const offline = JSON.parse(localStorage.getItem('offline_sales_queue') || '[]');
    offline.forEach(sale=>{
        if(sale.payment_method==='Cash') cash+=parseFloat(sale.subtotal||0);
        if(sale.payment_method==='Mpesa') mpesa+=parseFloat(sale.subtotal||0);
        if(sale.payment_method==='Credit'){
            credit+=parseFloat(sale.subtotal||0);
            if(sale.customer_id) creditCustomers.push({customerName:sale.customer_name||'Unknown', amount:sale.subtotal});
        }
    });
    return {cash, mpesa, credit, creditCustomers, openingCash: {{ $openRegister->opening_cash ?? 0 }} };
};

function updateRegisterModal(){
    const totals = window.getCurrentRegisterTotals();
    document.getElementById('cashTotal').textContent = totals.cash.toFixed(2);
    document.getElementById('mpesaTotal').textContent = totals.mpesa.toFixed(2);
    document.getElementById('creditTotal').textContent = totals.credit.toFixed(2);
    document.getElementById('expectedCash').textContent = (totals.openingCash + totals.cash).toFixed(2);

    const container=document.getElementById('creditCustomersContainer');
    const list=document.getElementById('creditCustomersList');
    list.innerHTML='';
    if(totals.creditCustomers.length){
        container.classList.remove('hidden');
        totals.creditCustomers.forEach(tx=>{
            const li=document.createElement('li');
            li.textContent=`${tx.customerName}: KES ${parseFloat(tx.amount).toFixed(2)}`;
            list.appendChild(li);
        });
    }else container.classList.add('hidden');
}

window.addEventListener('transactionCompleted', updateRegisterModal);

document.getElementById('closeRegisterForm')?.addEventListener('submit', async function(e){
    e.preventDefault();
    const form=this;
    const formData=new FormData(form);
    try{
        const res=await fetch(form.action,{
            method:'POST',
            headers:{
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept':'application/json'
            },
            body: formData
        });
        const data=await res.json();
        if(res.ok && data.success){
        localStorage.removeItem('offline_sales_queue');
        window.location.href='/login';
        }
        else alert(data.message || 'Failed to close register');
    }catch(err){
        console.error(err);
        alert('Error closing register, check console');
    }
});
</script>

@push('scripts')
<script>
@if(isset($offlineCartItems) && $offlineCartItems->count())
    @foreach($offlineCartItems as $item)
        window.cart.push({
            id: {{ $item['product_id'] }},
            name: "{{ $item['name'] }}",
            price: {{ $item['price'] }},
            quantity: {{ $item['quantity'] }},
            stock: {{ $item['stock'] ?? 1000 }}
        });
    @endforeach
    renderCart();
@endif

const productSearch=document.getElementById('productSearch');
function keepFocus(){ if(productSearch){ productSearch.focus(); productSearch.select(); } }
document.getElementById('addProductBtn')?.addEventListener('click', ()=> setTimeout(keepFocus,50));
productSearch?.addEventListener('keydown', (e)=> { if(e.key==='Enter'){ setTimeout(keepFocus,50); } });
</script>

@vite('resources/js/pos.js')
@endpush

@endif

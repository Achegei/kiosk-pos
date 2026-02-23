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
    <div class="bg-white p-6 rounded-2xl w-full max-w-md shadow-xl transition-all duration-300">
        <h2 class="text-xl font-bold mb-4">🧾 Open Register</h2>

        {{-- Open Register Form --}}
        <form id="openRegisterForm">
            @csrf
            <div class="mb-4">
                <label class="block font-semibold mb-1">Opening Cash</label>
                <input type="number" name="opening_cash" id="openingCashInput" required class="w-full rounded-lg border p-2">
            </div>
            <button type="submit" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 mb-2">
                Open Register
            </button>
        </form>

        {{-- Log Out Button --}}
        <a href="#" id="logoutBtn" class="w-full block text-center py-3 border border-gray-300 rounded-xl font-semibold hover:bg-gray-100 text-red-600">
            Log Out
        </a>

        {{-- Hidden logout form --}}
        <form id="logoutForm" method="POST" action="{{ route('logout') }}">
            @csrf
        </form>

        <script>
        document.getElementById('logoutBtn')?.addEventListener('click', function(e){
            e.preventDefault();
            document.getElementById('logoutForm').submit();
        });
        </script>
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
                <button id="triggerCashMovementBtn"
                class="px-4 py-2 bg-yellow-500 text-white rounded-xl font-bold hover:bg-yellow-600">
                💰 Till Actions
                </button>
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
@php
    $cashSales = $openRegister->transactions()->where('payment_method','Cash')->sum('total_amount');
    $mpesaSales = $openRegister->transactions()->where('payment_method','Mpesa')->sum('total_amount');
    $creditSales = $openRegister->transactions()->where('payment_method','Credit')->sum('total_amount');

    $movements = \App\Models\CashMovement::where('register_session_id',$openRegister->id)
        ->where('tenant_id', auth()->user()->tenant_id)
        ->get();

    $drops       = $movements->where('type','drop')->sum('amount');
    $expenses    = $movements->where('type','expense')->sum('amount');
    $payouts     = $movements->where('type','payout')->sum('amount');
    $deposits    = $movements->where('type','deposit')->sum('amount');
    $adjustments = $movements->where('type','adjustment')->sum('amount');

    $expectedCash = $openRegister->opening_cash + $cashSales - $drops - $expenses - $payouts + $deposits + $adjustments;
@endphp

<div id="closeRegisterModal" class="hidden fixed inset-0 bg-gray-100 bg-opacity-20 backdrop-blur-sm flex items-center justify-center z-50 transition-all duration-300">
    <div class="bg-white p-6 rounded-2xl w-full max-w-md shadow-xl overflow-auto" style="max-height: 90vh;">
        <h2 class="text-xl font-bold mb-4 text-center">🧾 Close Register</h2>

        <div class="mb-2 font-semibold">Cashier: {{ auth()->user()->name }}</div>

        <form id="closeRegisterForm" method="POST" action="{{ route('register.close') }}">
            @csrf

            {{-- Opening Cash --}}
            <div class="mb-2">Opening Cash: KES <span id="openingCash">{{ number_format($openRegister->opening_cash,2) }}</span></div>

            {{-- Sales --}}
            <div class="mb-2">Cash Sales: KES <span id="cashTotal">{{ number_format($cashSales,2) }}</span></div>
            <div class="mb-2">Mpesa Sales: KES <span id="mpesaTotal">{{ number_format($mpesaSales,2) }}</span></div>
            <div class="mb-2 font-bold">Total Cash + Mpesa: KES <span id="totalCashMpesa">{{ number_format($cashSales + $mpesaSales,2) }}</span></div>
            <div class="mb-2 font-bold">Grand Total (Opening + Cash + Mpesa): KES <span id="grandTotalCash">{{ number_format($openRegister->opening_cash + $cashSales + $mpesaSales,2) }}</span></div>
            <div class="mb-2">Credit Sales: KES <span id="creditTotal">{{ number_format($creditSales,2) }}</span></div>

            {{-- Expected Cash --}}
            <div class="mb-2 font-bold">Expected Cash: KES <span id="expectedCash">{{ number_format($expectedCash,2) }}</span></div>

            {{-- Cash Movements --}}
            <div class="mt-4 border-t pt-3">
                <div class="font-bold mb-1">Till Movements</div>
                <div class="text-sm">Cash Drops: KES <span id="dropTotal">{{ number_format($drops,2) }}</span></div>
                <div class="text-sm">Expenses: KES <span id="expenseTotal">{{ number_format($expenses,2) }}</span></div>
                <div class="text-sm">Payouts: KES <span id="payoutTotal">{{ number_format($payouts,2) }}</span></div>
                <div class="text-sm">Deposits: KES <span id="depositTotal">{{ number_format($deposits,2) }}</span></div>
                <div class="text-sm">Adjustments: KES <span id="adjustTotal">{{ number_format($adjustments,2) }}</span></div>
            </div>
            <div class="mt-4 flex gap-2">
                <button type="button" id="refreshRegisterBtn" class="flex-1 py-2 bg-blue-500 text-white rounded-xl font-bold hover:bg-blue-600">
                    🔄 Refresh Totals
                </button>
            </div>
            {{-- Closing Cash Input --}}
            <div class="mb-4">
                <label class="block font-semibold mb-1">Closing Cash</label>
                <input type="number" id="closing_cash" name="closing_cash" required class="w-full rounded-lg border p-2" step="0.01">
            </div>

            <button type="submit" class="w-full py-3 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 mb-2">
                Close Register
            </button>
            <button type="button" class="w-full py-3 border border-gray-300 rounded-xl font-semibold hover:bg-gray-100" id="cancelCloseRegister">
                Cancel / Dashboard
            </button>
        </form>
    </div>
</div>

{{-- CASH MOVEMENT MODAL --}}
<div id="cashMovementModal" class="hidden fixed inset-0 bg-gray-100 bg-opacity-20 backdrop-blur-sm flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-2xl w-full max-w-md shadow-xl">
        <h2 class="text-xl font-bold mb-4">💰 Add Till Movement</h2>

        <form id="cashMovementForm">
            @csrf
            {{-- Attach current open register --}}
            <input type="hidden" name="register_session_id" value="{{ $openRegister->id ?? '' }}">

            <div class="mb-4">
                <label class="block font-semibold mb-1">Type</label>
                <select name="type" required class="w-full rounded-lg border p-2">
                    <option value="">Select Movement Type</option>
                    <option value="drop">Cash Drop</option>
                    <option value="expense">Expense</option>
                    <option value="payout">Payout</option>
                    <option value="deposit">Deposit</option>
                    <option value="adjustment">Adjustment</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block font-semibold mb-1">Amount</label>
                <input type="number" name="amount" step="0.01" required class="w-full rounded-lg border p-2">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700">Save</button>
                <button type="button" id="cancelCashMovement" class="flex-1 py-3 border border-gray-300 rounded-xl hover:bg-gray-100">Cancel</button>
            </div>
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
                body: formData,
                credentials: 'same-origin'
            });
            const data = await res.json();
            if(res.ok && data.success){
                window.location.href = data.redirect;
            } else alert(data.message || "Failed to open register");
        }catch(err){
            console.error(err);
            alert("Something went wrong, check console");
        }
    });

    document.getElementById('checkoutForm')?.addEventListener('submit', function(e){
        if(!{{ $openRegister ? 'true' : 'false' }}){
            e.preventDefault();
            const openModal = document.getElementById('openRegisterModal');
            openModal.classList.remove('hidden');
            openModal.classList.remove('opacity-0');
            document.getElementById('openingCashInput').focus();
        }
    });

    window.currentUserName = @json(auth()->user()->name ?? 'Staff');

    // Cancel Buttons with fade + redirect
    document.getElementById('cancelOpenRegister')?.addEventListener('click', ()=>{
        const modal = document.getElementById('openRegisterModal');
        modal.classList.add('opacity-0');
        setTimeout(()=>{
            modal.classList.add('hidden');
            window.location.href = '{{ route("dashboard") }}';
        }, 300);
    });

    document.getElementById('cancelCloseRegister')?.addEventListener('click', ()=>{
        const modal = document.getElementById('closeRegisterModal');
        modal.classList.add('opacity-0');
        setTimeout(()=>{
            modal.classList.add('hidden');
            window.location.href = '{{ route("dashboard") }}';
        }, 300);
    });
});

  // ---------------- CANCEL CASH MOVEMENT ----------------
    document.getElementById('cancelCashMovement')?.addEventListener('click', () => {
        document.getElementById('cashMovementModal').classList.add('hidden');
    });

    // ---------------- OPEN CASH MOVEMENT MODAL ----------------
    document.getElementById('triggerCashMovementBtn')?.addEventListener('click', () => {
        document.getElementById('cashMovementModal').classList.remove('hidden');
    });

    // ---------------- REFRESH REGISTER TOTALS ----------------
    const refreshBtn = document.getElementById('refreshRegisterBtn');
    if(refreshBtn){
        refreshBtn.addEventListener('click', async () => {
            console.log('Refresh clicked');
            await refreshRegisterTotals();
        });
    } else {
        console.warn('Refresh button NOT FOUND');
    }

    // ---------------- CLOSE REGISTER MODAL ----------------
    document.getElementById('triggerCloseRegisterBtn')?.addEventListener('click', async () => {

        const modal = document.getElementById('closeRegisterModal');
        modal.classList.remove('hidden');

        const registerId = "{{ $openRegister->id ?? '' }}";
        if (!registerId) return;

        const res = await fetch(`/register/${registerId}/totals`);
        const totals = await res.json();

        updateRegisterModalFromServer(totals);
    });


    // ---------------- REGISTER TOTALS ----------------
    window.getCurrentRegisterTotals = function() {
        let cash = 0, mpesa = 0, credit = 0;
        let creditCustomers = [];

        // Offline sales queue
        const offline = JSON.parse(localStorage.getItem('offline_sales_queue') || '[]');
        offline.forEach(sale => {
            if (sale.payment_method === 'Cash') cash += parseFloat(sale.subtotal || 0);
            if (sale.payment_method === 'Mpesa') mpesa += parseFloat(sale.subtotal || 0);
            if (sale.payment_method === 'Credit') {
                credit += parseFloat(sale.subtotal || 0);
                if (sale.customer_id) creditCustomers.push({
                    customerName: sale.customer_name || 'Unknown',
                    amount: sale.subtotal
                });
            }
        });

        // DB totals from Blade
        const dbTotals = {
            openingCash: parseFloat("{{ $openRegister->opening_cash ?? 0 }}"),
            cash: cash + parseFloat("{{ $cashSales ?? 0 }}"),
            mpesa: mpesa + parseFloat("{{ $mpesaSales ?? 0 }}"),
            credit: credit + parseFloat("{{ $creditSales ?? 0 }}"),
            drops: parseFloat("{{ $drops ?? 0 }}"),
            expenses: parseFloat("{{ $expenses ?? 0 }}"),
            payouts: parseFloat("{{ $payouts ?? 0 }}"),
            deposits: parseFloat("{{ $deposits ?? 0 }}"),
            adjustments: parseFloat("{{ $adjustments ?? 0 }}")
        };

        return {
            ...dbTotals,
            creditCustomers,
            expectedCash: dbTotals.openingCash + dbTotals.cash - dbTotals.drops - dbTotals.expenses - dbTotals.payouts + dbTotals.deposits + dbTotals.adjustments,
            expectedMpesa: dbTotals.mpesa,
            totalCashMpesa: dbTotals.cash + dbTotals.mpesa
        };
    };

    // ---------------- UPDATE REGISTER MODAL ----------------
    function updateRegisterModalFromServer(totals) {
    // Offline cash movements
    let offlineMovements = JSON.parse(localStorage.getItem('offline_cash_movements') || '[]');
    let drops = 0, expenses = 0, payouts = 0, deposits = 0, adjustments = 0;

    offlineMovements.forEach(m => {
        const amt = parseFloat(m.amount || 0);
        if (m.type === 'drop') drops += amt;
        if (m.type === 'expense') expenses += amt;
        if (m.type === 'payout') payouts += amt;
        if (m.type === 'deposit') deposits += amt;
        if (m.type === 'adjustment') adjustments += amt;
    });

    // Combine DB totals + offline movements
    const combined = {
        openingCash: parseFloat(totals.openingCash),
        cash: parseFloat(totals.cash),
        mpesa: parseFloat(totals.mpesa),
        credit: parseFloat(totals.credit),
        drops: parseFloat(totals.drops) + drops,
        expenses: parseFloat(totals.expenses) + expenses,
        payouts: parseFloat(totals.payouts) + payouts,
        deposits: parseFloat(totals.deposits) + deposits,
        adjustments: parseFloat(totals.adjustments) + adjustments,
        creditCustomers: totals.creditCustomers || [],
    };

    combined.expectedCash = combined.openingCash + combined.cash - combined.drops - combined.expenses - combined.payouts + combined.deposits + combined.adjustments;
    combined.expectedMpesa = combined.mpesa;
    combined.totalCashMpesa = combined.cash + combined.mpesa;

    // Update modal DOM
    document.getElementById('cashTotal').textContent = combined.cash.toFixed(2);
    document.getElementById('mpesaTotal').textContent = combined.mpesa.toFixed(2);
    document.getElementById('creditTotal').textContent = combined.credit.toFixed(2);
    document.getElementById('dropTotal').textContent = combined.drops.toFixed(2);
    document.getElementById('expenseTotal').textContent = combined.expenses.toFixed(2);
    document.getElementById('payoutTotal').textContent = combined.payouts.toFixed(2);
    document.getElementById('depositTotal').textContent = combined.deposits.toFixed(2);
    document.getElementById('adjustTotal').textContent = combined.adjustments.toFixed(2);
    document.getElementById('expectedCash').textContent = combined.expectedCash.toFixed(2);
    document.getElementById('totalCashMpesa').textContent = combined.totalCashMpesa.toFixed(2);
    document.getElementById('grandTotalCash').textContent = (combined.openingCash + combined.totalCashMpesa).toFixed(2);

    // Update credit customer list if exists
    const container = document.getElementById('creditCustomersContainer');
    const list = document.getElementById('creditCustomersList');
    if (list) list.innerHTML = '';
    if (combined.creditCustomers.length && container) {
        container.classList.remove('hidden');
        combined.creditCustomers.forEach(tx => {
            const li = document.createElement('li');
            li.textContent = `${tx.customerName}: KES ${parseFloat(tx.amount).toFixed(2)}`;
            list.appendChild(li);
        });
    } else if (container) container.classList.add('hidden');
}

    // ---------------- CLOSE REGISTER FORM ----------------
    document.getElementById('closeRegisterForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = this;
        const formData = new FormData(form);
        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            });
            const data = await res.json();
            if (res.ok && data.success) {
                const report = data.report;

                function formatTimestamp(ts) {
                    if (!ts) return '';
                    return new Date(ts).toLocaleString('en-KE', {
                        timeZone: 'Africa/Nairobi',
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                    });
                }

                window.printRegisterClosing({
                    user: report.cashier || window.currentUserName || "Staff",
                    user_id: report.user_id || window.currentUserId || '',
                    session_id: report.session_id || '',
                    opened: formatTimestamp(report.opened_at),
                    closed: formatTimestamp(report.closed_at),
                    opening: parseFloat(report.opening_cash) || 0,
                    cash: parseFloat(report.cash_sales) || 0,
                    mpesa: parseFloat(report.mpesa_sales) || 0,
                    credit: parseFloat(report.credit_sales) || 0,
                    expected: parseFloat(report.expected_cash) || 0,
                    actual: parseFloat(report.counted_cash) || 0
                });

                localStorage.removeItem('offline_sales_queue');
                window.location.href = '/login';
            } else {
                alert(data.message || 'Failed to close register');
            }
        } catch (err) {
            console.error(err);
            alert('Error closing register, check console');
        }
    });

    // ---------------- SAVE CASH MOVEMENT ----------------
   document.getElementById('cashMovementForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    try {
        // 1️⃣ Save cash movement
        const res = await fetch("{{ route('cash-movements.store') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            // 2️⃣ Close modal
            document.getElementById('cashMovementModal').classList.add('hidden');

            // 3️⃣ Fetch updated totals from server
            await refreshRegisterTotals();
            // 5️⃣ Optional: show success alert
            alert("Saved and totals updated!");
            this.reset();
        } else {
            alert(data.message || "Failed to save movement");
        }
    } catch (err) {
        console.error(err);
        alert("Error saving movement, check console");
    }
});

//Refresh totals button function
    async function refreshRegisterTotals(){

        const registerId = "{{ $openRegister->id ?? '' }}";
        if(!registerId) return;

        try{
            const res = await fetch(`/register/${registerId}/totals`);
            const totals = await res.json();

            updateRegisterModalFromServer(totals);

        }catch(err){
            console.error('Refresh failed', err);
        }
    }
    
    
    
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

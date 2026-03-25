<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title','POS Dashboard')</title>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen">

<div class="flex h-screen">

    <!-- MOBILE SIDEBAR OVERLAY -->
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>

    <!-- SIDEBAR -->
    <aside id="sidebar" class="bg-white shadow-xl border-r flex flex-col w-64
        fixed inset-y-0 left-0 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-50">

        <!-- LOGO -->
        <div class="p-6 text-center border-b flex-shrink-0">
            <a href="{{ auth()->user()->isSuperAdmin() ? route('superadmin.dashboard') : route('dashboard') }}"
               class="text-xl font-extrabold tracking-tight
                      bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600
                      bg-clip-text text-transparent">
                POS Dashboard
            </a>
        </div>

        <!-- NAV -->
        <nav class="flex-1 py-6 space-y-1 text-sm overflow-y-auto">
            {{-- ================= SUPER ADMIN ================= --}}
            @if(auth()->user()->isSuperAdmin())
                <div class="px-6 mt-2 text-xs font-semibold text-gray-400 uppercase">Platform</div>
                <a href="{{ route('superadmin.dashboard') }}" class="flex px-6 py-3 hover:bg-gray-100">📊 Platform Dashboard</a>
                <a href="{{ route('admin.tenants.index') }}" class="flex px-6 py-3 hover:bg-gray-100">🏢 Tenants</a>
            @else
                {{-- TENANT USERS --}}
                @if(auth()->user()->canAccessPos())
                <a href="{{ route('dashboard') }}" class="flex items-center px-6 py-3 hover:bg-indigo-50 hover:text-indigo-600 transition">🧾 POS Terminal</a>
                @endif
                <div class="px-6 mt-6 text-xs font-semibold text-gray-400 uppercase">Sales</div>
                <a href="{{ route('transactions.index') }}" class="flex px-6 py-3 hover:bg-gray-100">💳 Transactions</a>
                <a href="{{ route('customers.index') }}" class="flex px-6 py-3 hover:bg-gray-100">👥 Customers</a>
                <div class="px-6 mt-6 text-xs font-semibold text-gray-400 uppercase">Catalog</div>
                <a href="{{ route('products.index') }}" class="flex px-6 py-3 hover:bg-gray-100">📦 Products</a>
                <a href="{{ route('inventories.index') }}" class="flex px-6 py-3 hover:bg-gray-100">🏬 Inventory</a>
                <a href="{{ route('quotes.index') }}" class="flex px-6 py-3 hover:bg-gray-100">📋 Quotes</a>
                <a href="{{ route('invoices.index') }}" class="flex px-6 py-3 hover:bg-gray-100">🧾 Invoices</a>
                <div class="px-6 mt-6 text-xs font-semibold text-gray-400 uppercase">Procurement</div>
                <a href="{{ route('purchase_orders.index') }}" class="flex px-6 py-3 hover:bg-gray-100 items-center space-x-2"><span>🛒</span><span>Purchase Orders</span></a>
                <a href="{{ route('admin.suppliers.index') }}" class="flex px-6 py-3 hover:bg-gray-100 items-center space-x-2"><span>🚚</span><span>Suppliers</span></a>
                @if(auth()->user()->tenant)
                <a href="{{ route('admin.tenants.export', auth()->user()->tenant->id) }}" class="flex px-6 py-3 hover:bg-gray-100">📥 Export Data</a>
                @endif
                @if(auth()->user()->isAdmin())
                <div class="px-6 mt-6 text-xs font-semibold text-gray-400 uppercase">Administration</div>
                <a href="{{ route('tenants.settings.notes') }}" class="flex px-6 py-3 hover:bg-gray-100">📝 Default Quote Notes</a>
                <a href="{{ route('staff.index') }}" class="flex px-6 py-3 hover:bg-gray-100">🛡 Staff</a>
                @endif
            @endif
        </nav>

        <!-- FOOTER -->
        <div class="p-4 border-t bg-white flex-shrink-0">

            <div class="text-xs text-gray-500 mb-3">
                Logged in as<br>
                <span class="font-semibold text-gray-700 break-words">
                    {{ auth()->user()->name }}
                </span>
            </div>

            <!-- Reset Password -->
            <a href="{{ route('admin.reset-password') }}"
            class="block w-full text-center bg-blue-500 text-white py-2 rounded mb-2 text-sm hover:bg-blue-600 transition">
                Reset Password
            </a>

            <!-- Logout -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full bg-red-500 text-white py-2 rounded text-sm hover:bg-red-600 transition">
                    Logout
                </button>
            </form>

        </div>

    </aside>

    <!-- ================= RIGHT SIDE ================= -->
    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen">

        <!-- TOPBAR -->
        <header class="bg-white shadow px-6 py-4 flex justify-between items-center sticky top-0 z-30">
            <div class="flex items-center space-x-4">
                <button id="mobile-menu-button" class="lg:hidden text-2xl">☰</button>
                <h1 class="text-lg font-semibold text-gray-700">@yield('page-title','Dashboard')</h1>
            </div>
            <div class="flex items-center space-x-4">
                {{-- Tenant info --}}
                @if(!auth()->user()->isSuperAdmin() && auth()->user()->tenant)
                    @php
                        $tenant = auth()->user()->tenant;
                        $daysLeft = $tenant->expiry_date ? now()->diffInDays($tenant->expiry_date, false) : null;
                    @endphp
                    @if($tenant->subscription_status === 'active' || $tenant->subscription_status === 'trial')
                        <div class="text-sm text-gray-600">
                            Plan expires: <span class="font-semibold">{{ $tenant->expiry_date ? $tenant->expiry_date->format('d M Y') : 'N/A' }}</span>
                        </div>
                        @if($daysLeft !== null && $daysLeft <= 5)
                            <form method="POST" action="{{ route('tenant.paySaaS') }}">@csrf
                                <button class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600 text-sm">Pay Subscription</button>
                            </form>
                        @endif
                    @elseif($tenant->subscription_status === 'suspended')
                        <form method="POST" action="{{ route('tenant.paySaaS') }}">@csrf
                            <button class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-sm">Account Suspended — Pay Now</button>
                        </form>
                    @endif
                @endif
                <div class="text-sm text-gray-500">{{ now()->format('l, d M Y') }}</div>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="flex-1 overflow-y-auto p-6">

            @if(auth()->user()->must_reset_password)
                <div class="max-w-md mx-auto bg-white shadow-2xl rounded-2xl p-8">
                    <h2 class="text-2xl font-bold mb-4 text-gray-700">Reset Your Password</h2>
                    <p class="text-sm text-gray-500 mb-6">
                        For security reasons, you must reset your password before using the dashboard.
                    </p>

                    <form method="POST" action="{{ route('admin.reset-password') }}">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">New Password</label>
                            <input type="password" name="password" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                            <input type="password" name="password_confirmation" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded hover:bg-indigo-700">
                            Reset Password
                        </button>
                    </form>
                </div>
            @else
                {{-- Existing dashboard content --}}
                @if(session('success'))
                    <script>
                        Swal.fire({icon:'success',title:'Success',text:"{{ session('success') }}",timer:2000,showConfirmButton:false})
                    </script>
                @endif
                @yield('content')
            @endif

    </main>

    </div>

</div>

<!-- SIDEBAR TOGGLE SCRIPT -->
<script>
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const buttons = document.querySelectorAll('#mobile-menu-button');

buttons.forEach(btn => {
    btn.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    });
});

overlay.addEventListener('click', () => {
    sidebar.classList.add('-translate-x-full');
    overlay.classList.add('hidden');
});
</script>

</body>
</html>
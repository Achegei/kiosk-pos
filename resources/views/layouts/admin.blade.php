<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title','POS Dashboard')</title>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@vite(['resources/css/app.css','resources/js/app.js'])
</head>

<body class="bg-gray-100 h-screen overflow-hidden">

<div class="flex h-screen">
<!-- ================= STICKY SIDEBAR ================= -->
<aside class="w-64 bg-white shadow-xl border-r flex flex-col
              sticky top-0 max-h-screen">

    <!-- LOGO -->
    <div class="p-6 text-center border-b flex-shrink-0">
        <a href="{{ route('dashboard') }}"
           class="text-xl font-extrabold tracking-tight
                  bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600
                  bg-clip-text text-transparent">
            POS Dashboard
        </a>
    </div>


    <!-- NAV (THIS becomes scrollable) -->
    <nav class="flex-1 py-6 space-y-1 text-sm overflow-y-auto">

        @if(auth()->user()->canAccessPos())
        <a href="{{ route('dashboard') }}"
           class="flex items-center px-6 py-3 hover:bg-indigo-50 hover:text-indigo-600 transition">
            🧾 POS Terminal
        </a>
        @endif

        <div class="px-6 mt-6 text-xs font-semibold text-gray-400 uppercase">
            Sales
        </div>

        <a href="{{ route('transactions.index') }}"
           class="flex px-6 py-3 hover:bg-gray-100">💳 Transactions</a>

        <a href="{{ route('customers.index') }}"
           class="flex px-6 py-3 hover:bg-gray-100">👥 Customers</a>

        <div class="px-6 mt-6 text-xs font-semibold text-gray-400 uppercase">
            Catalog
        </div>

        <a href="{{ route('products.index') }}"
           class="flex px-6 py-3 hover:bg-gray-100">📦 Products</a>

        <a href="{{ route('inventories.index') }}"
           class="flex px-6 py-3 hover:bg-gray-100">🏬 Inventory</a>

        @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
        <div class="px-6 mt-6 text-xs font-semibold text-gray-400 uppercase">
            Administration
        </div>

        <a href="{{ route('staff.index') }}"
           class="flex px-6 py-3 hover:bg-gray-100">🛡 Staff</a>
        @endif

    </nav>

    <!-- FOOTER (NEVER HIDDEN AGAIN) -->
    <div class="p-4 border-t bg-white flex-shrink-0">

        <div class="text-xs text-gray-500 mb-2">
            Logged in as<br>
            <span class="font-semibold text-gray-700">
                {{ auth()->user()->name }}
            </span>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="w-full bg-red-500 text-white py-2 rounded hover:bg-red-600 text-sm">
                Logout
            </button>
        </form>

    </div>

</aside>

<!-- ================= RIGHT SIDE ================= -->
<div class="flex-1 flex flex-col h-screen">


    <!-- 🔥 STICKY TOPBAR -->
    <header class="bg-white shadow px-6 py-4 flex justify-between items-center
                   sticky top-0 z-30">

        <h1 class="text-lg font-semibold text-gray-700">
            @yield('page-title','Dashboard')
        </h1>

        <div class="text-sm text-gray-500">
            {{ now()->format('l, d M Y') }}
        </div>

    </header>


    <!-- 🔥 ONLY THIS SCROLLS -->
    <main class="flex-1 overflow-y-auto p-6">

        @if(session('success'))
        <script>
            Swal.fire({
                icon:'success',
                title:'Success',
                text:"{{ session('success') }}",
                timer:2000,
                showConfirmButton:false
            })
        </script>
        @endif

        @yield('content')

    </main>

</div>

</div>

</body>
</html>

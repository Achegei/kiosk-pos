<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'POS Dashboard')</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite(['resources/css/app.css','resources/js/app.js'])

</head>
    <body class="bg-gray-100 font-sans antialiased">

        <div class="flex h-screen overflow-hidden">
            <!-- Sidebar -->
            <aside class="w-64 bg-white shadow-md flex-shrink-0">
                <a href="{{ route('dashboard') }}" class="inline-block mt-6">
                    <div class="px-4 py-2 text-xl font-bold text-white bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 
                                rounded-full shadow-md hover:scale-105 transform transition duration-300 border-b-2 border-blue-700 text-center">
                        POS Dashboard
                    </div>
                </a>


            <nav class="mt-6">
                <!--<a href="{{ route('dashboard') }}" class="block px-6 py-3 text-gray-700 hover:bg-gray-100">POS</a>-->
                <a href="{{ route('products.index') }}" class="block px-6 py-3 text-gray-700 hover:bg-gray-100">Products</a>
                <a href="{{ route('customers.index') }}" class="block px-6 py-3 text-gray-700 hover:bg-gray-100">Customers</a>
                <a href="{{ route('transactions.index') }}" class="block px-6 py-3 text-gray-700 hover:bg-gray-100">Transactions</a>
                <a href="{{ route('inventories.index') }}" class="block px-6 py-3 text-gray-700 hover:bg-gray-100">Inventory</a>
                 <a href="{{ route('staff.index') }}" class="block px-6 py-3 text-gray-700 hover:bg-gray-100">Staff</a>
            </nav>
        </aside>

        <!-- Main content -->
        <div class="flex-1 flex flex-col overflow-auto">
            <!-- Header -->
            <header class="bg-white shadow p-4 flex justify-between items-center">
                <h1 class="text-xl font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h1>
                <div x-data="{open:false}" class="relative">

    @auth
        <button @click="open=!open"
            class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded">

            <!-- USER INITIAL -->
            <div class="w-8 h-8 flex items-center justify-center bg-blue-600 text-white rounded-full font-bold">
                {{ strtoupper(substr(auth()->user()->name,0,1)) }}
            </div>

            <div class="text-left leading-tight">
                <div class="font-semibold text-sm">{{ auth()->user()->name }}</div>
                <div class="text-xs text-gray-500 capitalize">
                    {{ str_replace('_',' ',auth()->user()->role) }}
                </div>
            </div>
        </button>

        <!-- DROPDOWN -->
        <div x-show="open"
             @click.outside="open=false"
             class="absolute right-0 mt-2 w-48 bg-white rounded shadow-lg border">

            <a href="#" class="block px-4 py-2 hover:bg-gray-100">
                Profile
            </a>

            <hr>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full text-left px-4 py-2 hover:bg-gray-100 text-red-600">
                    Logout
                </button>
            </form>

        </div>
    @endauth


    @guest
        <a href="{{ route('login') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded">
           Login
        </a>
    @endguest

</div>

            </header>

            <!-- Content -->
            <main class="p-6 flex-1 overflow-y-auto">
                @if (session('success'))
                    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
@stack('scripts')
</body>
</html>

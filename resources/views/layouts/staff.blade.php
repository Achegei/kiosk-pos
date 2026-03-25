<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pos.js'])
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">

    <!-- HEADER -->
    <header class="bg-white shadow p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 sm:gap-0 sticky top-0 z-30">
        <h1 class="font-bold text-lg sm:text-xl">POS System</h1>

        <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4 w-full sm:w-auto gap-2 sm:gap-0 mt-2 sm:mt-0">
            <a href="{{ route('logout') }}" 
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();" 
               class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 text-sm text-center sm:text-left">
               Logout
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-4 sm:p-6 md:p-8 overflow-auto">
        @yield('content')
    </main>

    @stack('scripts')

    <script>
    window.STORE = {!! json_encode([
        'name' => auth()->user()->tenant->name,
        'address' => trim(auth()->user()->tenant->building_name . ' ' . auth()->user()->tenant->street_address),
        'phone' => auth()->user()->tenant->phone ?? 'N/A',
        'footer' => auth()->user()->tenant->default_notes ?? 'Thank you for shopping with us!'
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!};
    </script>

</body>
</html>
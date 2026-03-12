<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <header class="bg-white shadow p-4 flex justify-between">
        <h1 class="font-bold">POS System</h1>
        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
    </header>

    <main class="p-4">
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


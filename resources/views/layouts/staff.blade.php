<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>
    @vite('resources/css/app.css', 'resources/js/app.js')
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
</body>
</html>

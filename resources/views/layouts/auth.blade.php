<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title','Login')</title>

<script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="min-h-screen bg-gradient-to-br from-indigo-600 via-blue-600 to-purple-700">

<div class="min-h-screen flex">

    <!-- LEFT SIDE (Branding / Decoration) -->
    <div class="hidden lg:flex w-1/2 text-white flex-col justify-center px-20">

        <h1 class="text-5xl font-bold mb-6">
            MyPOS
        </h1>

        <p class="text-xl opacity-90 mb-8">
            Smart Retail Management System built for modern businesses.
        </p>

        <div class="space-y-4 text-lg opacity-80">
            <div>✔ Fast checkout</div>
            <div>✔ Inventory tracking</div>
            <div>✔ Customer insights</div>
            <div>✔ Daily analytics</div>
        </div>

    </div>

    <!-- RIGHT SIDE (FORM) -->
    <div class="flex w-full lg:w-1/2 justify-center items-center p-8">

        <div class="bg-white shadow-2xl rounded-2xl w-full max-w-md p-10">

            @yield('content')

        </div>

    </div>

</div>

</body>
</html>

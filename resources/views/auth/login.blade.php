@extends('layouts.auth')

@section('content')
<div class="flex justify-center items-center h-screen">
    <div class="bg-white shadow-md p-8 rounded w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center">Login to MyPOS</h2>

        @if($errors->any())
            <div class="bg-red-100 text-red-700 p-2 mb-4 rounded">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block mb-1 font-medium">Email</label>
                <input type="email" name="email" class="w-full border border-gray-300 px-3 py-2 rounded" required>
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-medium">Password</label>
                <input type="password" name="password" class="w-full border border-gray-300 px-3 py-2 rounded" required>
            </div>
            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Login</button>
        </form>
    </div>
</div>
@endsection

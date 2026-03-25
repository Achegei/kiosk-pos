@extends('layouts.admin')

@section('title', 'Reset Password')

@section('content')
<div class="max-w-md mx-auto bg-white p-8 rounded-xl shadow">
    <h2 class="text-2xl font-bold mb-4 text-gray-700">Reset Your Password</h2>
    <p class="text-sm text-gray-500 mb-6">
        For security reasons, you must reset your password before accessing the dashboard.
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
@endsection
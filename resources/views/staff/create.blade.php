<!-- resources/views/staff/create.blade.php -->
@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto mt-10 p-8 bg-white shadow-xl rounded-2xl border border-gray-200">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Create New Staff Member</h1>

    <form action="{{ route('staff.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
            <input type="text" name="name" id="name" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-gray-900">
        </div>

        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
            <input type="email" name="email" id="email" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-gray-900">
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input type="password" name="password" id="password" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-gray-900">
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-gray-900">
        </div>

        <!-- Role -->
        <div>
            <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
            <select name="role" id="role" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-gray-900">
                @foreach($roles as $role)
                    <option value="{{ $role }}">{{ ucfirst(str_replace('_',' ',$role)) }}</option>
                @endforeach
            </select>
        </div>

        <!-- POS Access -->
        <div class="flex items-center space-x-2">
            <input type="checkbox" name="can_pos" id="can_pos" value="1" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
            <label for="can_pos" class="text-sm text-gray-700">Allow POS Access (can sell)</label>
        </div>

        <!-- Submit Button -->
        <div>
            <button type="submit"
                class="w-full py-3 px-6 bg-indigo-600 text-white font-semibold rounded-lg shadow hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                Create Staff
            </button>
        </div>
    </form>
</div>
@endsection

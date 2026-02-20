@extends('layouts.admin')

@section('title', 'Edit Customer')
@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <h1 class="text-2xl font-bold mb-6">Edit Customer</h1>

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="bg-red-100 text-red-800 px-4 py-3 rounded mb-6 shadow-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('customers.update', $customer) }}" method="POST" class="bg-white shadow-md rounded-lg p-6 space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="block text-gray-700 font-medium mb-2">Name</label>
            <input type="text" name="name" id="name" value="{{ old('name', $customer->name) }}" required
                   class="w-full border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label for="phone" class="block text-gray-700 font-medium mb-2">Phone</label>
            <input type="text" name="phone" id="phone" value="{{ old('phone', $customer->phone) }}" required
                   class="w-full border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label for="email" class="block text-gray-700 font-medium mb-2">Email (optional)</label>
            <input type="email" name="email" id="email" value="{{ old('email', $customer->email) }}"
                   class="w-full border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label for="credit" class="block text-gray-700 font-medium mb-2">Credit (KSh)</label>
            <input type="number" step="0.01" name="credit" id="credit" value="{{ old('credit', $customer->credit) }}"
                   class="w-full border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="flex items-center gap-3 mt-4">
            <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 transition">
                Update Customer
            </button>
            <a href="{{ route('customers.index') }}" class="text-gray-600 hover:underline transition">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection

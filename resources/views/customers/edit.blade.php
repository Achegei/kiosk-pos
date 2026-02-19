@extends('layouts.admin')

@section('content')
<div class="container mx-auto py-8">
    <h2 class="text-2xl font-bold mb-6">Edit Customer</h2>

    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('customers.update', $customer) }}" method="POST" class="bg-white p-6 rounded shadow-md">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="name" class="block font-medium mb-1">Name</label>
            <input type="text" name="name" id="name" value="{{ old('name', $customer->name) }}" class="w-full border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>

        <div class="mb-4">
            <label for="phone" class="block font-medium mb-1">Phone</label>
            <input type="text" name="phone" id="phone" value="{{ old('phone', $customer->phone) }}" class="w-full border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>

        <div class="mb-4">
            <label for="email" class="block font-medium mb-1">Email (optional)</label>
            <input type="email" name="email" id="email" value="{{ old('email', $customer->email) }}" class="w-full border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label for="credit" class="block font-medium mb-1">Credit (KSh)</label>
            <input type="number" step="0.01" name="credit" id="credit" value="{{ old('credit', $customer->credit) }}" class="w-full border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Update Customer</button>
        <a href="{{ route('customers.index') }}" class="ml-2 text-gray-600 hover:underline">Cancel</a>
    </form>
</div>
@endsection

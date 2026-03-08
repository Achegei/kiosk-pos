@extends('layouts.admin')

@section('title', 'Add Supplier')

@section('content')
<div class="container mx-auto py-6 max-w-3xl">
    <h1 class="text-2xl font-bold mb-4">Add Supplier</h1>

    <form action="{{ route('admin.suppliers.store') }}" method="POST" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700">Name</label>
            <input type="text" name="name" class="border rounded p-2 w-full" value="{{ old('name') }}" required>
            @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Contact</label>
            <input type="text" name="contact" class="border rounded p-2 w-full" value="{{ old('contact') }}">
            @error('contact') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" class="border rounded p-2 w-full" value="{{ old('email') }}">
            @error('email') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Address</label>
            <input type="text" name="address" class="border rounded p-2 w-full" value="{{ old('address') }}">
            @error('address') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Save Supplier</button>
        </div>
    </form>
</div>
@endsection
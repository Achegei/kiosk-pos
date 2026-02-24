@extends('layouts.admin')

@section('content')
<div class="p-6 bg-gray-50 min-h-screen">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <h1 class="text-3xl font-extrabold text-gray-900">Edit Tenant: {{ $tenant->name }}</h1>

        <div class="flex gap-2">
            <a href="{{ route('admin.tenants.index') }}"
               class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-5 py-2 rounded-lg shadow-md transition">
                Back to Tenants
            </a>
        </div>
    </div>

    {{-- Update Form --}}
    <form action="{{ route('admin.tenants.update', $tenant->id) }}" method="POST" class="bg-white p-6 rounded-lg shadow-md mb-6">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="font-medium block mb-1" for="business_name">Business Name</label>
            <input type="text" name="business_name" id="business_name" value="{{ old('business_name', $tenant->name) }}"
                   class="w-full border-gray-300 rounded-lg shadow-sm p-2" required>
        </div>

        <div class="mb-4">
            <label class="font-medium block mb-1" for="phone">Phone</label>
            <input type="text" name="phone" id="phone" value="{{ old('phone', $tenant->phone) }}"
                   class="w-full border-gray-300 rounded-lg shadow-sm p-2">
        </div>

        <div class="mb-4">
            <label class="font-medium block mb-1" for="status">Subscription Status</label>

            @php
                $statusClasses = [
                    'trial'  => 'bg-yellow-100 text-yellow-800',
                    'active' => 'bg-green-100 text-green-800',
                    'inactive'=> 'bg-gray-100 text-gray-800',
                ];
            @endphp

            <select name="status" id="status" class="w-full border-gray-300 rounded-lg shadow-sm p-2">
                <option value="trial" {{ $tenant->subscription_status === 'trial' ? 'selected' : '' }}>Trial</option>
                <option value="active" {{ $tenant->subscription_status === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $tenant->subscription_status === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <button type="submit"
                class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded-lg shadow-md transition">
            Update Tenant
        </button>
    </form>

    {{-- Delete Tenant --}}
    <form action="{{ route('admin.tenants.destroy', $tenant->id) }}" method="POST"
          onsubmit="return confirm('Are you sure you want to delete this tenant?');"
          class="inline-block">
        @csrf
        @method('DELETE')
        <button type="submit"
                class="bg-red-100 text-red-800 px-4 py-2 rounded-lg font-medium hover:bg-red-200 transition">
            Delete Tenant
        </button>
    </form>

</div>
@endsection
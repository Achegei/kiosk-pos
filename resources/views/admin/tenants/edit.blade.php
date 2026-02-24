@extends('layouts.admin')

@section('content')
<div class="p-6 bg-gray-50 min-h-screen">

    <h1 class="text-3xl font-bold mb-6">Edit Tenant: {{ $tenant->name }}</h1>

    <form action="{{ route('admin.tenants.update', $tenant->id) }}" method="POST" class="space-y-6 bg-white p-6 rounded-lg shadow">
        @csrf
        @method('PUT')

        {{-- Name --}}
        <div>
            <label class="block font-medium mb-1">Business Name</label>
            <input type="text" name="name" value="{{ old('name', $tenant->name) }}"
                   class="w-full border rounded-lg p-2" required>
        </div>

        {{-- Phone --}}
        <div>
            <label class="block font-medium mb-1">Phone</label>
            <input type="text" name="phone" value="{{ old('phone', $tenant->phone) }}"
                   class="w-full border rounded-lg p-2">
        </div>

        {{-- Subscription Status --}}
        <div>
            <label class="block font-medium mb-1">Subscription Status</label>
            <select name="subscription_status" class="w-full border rounded-lg p-2">
                <option value="trial" {{ $tenant->subscription_status === 'trial' ? 'selected' : '' }}>Trial</option>
                <option value="active" {{ $tenant->subscription_status === 'active' ? 'selected' : '' }}>Active</option>
                <option value="expired" {{ $tenant->subscription_status === 'expired' ? 'selected' : '' }}>Expired</option>
            </select>
        </div>

        {{-- Expiry Date --}}
        <div>
            <label class="block font-medium mb-1">Expiry Date</label>
            <input type="date" name="expiry_date" value="{{ old('expiry_date', optional($tenant->expiry_date)->format('Y-m-d')) }}"
                   class="w-full border rounded-lg p-2">
        </div>

        {{-- Submit --}}
        <div>
            <button type="submit"
                class="bg-blue-500 text-white px-5 py-2 rounded-lg hover:bg-blue-600 transition font-medium">
                Update Tenant
            </button>
        </div>
    </form>

</div>
@endsection
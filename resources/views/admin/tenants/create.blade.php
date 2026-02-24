@extends('layouts.super_admin')

@section('title', 'Edit Tenant')

@section('content')
<div class="min-h-screen bg-gray-100">

    <!-- Page Container -->
    <div class="max-w-4xl mx-auto py-10 px-6">

        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-50 to-blue-100 p-6 rounded-lg shadow-md mb-8 border-l-4 border-indigo-600">
            <h2 class="text-2xl font-bold text-gray-800">Edit Business Tenant</h2>
            <p class="text-gray-500 mt-1">
                Update tenant information or manage its subscription status.
            </p>
        </div>

        <!-- Form Card -->
        <div class="bg-white shadow-lg rounded-lg p-8 border">

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg mb-6">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.tenants.update', $tenant->id) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- BUSINESS INFO -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Business Name</label>
                    <input name="business_name"
                           value="{{ old('business_name', $tenant->name) }}"
                           required
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                           placeholder="Example: Smart Shop Supermarket">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Phone</label>
                    <input name="phone"
                           value="{{ old('phone', $tenant->phone) }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                           placeholder="07XXXXXXXX">
                </div>

                <!-- STATUS -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Subscription Status</label>
                    <select name="status"
                            required
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        @php
                            $statuses = ['trial' => 'Trial', 'active' => 'Active', 'expired' => 'Expired'];
                        @endphp
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ old('status', $tenant->subscription_status) === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- BUTTONS -->
                <div class="flex items-center justify-between space-x-4 pt-6">

                    <a href="{{ route('admin.tenants.index') }}"
                       class="px-5 py-3 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 transition font-semibold">
                        Back
                    </a>

                    <div class="flex space-x-2">

                        <button type="submit"
                            class="px-7 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition shadow">
                            Update Tenant
                        </button>

                        <button type="button" onclick="confirmDelete()"
                            class="px-5 py-3 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg transition shadow">
                            Delete
                        </button>

                    </div>

                </div>

            </form>

            <!-- DELETE FORM (hidden) -->
            <form id="deleteTenantForm" method="POST" action="{{ route('admin.tenants.destroy', $tenant->id) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>

        </div>
    </div>
</div>

<script>
    function confirmDelete() {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will permanently delete the tenant!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e3342f',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it!',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteTenantForm').submit();
            }
        });
    }
</script>
@endsection
@extends('layouts.admin')

@section('title', 'Create Tenant')

@section('content')
<div class="min-h-screen bg-gray-100">

    <div class="max-w-4xl mx-auto py-10 px-6">

        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-50 to-blue-100 p-6 rounded-lg shadow-md mb-8 border-l-4 border-indigo-600">
            <h2 class="text-2xl font-bold text-gray-800">Create New Business Tenant</h2>
            <p class="text-gray-500 mt-1">
                Fill in the details to register a new tenant.
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

            <form method="POST" action="{{ route('admin.tenants.store') }}" class="space-y-6" enctype="multipart/form-data">
                @csrf

                <!-- BUSINESS INFO -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Business Name</label>
                    <input name="business_name"
                           value="{{ old('business_name') }}"
                           required
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                           placeholder="Example: Smart Shop Supermarket">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Phone</label>
                    <input name="phone"
                           value="{{ old('phone') }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                           placeholder="07XXXXXXXX">
                </div>

                <!-- ADDRESS -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Street</label>
                    <input name="street"
                           value="{{ old('street') }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                           placeholder="Street name or road">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Building / Suite</label>
                    <input name="building"
                           value="{{ old('building') }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                           placeholder="Building / Suite">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Shop Number</label>
                    <input name="shop_number"
                           value="{{ old('shop_number') }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                           placeholder="Shop number">
                </div>

                <!-- LOGO -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Business Logo</label>
                    <input type="file" name="logo" accept="image/*"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <!-- ADMIN INFO -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Admin Name</label>
                    <input name="admin_name"
                           value="{{ old('admin_name') }}"
                           required
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                           placeholder="Full name of the admin">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Admin Email</label>
                    <input type="email" name="admin_email"
                           value="{{ old('admin_email') }}"
                           required
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                           placeholder="admin@example.com">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Admin Password</label>
                    <input type="password" name="admin_password"
                           required
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                           placeholder="Password (min 6 characters)">
                </div>

                <!-- BUTTONS -->
                <div class="flex items-center justify-between space-x-4 pt-6">

                    <a href="{{ route('admin.tenants.index') }}"
                       class="px-5 py-3 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 transition font-semibold">
                        Back
                    </a>

                    <button type="submit"
                        class="px-7 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition shadow">
                        Create Tenant
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>
@endsection
@extends('layouts.admin')

@section('title', 'Create Tenant')

@section('content')
<div class="min-h-screen bg-gray-100">

    <!-- Page Container -->
    <div class="max-w-4xl mx-auto py-10 px-6">

        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-50 to-blue-100 p-6 rounded-lg shadow-md mb-8 border-l-4 border-indigo-600">
            <h2 class="text-2xl font-bold text-gray-800">Create New Business Tenant</h2>
            <p class="text-gray-500 mt-1">
                Add a new store/business to your SaaS POS platform.  
                A tenant admin account will also be created automatically.
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

            <form method="POST" action="/admin/tenants" class="space-y-6">
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

                <!-- ADMIN ACCOUNT -->
                <div class="border-t pt-6">

                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        Tenant Administrator Account
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Admin Name</label>
                            <input name="admin_name"
                                   value="{{ old('admin_name') }}"
                                   required
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Admin Email</label>
                            <input name="admin_email"
                                   type="email"
                                   value="{{ old('admin_email') }}"
                                   required
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>

                    </div>

                    <div class="mt-6">
                        <label class="block text-gray-700 font-semibold mb-2">Password</label>
                        <input name="admin_password"
                               type="password"
                               required
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>

                </div>

                <!-- BUTTONS -->
                <div class="flex items-center justify-end space-x-4 pt-6">

                    <a href="{{ route('dashboard') }}"
                       class="px-5 py-3 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 transition font-semibold">
                        Cancel
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
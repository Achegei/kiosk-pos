@extends('layouts.super_admin')

@section('title', 'Super Admin Tenants Dashboard')

@section('content')
<div class="p-6 space-y-6">

    <h1 class="text-2xl font-bold text-indigo-700 mb-4">Tenants Overview</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($tenantStats as $stat)
        <div class="bg-white shadow rounded-lg p-4 hover:shadow-md transition">
            <div class="flex justify-between items-center mb-3">
                <h2 class="font-semibold text-lg text-gray-700">{{ $stat['tenant']->name }}</h2>
                <span class="text-sm text-gray-500">{{ ucfirst($stat['tenant']->subscription_status) }}</span>
            </div>

            <p class="text-sm text-gray-500 mb-2">Phone: {{ $stat['tenant']->phone ?? '-' }}</p>
            <p class="text-sm text-gray-500 mb-4">Active Customers: {{ $stat['tenant']->customers_count }}</p>

            <div class="grid grid-cols-2 gap-2 mb-4">
                <div class="bg-green-50 text-green-700 px-2 py-1 rounded text-sm text-center">
                    Daily Sales<br>${{ number_format($stat['dailySales'], 2) }}
                </div>
                <div class="bg-yellow-50 text-yellow-700 px-2 py-1 rounded text-sm text-center">
                    Daily Credit<br>${{ number_format($stat['dailyCreditSales'], 2) }}
                </div>
                <div class="bg-blue-50 text-blue-700 px-2 py-1 rounded text-sm text-center">
                    Total Revenue<br>${{ number_format($stat['totalRevenue'], 2) }}
                </div>
                <div class="bg-red-50 text-red-700 px-2 py-1 rounded text-sm text-center">
                    Low Stock<br>{{ $stat['lowStockCount'] }}
                </div>
                <div class="bg-purple-50 text-purple-700 px-2 py-1 rounded text-sm text-center col-span-2">
                    Active Devices<br>{{ $stat['activeDevices'] }}
                </div>
            </div>

            <div class="flex justify-between mt-2">
                <a href="{{ route('admin.tenants.view', $stat['tenant']->id) }}"
                   class="text-indigo-600 hover:underline text-sm">View</a>
                <a href="{{ route('admin.tenants.users', $stat['tenant']->id) }}"
                   class="text-indigo-600 hover:underline text-sm">Manage Users</a>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-6">
        <a href="{{ route('admin.tenants.create') }}"
           class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
           ➕ Create New Tenant
        </a>
    </div>

</div>
@endsection
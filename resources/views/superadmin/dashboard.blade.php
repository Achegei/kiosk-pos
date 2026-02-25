@extends('layouts.admin')

@section('title','Super Admin Dashboard')

@section('content')
<div class="p-6 space-y-6">

    <h1 class="text-3xl font-bold mb-6 text-gray-800">Super Admin Dashboard</h1>

    {{-- ================= SUMMARY CARDS ================= --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
        <div class="bg-white shadow-md rounded-xl p-5 hover:shadow-xl transition flex flex-col">
            <span class="text-gray-400 uppercase text-xs font-semibold tracking-wide">Total Tenants</span>
            <span class="mt-2 text-2xl sm:text-3xl font-bold text-gray-800 font-mono">
                {{ $tenants->total() }}
            </span>
        </div>

        <div class="bg-white shadow-md rounded-xl p-5 hover:shadow-xl transition flex flex-col">
            <span class="text-gray-400 uppercase text-xs font-semibold tracking-wide">Active Tenants Today</span>
            <span class="mt-2 text-2xl sm:text-3xl font-bold text-indigo-600 font-mono">
                {{ $activeTenantsToday }}
            </span>
        </div>

        <div class="bg-white shadow-md rounded-xl p-5 hover:shadow-xl transition flex flex-col">
            <span class="text-gray-400 uppercase text-xs font-semibold tracking-wide">Revenue This Month</span>
            <span class="mt-2 text-2xl sm:text-3xl font-bold text-green-600 font-mono">
                ${{ number_format($monthlyRevenue, 2) }}
            </span>
        </div>

        <div class="bg-white shadow-md rounded-xl p-5 hover:shadow-xl transition flex flex-col">
            <span class="text-gray-400 uppercase text-xs font-semibold tracking-wide">Total Staff</span>
            <span class="mt-2 text-2xl sm:text-3xl font-bold text-purple-600 font-mono">
                {{ $tenants->sum('staff_count') }}
            </span>
        </div>
    </div>

    {{-- ================= TENANTS TABLE ================= --}}
    <div class="bg-white rounded-xl shadow overflow-x-auto p-4 mt-6">
        <table class="w-full table-auto border-collapse text-sm text-gray-700 font-mono">
            <thead>
                <tr class="bg-gray-50 uppercase text-gray-500 text-xs tracking-wider">
                    <th class="px-4 py-2 text-left">Tenant Name</th>
                    <th class="px-4 py-2 text-left">Email / Contact</th>
                    <th class="px-4 py-2 text-center">Staff</th>
                    <th class="px-4 py-2 text-center">Tx Today</th>
                    <th class="px-4 py-2 text-center">Tx Month</th>
                    <th class="px-4 py-2 text-right">Revenue Today</th>
                    <th class="px-4 py-2 text-right">Revenue Month</th>
                    <th class="px-4 py-2 text-center">Avg Ticket</th>
                    <th class="px-4 py-2 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($tenants as $tenant)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-semibold">{{ $tenant->name }}</td>
                        <td class="px-4 py-2 truncate">{{ $tenant->email ?? '-' }}</td>
                        <td class="px-4 py-2 text-center">{{ $tenant->staff_count }}</td>
                        <td class="px-4 py-2 text-center">{{ $tenant->today_transactions }}</td>
                        <td class="px-4 py-2 text-center">{{ $tenant->month_transactions }}</td>
                        <td class="px-4 py-2 text-right">${{ number_format($tenant->today_revenue,2) }}</td>
                        <td class="px-4 py-2 text-right">${{ number_format($tenant->month_revenue,2) }}</td>
                        <td class="px-4 py-2 text-center">
                            ${{ number_format($tenant->month_transactions ? $tenant->month_revenue / $tenant->month_transactions : 0,2) }}
                        </td>
                        <td class="px-4 py-2 text-center space-x-2">
                            <a href="{{ route('admin.tenants.show', $tenant) }}" class="text-indigo-600 hover:underline">View</a>
                            <a href="{{ route('admin.tenants.export', $tenant) }}" class="text-green-600 hover:underline">CSV</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $tenants->links() }}
        </div>
    </div>

</div>
@endsection
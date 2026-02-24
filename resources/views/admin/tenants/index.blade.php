@extends('layouts.admin')

@section('content')
<div class="p-6 bg-gray-50 min-h-screen">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <h1 class="text-3xl font-extrabold text-gray-900">Tenants</h1>

        <a href="{{ route('admin.tenants.create') }}"
           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded-lg shadow-md transition">
           + Create Tenant
        </a>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-lg shadow-md bg-white">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase tracking-wider">Business</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase tracking-wider">Phone</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase tracking-wider">Created</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($tenants as $tenant)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-gray-900 font-medium">{{ $tenant->name }}</td>
                    <td class="px-6 py-4 text-gray-700">{{ $tenant->phone ?? '-' }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                            {{ $tenant->subscription_status === 'trial' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                            {{ ucfirst($tenant->subscription_status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-700">{{ $tenant->created_at->format('d M Y') }}</td>
                    <td class="px-6 py-4 flex justify-center items-center gap-2">
                        <a href="{{ route('admin.tenants.show', $tenant->id) }}"
                           class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-lg text-sm font-medium hover:bg-blue-200 transition">View</a>

                        <a href="{{ route('admin.tenants.edit', $tenant->id) }}"
                           class="inline-flex items-center px-3 py-1 bg-yellow-100 text-yellow-800 rounded-lg text-sm font-medium hover:bg-yellow-200 transition">Edit</a>

                        <form action="{{ route('admin.tenants.destroy', $tenant->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this tenant?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex items-center px-3 py-1 bg-red-100 text-red-800 rounded-lg text-sm font-medium hover:bg-red-200 transition">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach

                @if($tenants->isEmpty())
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No tenants found.</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
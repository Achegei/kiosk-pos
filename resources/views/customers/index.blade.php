@extends('layouts.admin')

@section('content')
<div class="container mx-auto py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Customers</h2>
        <a href="{{ route('customers.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add Customer</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto bg-white shadow rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Name</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Phone</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Email</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Credit (KSh)</th>
                    <th class="px-6 py-3 text-center text-sm font-medium text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($customers as $customer)
                    <tr>
                        <td class="px-6 py-4">{{ $customer->name }}</td>
                        <td class="px-6 py-4">{{ $customer->phone }}</td>
                        <td class="px-6 py-4">{{ $customer->email ?? '-' }}</td>
                        <td class="px-6 py-4">{{ number_format($customer->credit, 2) }}</td>
                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('customers.edit', $customer) }}" class="text-blue-600 hover:underline mr-2">Edit</a>
                            <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No customers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $customers->links() }}
    </div>
</div>
@endsection

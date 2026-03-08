@extends('layouts.admin')

@section('title', 'Purchase Orders')

@section('content')
<div class="container mx-auto py-6">

    {{-- Tenant Branding --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold">
            {{ auth()->user()->tenant->name ?? 'My Business' }} - Purchase Orders
        </h1>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Actions --}}
    <div class="mb-4 flex justify-between items-center">
        <a href="{{ route('purchase_orders.create') }}"
           class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            + New Purchase Order
        </a>

        <form action="{{ route('purchase_orders.index') }}" method="GET" class="flex space-x-2">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search PO#"
                class="border rounded px-2 py-1"
            >
            <button type="submit"
                class="bg-gray-500 text-white px-3 py-1 rounded hover:bg-gray-600">
                Search
            </button>
        </form>
    </div>

    {{-- Purchase Orders Table --}}
    <div class="overflow-x-auto bg-white shadow rounded">
        <table class="w-full table-auto border-collapse border border-gray-200">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-4 py-2 text-left">PO #</th>
                    <th class="border px-4 py-2 text-left">Supplier</th>
                    <th class="border px-4 py-2 text-left">Status</th>
                    <th class="border px-4 py-2 text-left">Date</th>
                    <th class="border px-4 py-2 text-center">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($purchaseOrders as $po)
                    <tr class="hover:bg-gray-50">

                        <td class="border px-4 py-2 font-semibold">
                            #{{ $po->id }}
                        </td>

                        <td class="border px-4 py-2">
                            {{ $po->supplier->name ?? '-' }}
                        </td>

                        <td class="border px-4 py-2">
                            @if($po->status == 'pending')
                                <span class="bg-yellow-200 text-yellow-800 px-2 py-1 rounded text-sm">
                                    Pending
                                </span>

                            @elseif($po->status == 'received')
                                <span class="bg-green-200 text-green-800 px-2 py-1 rounded text-sm">
                                    Received
                                </span>

                            @elseif($po->status == 'cancelled')
                                <span class="bg-red-200 text-red-800 px-2 py-1 rounded text-sm">
                                    Cancelled
                                </span>
                            @endif
                        </td>

                        <td class="border px-4 py-2">
                            {{ $po->created_at->format('d M Y') }}
                        </td>

                        <td class="border px-4 py-2 text-center space-x-1">

                            {{-- View --}}
                            <a href="{{ route('purchase_orders.show', $po) }}"
                               class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600">
                               View
                            </a>

                            {{-- Edit only if not cancelled --}}
                            @if($po->status != 'cancelled')
                                <a href="{{ route('purchase_orders.edit', $po) }}"
                                   class="bg-yellow-500 text-white px-2 py-1 rounded hover:bg-yellow-600">
                                   Edit
                                </a>
                            @endif

                            {{-- Delete --}}
                            <form action="{{ route('purchase_orders.destroy', $po) }}"
                                  method="POST"
                                  class="inline-block"
                                  onsubmit="return confirm('Delete this purchase order and reverse stock?')">

                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                    class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600">
                                    Delete
                                </button>

                            </form>

                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="5"
                            class="border px-4 py-6 text-center text-gray-500">
                            No Purchase Orders found.
                        </td>
                    </tr>
                @endforelse
            </tbody>

        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $purchaseOrders->appends(request()->query())->links() }}
    </div>

</div>
@endsection
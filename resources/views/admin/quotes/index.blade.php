@extends('layouts.admin')
@section('title','Quotes')

@section('content')
<div class="p-6 max-w-7xl mx-auto">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Quotes</h1>
        <a href="{{ route('quotes.create') }}" 
           class="mt-3 md:mt-0 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded shadow transition duration-150 ease-in-out">
            + Create New Quote
        </a>
    </div>

    {{-- SUCCESS MESSAGE --}}
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- QUOTES TABLE --}}
    <div class="overflow-x-auto shadow rounded-lg bg-white">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 text-gray-700 uppercase text-xs font-semibold">
                <tr>
                    <th class="px-6 py-3 text-left">#</th>
                    <th class="px-6 py-3 text-left">Client</th>
                    <th class="px-6 py-3 text-left">Tenant</th>
                    <th class="px-6 py-3 text-right">Total</th>
                    <th class="px-6 py-3 text-center">Status</th>
                    <th class="px-6 py-3 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($quotes as $quote)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $quote->quote_number ?? $quote->id }}</td>
                        <td class="px-6 py-4 text-sm text-gray-800">{{ $quote->client_name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-800">{{ $quote->tenant->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-sm font-semibold text-right">${{ number_format($quote->total_amount,2) }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                {{ $quote->status == 'Draft' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                                {{ $quote->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center flex flex-wrap justify-center gap-2">
                            <a href="{{ route('quotes.show', $quote->id) }}" 
                               class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded text-sm transition duration-150 ease-in-out">
                               View
                            </a>
                            <a href="{{ route('quotes.edit', $quote->id) }}" 
                               class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm transition duration-150 ease-in-out">
                               Edit
                            </a>

                            @if($quote->status == 'Draft')
                                <form action="{{ route('quotes.convert', $quote->id) }}" method="POST" 
                                      onsubmit="return confirm('Convert this quote to invoice?');" class="inline-block">
                                    @csrf
                                    <button type="submit" 
                                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded text-sm transition duration-150 ease-in-out">
                                        Convert
                                    </button>
                                </form>
                            @endif

                            <form action="{{ route('quotes.destroy', $quote->id) }}" method="POST" 
                                  onsubmit="return confirm('Delete this quote?');" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm transition duration-150 ease-in-out">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-6 text-gray-500">No quotes found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    <div class="mt-6 flex justify-end">
        {{ $quotes->links() }}
    </div>
</div>
@endsection
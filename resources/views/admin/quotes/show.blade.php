@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto p-6 bg-white rounded shadow">

    {{-- HEADER --}}
    <div class="flex justify-between items-start mb-6">
        
        {{-- Logo --}}
        <div class="flex-shrink-0 mr-4">
            @if($quote->company_logo)
                <img src="{{ asset('storage/' . $quote->company_logo) }}"
                     style="max-height:80px;">
            @endif
        </div>

        {{-- Quote Info + Company --}}
        <div class="flex-grow">
            <div class="flex justify-between items-start mb-2">

                <div>
                    <h1 class="text-2xl font-bold">
                        Quote {{ $quote->quote_number }}
                    </h1>

                    <p class="text-gray-600">
                        Created: {{ $quote->created_at->format('d M Y') }}
                    </p>

                    @if($quote->expiry_date)
                        <p class="text-gray-600">
                            Expiry: {{ \Carbon\Carbon::parse($quote->expiry_date)->format('d M Y') }}
                        </p>
                    @endif
                </div>

                <div class="text-right">
                    <h3 class="font-bold text-lg">
                        {{ $quote->company_name }}
                    </h3>
                    <p>{{ $quote->company_address }}</p>
                    <p>{{ $quote->company_phone }}</p>
                    <p>{{ $quote->company_email }}</p>
                </div>

            </div>
        </div>
    </div>

    <hr class="mb-6">

    {{-- CLIENT --}}
    <div class="mb-6">
        <p><strong>Bill To:</strong> {{ $quote->client_name }}</p>
        <p><strong>Physical Address:</strong> {{ $quote->client_address }}</p>
        <p><strong>Cell Phone:</strong> {{ $quote->client_phone }}</p>
        <p><strong>Email:</strong> {{ $quote->client_email }}</p>
    </div>

    {{-- ITEMS --}}
    <table class="w-full border-collapse mb-6 text-sm">
        <thead>
            <tr class="border-b bg-gray-100">
                <th class="text-left p-2">Product</th>
                <th class="text-center p-2">Qty</th>
                <th class="text-right p-2">Price</th>
                <th class="text-right p-2">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $subtotal = 0; @endphp

            @foreach($quote->items as $item)
                @php
                    $lineTotal = $item->quantity * $item->price;
                    $subtotal += $lineTotal;
                @endphp

                <tr class="border-b">
                    <td class="p-2">
                        {{ $item->product->name ?? 'Product Deleted' }}
                    </td>
                    <td class="text-center p-2">
                        {{ $item->quantity }}
                    </td>
                    <td class="text-right p-2">
                        KES {{ number_format($item->price, 2) }}
                    </td>
                    <td class="text-right p-2">
                        KES {{ number_format($lineTotal, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TOTALS --}}
    @php
        $taxAmount = ($subtotal * $quote->tax_percent) / 100;
    @endphp

    <div class="text-right space-y-1">
        <p>
            Subtotal: KES
            {{ number_format($subtotal, 2) }}
        </p>

        <p>
            Tax ({{ $quote->tax_percent }}%):
            {{ number_format($taxAmount, 2) }}
        </p>

        <p>
            Discount: KES
            {{ number_format($quote->discount, 2) }}
        </p>

        <h3 class="font-bold text-lg mt-2">
            Total: KES
            {{ number_format($quote->total_amount, 2) }}
        </h3>
    </div>

    {{-- NOTES (Quote Notes OR Tenant Default Notes) --}}
    @php
        $notes = $quote->notes ?? ($quote->tenant->default_notes ?? null);
    @endphp

    @if($notes)
        <div class="mt-6">
            <h3 class="font-semibold mb-2">Notes</h3>
            <p>{!! nl2br(e($notes)) !!}</p>
        </div>
    @endif

    {{-- ACTIONS --}}
    <div class="mt-8 flex gap-3">

        <a href="{{ route('quotes.edit',$quote->id) }}"
           class="bg-indigo-600 text-white px-4 py-2 rounded">
            Edit
        </a>

        <a href="{{ route('quotes.index') }}"
           class="bg-gray-500 text-white px-4 py-2 rounded">
            Back
        </a>

        <a href="{{ route('quotes.pdf', $quote->id) }}"
           class="bg-green-600 text-white px-4 py-2 rounded">
            Download PDF
        </a>

    </div>

</div>
@endsection
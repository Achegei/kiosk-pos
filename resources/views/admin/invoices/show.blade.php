@extends('layouts.admin')
@section('title','Invoice #'.($invoice->invoice_number ? 'INV-'.str_pad($invoice->invoice_number,5,'0',STR_PAD_LEFT) : 'INV-'.$invoice->id))

@section('content')
<div class="p-6 max-w-5xl mx-auto bg-white rounded shadow">

    {{-- HEADER --}}
    <div class="flex justify-between items-start mb-6">
        {{-- Logo --}}
        <div class="flex-shrink-0 mr-4">
            @if($invoice->company_logo ?? $invoice->tenant?->logo)
                <img src="{{ asset('storage/' . ($invoice->company_logo ?? $invoice->tenant->logo)) }}" style="max-height:80px;">
            @endif
        </div>

        {{-- Invoice Info + Company --}}
        <div class="flex-grow flex justify-between items-start mb-2">
            <div>
                <h1 class="text-2xl font-bold">
                    {{ str_pad($invoice->invoice_number, 5, '0', STR_PAD_LEFT) }}
                </h1>
                <p class="text-gray-600">Date: {{ $invoice->created_at->format('d M Y') }}</p>
                @if($invoice->expiry_date)
                    <p class="text-gray-600">Expiry: {{ \Carbon\Carbon::parse($invoice->expiry_date)->format('d M Y') }}</p>
                @endif
            </div>

            <div class="text-right">
                <h3 class="font-bold text-lg">{{ $invoice->company_name ?? $invoice->tenant?->name ?? 'Company Name' }}</h3>
                <p>
                    @if(!empty($invoice->company_address))
                        {{ $invoice->company_address }}
                    @elseif(!empty($invoice->tenant?->street_address) || !empty($invoice->tenant?->building_name) || !empty($invoice->tenant?->office_number))
                        {{ trim(($invoice->tenant->street_address ?? '') . ' ' . ($invoice->tenant->building_name ?? '') . ' ' . ($invoice->tenant->office_number ?? '')) }}
                    @else
                        Address not set
                    @endif
                </p>
                <p>{{ $invoice->company_phone ?? $invoice->tenant?->phone ?? '' }}</p>
                <p>{{ $invoice->company_email ?? $invoice->tenant?->email ?? '' }}</p>
            </div>
        </div>
    </div>

    <hr class="mb-6">

    {{-- CLIENT --}}
    <div class="mb-6">
        <p><strong>Bill To:</strong> {{ $invoice->client_name ?? $invoice->customer?->name ?? 'N/A' }}</p>
        <p><strong>Email:</strong> {{ $invoice->client_email ?? $invoice->customer?->email ?? 'N/A' }}</p>
        <p><strong>Phone:</strong> {{ $invoice->client_phone ?? $invoice->customer?->phone ?? 'N/A' }}</p>
        <p><strong>Address:</strong> {{ $invoice->client_address ?? $invoice->customer?->address ?? 'N/A' }}</p>
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
            @foreach($invoice->items as $item)
                @php
                    $lineTotal = ($item->quantity ?? 0) * ($item->price ?? 0);
                    $subtotal += $lineTotal;
                @endphp
                <tr class="border-b">
                    <td class="p-2">{{ $item->product?->name ?? 'Deleted Product' }}</td>
                    <td class="text-center p-2">{{ $item->quantity ?? 0 }}</td>
                    <td class="text-right p-2">KES {{ number_format($item->price ?? 0, 2) }}</td>
                    <td class="text-right p-2">KES {{ number_format($lineTotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TOTALS --}}
    @php
        $taxAmount = ($subtotal * ($invoice->tax_percent ?? 0)) / 100;
        $totalAmount = max($subtotal + $taxAmount - ($invoice->discount ?? 0), 0);
    @endphp
    <div class="text-right space-y-1 mb-6">
        <p>Subtotal: KES {{ number_format($subtotal, 2) }}</p>
        <p>Tax ({{ $invoice->tax_percent ?? 0 }}%): KES {{ number_format($taxAmount, 2) }}</p>
        <p>Discount: KES {{ number_format($invoice->discount ?? 0, 2) }}</p>
        <h3 class="font-bold text-lg">Total: KES {{ number_format($totalAmount, 2) }}</h3>
    </div>

    {{-- NOTES --}}
    @php
        $notes = $invoice->notes ?? $invoice->tenant?->default_notes;
    @endphp
    @if($notes)
        <div class="mt-6">
            <h3 class="font-semibold mb-2">Notes</h3>
            <p>{!! nl2br(e($notes)) !!}</p>
        </div>
    @endif

    {{-- ACTIONS --}}
    <div class="mt-8 flex gap-3 flex-wrap">
        <a href="{{ route('invoices.pdf', $invoice->id) }}" target="_blank"
           class="bg-green-600 text-white px-4 py-2 rounded">Download PDF</a>

        @foreach(['Paid' => 'bg-green-600', 'On Credit' => 'bg-yellow-500'] as $status => $color)
            @if($invoice->status !== $status)
                <form action="{{ route('invoices.updateStatus', $invoice->id) }}" method="POST" class="inline-block">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ $status }}">
                    <button type="submit" class="{{ $color }} text-white px-4 py-2 rounded">Mark as {{ $status }}</button>
                </form>
            @endif
        @endforeach
    </div>

</div>
@endsection
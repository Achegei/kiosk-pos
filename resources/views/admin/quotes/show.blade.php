@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto p-6 bg-white rounded shadow">

{{-- HEADER --}}
<div class="flex justify-between items-start mb-6">
    <!-- Logo -->
    <div class="flex-shrink-0 mr-4">
        @if($quote->company_logo)
            <img src="{{ asset('storage/' . $quote->company_logo) }}"
                 style="max-height:80px;">
        @endif
    </div>

    <!-- Company Info and Quote Number -->
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

            <!-- Company Details -->
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
        <p><strong>Physicall Adress:</strong> {{ $quote->client_address }}</p>
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
                    <td class="p-2">{{ $item->product->name ?? 'Product Deleted' }}</td>
                    <td class="text-center p-2">{{ $item->quantity }}</td>
                    <td class="text-right p-2">{{ number_format($item->price,2) }}</td>
                    <td class="text-right p-2">{{ number_format($lineTotal,2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TOTALS --}}
    <div class="text-right space-y-1">
        <p>Subtotal: {{ number_format($subtotal,2) }}</p>

        <p>
            Tax ({{ $quote->tax_percent }}%):
            {{ number_format(($subtotal * $quote->tax_percent)/100,2) }}
        </p>

        <p>
            Discount:
            {{ number_format($quote->discount,2) }}
        </p>

        <h3 class="font-bold text-lg mt-2">
            Total: {{ number_format($quote->total_amount,2) }}
        </h3>
    </div>

    {{-- NOTES --}}
    @if($quote->notes)
        <div class="mt-6">
            <h3 class="font-semibold mb-2">Notes</h3>
            <p>{{ $quote->notes }}</p>
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

        <button onclick="printQuote()"
                class="bg-green-600 text-white px-4 py-2 rounded">
            Print
        </button>
    </div>

</div>

<script>
function printQuote() {
    // Store values from Blade in JS variables first
    const quoteNumber = "{{ $quote->quote_number }}";
    const companyName = "{{ $quote->company_name }}";
    const companyAddress = "{{ $quote->company_address }}";
    const companyPhone = "{{ $quote->company_phone }}";
    const companyEmail = "{{ $quote->company_email }}";
    const companyLogo = "{{ $quote->company_logo ? asset('storage/' . $quote->company_logo) : '' }}";

    const clientName = "{{ $quote->client_name }}";
    const clientAddress = "{{ $quote->client_address }}";
    const clientPhone = "{{ $quote->client_phone }}";
    const clientEmail = "{{ $quote->client_email }}";

    const taxPercent = "{{ $quote->tax_percent }}";
    const discount = "{{ number_format($quote->discount,2) }}";
    const totalAmount = "{{ number_format($quote->total_amount,2) }}";

    // Inject notes: use quote notes or fallback to tenant default notes
    const rawNotes = `{{ $quote->notes ?? $quote->tenant->default_notes ?? '' }}`;

    // Format notes as numbered list
    const formattedNotes = rawNotes
        .split(/\r?\n|\d\.\s+/) // split on newlines or numbers followed by dot
        .filter(line => line.trim() !== '')
        .map(line => `<li>${line.trim()}</li>`)
        .join('');

    const notesHTML = formattedNotes ? `<div style="margin-top:15px;">
        <h3><strong>Notes:</strong></h3>
        <ol>${formattedNotes}</ol>
    </div>` : '';

    let printContent = `
    <div style="font-family:sans-serif;max-width:800px;margin:auto;">
        <!-- HEADER -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
            <div style="border:1px solid #000;padding:10px;font-weight:bold;">
                Quote ${quoteNumber}
            </div>

            <div style="flex-grow:1; padding-left:20px;">
                <h3>${companyName}</h3>
                <p>${companyAddress}</p>
                <p>${companyPhone}</p>
                <p>${companyEmail}</p>
            </div>

            ${companyLogo ? `<img src="${companyLogo}" style="max-height:80px;" id="companyLogo">` : ''}
        </div>
        <hr style="margin:10px 0;">

        <div style="margin-bottom:10px;">
            <p><strong>Bill To:</strong> ${clientName}</p>
            <p><strong>Physical Address:</strong> ${clientAddress}</p>
            <p><strong>Cell Phone:</strong> ${clientPhone}</p>
            <p><strong>Email:</strong> ${clientEmail}</p>
        </div>

        <table border="1" width="100%" cellpadding="5" cellspacing="0" style="margin-top:10px;border-collapse:collapse;">
            <thead style="background-color:#f0f0f0;">
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
    `;

    @foreach($quote->items as $item)
        printContent += `
            <tr>
                <td>{{ $item->product->name ?? 'Product Deleted' }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->price,2) }}</td>
                <td>{{ number_format($item->quantity * $item->price,2) }}</td>
            </tr>
        `;
    @endforeach

    printContent += `
            </tbody>
        </table>

        <div style="margin-top:10px;text-align:right;">
            <p><strong>Tax:</strong> ${taxPercent}%</p>
            <p><strong>Discount:</strong> ${discount}</p>
            <h3><strong>Total:</strong> ${totalAmount}</h3>
        </div>

        ${notesHTML}
    </div>
    `;

    const w = window.open('', '_blank');
    w.document.write(printContent);
    w.document.close();

    // Wait for all images to load before printing
    const images = w.document.images;
    let loaded = 0;
    const total = images.length;

    if(total === 0){
        w.print();
    } else {
        for(let img of images){
            img.onload = img.onerror = function() {
                loaded++;
                if(loaded === total){
                    w.print();
                }
            };
        }
    }
}
</script>
@endsection
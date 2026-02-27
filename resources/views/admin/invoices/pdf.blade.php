<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice->invoice_number ? 'INV-' . str_pad($invoice->invoice_number, 5, '0', STR_PAD_LEFT) : 'INV-' . str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 13px;
            color: #333;
        }

        .header {
            width: 100%;
            margin-bottom: 20px;
        }

        .company {
            text-align: right;
        }

        .logo {
            max-height: 80px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        table th {
            background: #f2f2f2;
        }

        .totals {
            margin-top: 15px;
            text-align: right;
        }

        .totals p {
            margin: 3px 0;
        }

        .total-final {
            font-size: 16px;
            font-weight: bold;
        }

        .notes {
            margin-top: 25px;
        }

        .box-number {
            border: 1px solid #000;
            padding: 6px 10px;
            font-weight: bold;
            display: inline-block;
        }
    </style>
</head>
<body>

{{-- HEADER --}}
<table class="header">
    <tr>
        <td>
            <div class="box-number">
                Invoice #{{ $invoice->invoice_number ? 'INV-' . str_pad($invoice->invoice_number, 5, '0', STR_PAD_LEFT) : 'INV-' . str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}
            </div>
            <p>Date: {{ $invoice->created_at->format('d M Y') }}</p>
            @if($invoice->expiry_date)
                <p>Expiry: {{ \Carbon\Carbon::parse($invoice->expiry_date)->format('d M Y') }}</p>
            @endif
        </td>

        <td class="company">
            @if(!empty($invoice->company_logo) && file_exists(public_path('storage/' . $invoice->company_logo)))
                <img src="{{ public_path('storage/' . $invoice->company_logo) }}" class="logo">
            @elseif(!empty($invoice->tenant?->logo) && file_exists(public_path('storage/' . $invoice->tenant->logo)))
                <img src="{{ public_path('storage/' . $invoice->tenant->logo) }}" class="logo">
            @endif

            <h3>{{ $invoice->company_name ?? $invoice->tenant?->name ?? 'Company Name' }}</h3>
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
        </td>
    </tr>
</table>

<hr>

{{-- CLIENT --}}
<h4>Bill To:</h4>
<p><strong>{{ $invoice->client_name ?? $invoice->customer->name ?? 'N/A' }}</strong></p>
<p>{{ $invoice->client_address ?? $invoice->customer->address ?? 'N/A' }}</p>
<p>{{ $invoice->client_phone ?? $invoice->customer->phone ?? 'N/A' }}</p>
<p>{{ $invoice->client_email ?? $invoice->customer->email ?? 'N/A' }}</p>

{{-- ITEMS --}}
<table>
    <thead>
        <tr>
            <th align="left">Product</th>
            <th align="center">Qty</th>
            <th align="right">Price</th>
            <th align="right">Total</th>
        </tr>
    </thead>
    <tbody>
        @php $subtotal = 0; @endphp
        @foreach($invoice->items as $item)
            @php
                $lineTotal = ($item->quantity ?? 0) * ($item->price ?? 0);
                $subtotal += $lineTotal;
            @endphp
            <tr>
                <td>{{ $item->product->name ?? 'Product Deleted' }}</td>
                <td align="center">{{ $item->quantity ?? 0 }}</td>
                <td align="right">{{ number_format($item->price ?? 0,2) }}</td>
                <td align="right">{{ number_format($lineTotal,2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- TOTALS --}}
@php
    $taxPercent = $invoice->tax_percent ?? 0;
    $discount = $invoice->discount ?? 0;
    $taxAmount = ($subtotal * $taxPercent) / 100;
    $totalAmount = max($subtotal + $taxAmount - $discount, 0);
@endphp
<div class="totals">
    <p>Subtotal: KES {{ number_format($subtotal,2) }}</p>
    <p>Tax ({{ $taxPercent }}%): {{ number_format($taxAmount,2) }}</p>
    <p>Discount: KES {{ number_format($discount,2) }}</p>
    <p class="total-final">Total: KES {{ number_format($totalAmount,2) }}</p>
</div>

{{-- NOTES --}}
@php
    $notes = $invoice->notes ?? $tenant->default_notes ?? null;
@endphp

@php
    $notes = $invoice->notes ?? ($invoice->tenant->default_notes ?? null);
@endphp

@if($notes)
    <div class="mt-6">
        <h3 class="font-semibold mb-2">Notes</h3>
        <p>{!! nl2br(e($notes)) !!}</p>
    </div>
@endif

</body>
</html>
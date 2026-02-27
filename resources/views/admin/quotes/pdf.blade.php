<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quote {{ $quote->quote_number }}</title>

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
                Quote {{ $quote->quote_number }}
            </div>
            <p>Date: {{ $quote->created_at->format('d M Y') }}</p>
            @if($quote->expiry_date)
                <p>Expiry: {{ \Carbon\Carbon::parse($quote->expiry_date)->format('d M Y') }}</p>
            @endif
        </td>

        <td class="company">
            @if($quote->company_logo)
                <img src="{{ public_path('storage/'.$quote->company_logo) }}" class="logo">
            @endif

            <h3>{{ $quote->company_name }}</h3>
            <p>{{ $quote->company_address }}</p>
            <p>{{ $quote->company_phone }}</p>
            <p>{{ $quote->company_email }}</p>
        </td>
    </tr>
</table>

<hr>

{{-- CLIENT --}}
<h4>Bill To:</h4>
<p><strong>{{ $quote->client_name }}</strong></p>
<p>{{ $quote->client_address }}</p>
<p>{{ $quote->client_phone }}</p>
<p>{{ $quote->client_email }}</p>

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

        @foreach($quote->items as $item)
            @php
                $lineTotal = $item->quantity * $item->price;
                $subtotal += $lineTotal;
            @endphp
            <tr>
                <td>{{ $item->product->name ?? 'Product Deleted' }}</td>
                <td align="center">{{ $item->quantity }}</td>
                <td align="right">{{ number_format($item->price,2) }}</td>
                <td align="right">{{ number_format($lineTotal,2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- TOTALS --}}
<div class="totals">
    <p>Subtotal: KES {{ number_format($subtotal,2) }}</p>

    <p>
        Tax ({{ $quote->tax_percent }}%):
        KES {{ number_format(($subtotal * $quote->tax_percent)/100,2) }}
    </p>

    <p>
        Discount:
        KES {{ number_format($quote->discount,2) }}
    </p>

    <p class="total-final">
        Total: KES {{ number_format($quote->total_amount,2) }}
    </p>
</div>

{{-- NOTES --}}
@php
    $notes = $quote->notes ?? $quote->tenant->default_notes ?? null;
@endphp

@if($notes)
<div class="notes">
    <h4>Notes:</h4>
    <ol>
        @foreach(preg_split('/\r\n|\r|\n/', $notes) as $line)
            @if(trim($line))
                <li>{{ trim($line) }}</li>
            @endif
        @endforeach
    </ol>
</div>
@endif

</body>
</html>
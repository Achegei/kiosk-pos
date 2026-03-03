<!DOCTYPE html>
<html>
<head>
<style>
body { font-family: monospace; font-size: 12px; width: 80mm; margin: auto; }
.center { text-align:center; }
.row { display:flex; justify-content:space-between; }
.row-note { padding-left:10px; font-size:11px; }
hr { border-top:1px dashed #000; margin:4px 0; }
.big { font-size:16px; font-weight:bold; text-align:center; margin-top:6px; }
.ok { font-weight:bold; }
.bad { font-weight:bold; color:red; }
</style>
</head>
<body>

<div class="center">
    <div style="font-size:16px;font-weight:bold;">{{ $tenant->name }}</div>
    {{ $tenant->address ?? '' }}<br>
    Tel: {{ $tenant->phone ?? '' }}
</div>

<hr>

<div class="center"><strong>END OF DAY REPORT</strong></div>

<hr>

Cashier: {{ $session->user->name }}<br>
User ID: {{ $session->user_id }}<br>
Session: {{ $session->id }}<br>
Opened: {{ $session->opened_at }}<br>
Closed: {{ $session->closed_at }}<br>

<hr>

<div class="row">
    <div>Opening Cash</div>
    <div>KES {{ number_format($session->opening_cash,2) }}</div>
</div>
<div class="row">
    <div>Cash Sales</div>
    <div>KES {{ number_format($sales->cash,2) }}</div>
</div>
<div class="row">
    <div>Mpesa Sales</div>
    <div>KES {{ number_format($sales->mpesa,2) }}</div>
</div>
<div class="row">
    <div>Credit Sales</div>
    <div>KES {{ number_format($sales->credit,2) }}</div>
</div>

<hr>

<strong>Cash Movements</strong>
@php
    $types = ['drop'=>'Drops','expense'=>'Expenses','payout'=>'Payouts','deposit'=>'Deposits','adjustment'=>'Adjustments'];

    $movementTotals = [
        'drop' => $movements->drops ?? 0,
        'expense' => $movements->expenses ?? 0,
        'payout' => $movements->payouts ?? 0,
        'deposit' => $movements->deposits ?? 0,
        'adjustment' => $movements->adjustments ?? 0,
    ];
@endphp

@foreach($types as $key => $label)
    @php
        $items = $allMovements->where('type', $key);
        $total = $movementTotals[$key];
    @endphp

    @if($items->count())
        <div class="row" style="font-weight:bold; border-bottom:1px dashed #000; margin-top:4px; padding-bottom:2px;">
            <div>{{ $label }} Total</div>
            <div>KES {{ number_format($total,2) }}</div>
        </div>

        @foreach($items as $item)
            <div class="row row-note">
                <div>{{ $item->note ?? '-' }}</div>
                <div>KES {{ number_format($item->amount,2) }}</div>
            </div>
        @endforeach
    @endif
@endforeach

<hr>

<div class="big">
EXPECTED CASH<br>
KES {{ number_format($expectedCash,2) }}
</div>

<div class="big">
COUNTED CASH<br>
KES {{ number_format($session->closing_cash,2) }}
</div>

<div class="big {{ $difference == 0 ? 'ok' : 'bad' }}">
DIFFERENCE<br>
KES {{ number_format($difference,2) }}
</div>

<hr><br>

Cashier Sign:<br><br>
________________________<br><br>

Supervisor Sign:<br><br>
________________________<br><br>

<div class="center">SYSTEM GENERATED REPORT</div>

</body>
</html>
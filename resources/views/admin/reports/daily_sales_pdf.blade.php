<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Daily Sales Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f2f2f2; text-align: left; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
    </style>
</head>
<body>
    <h2>Daily Sales Report</h2>
    <p>From: {{ $start }} To: {{ $end }}</p>
    <table>
        <thead>
            <tr>
                <th>Customer</th>
                <th>Items</th>
                <th class="text-center">Total</th>
                <th class="text-center">Status</th>
                <th class="text-center">Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $txn)
                <tr>
                    <td>{{ optional($txn->customer)->name ?? 'Walk-in' }}</td>
                    <td>
                        @foreach($txn->items as $item)
                            {{ optional($item->product)->name }} x{{ $item->quantity }}<br>
                        @endforeach
                    </td>
                    <td class="text-center">KSh {{ number_format($txn->total_amount, 2) }}</td>
                    <td class="text-center">{{ $txn->status }}</td>
                    <td class="text-center">{{ $txn->created_at->format('d-M-Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
<!DOCTYPE html>
<html>
<head>
    <title>Low Stock Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h2>Low Stock Products Report</h2>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Quantity</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lowStock as $item)
                <tr>
                    <td>{{ optional($item->product)->name ?? 'Unknown' }}</td>
                    <td>{{ $item->quantity }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">No low stock products.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
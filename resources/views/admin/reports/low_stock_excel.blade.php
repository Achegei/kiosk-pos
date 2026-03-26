<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Low Stock Report</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <h2>Low Stock Report</h2>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Low Stock Threshold</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lowStock as $item)
                <tr>
                    <td>{{ optional($item->product)->name ?? 'N/A' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->low_stock_threshold ?? 5 }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center;">No low stock items found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
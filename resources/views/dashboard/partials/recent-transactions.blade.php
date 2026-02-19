<div class="bg-white shadow rounded-lg p-6 mt-6">
    <h3 class="text-lg font-semibold text-gray-700 mb-4">Recent Transactions</h3>
    @if($recentTransactions->isEmpty())
        <p class="text-gray-500">No transactions yet.</p>
    @else
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentTransactions as $txn)
                    <tr>
                        <td>{{ $txn->customer->name ?? 'Walk-in' }}</td>
                        <td>
                            @foreach($txn->items as $item)
                                <div>{{ $item->product->name }} x{{ $item->quantity }}</div>
                            @endforeach
                        </td>
                        <td>${{ number_format($txn->total_amount, 2) }}</td>
                        <td>{{ $txn->status }}</td>
                        <td>{{ $txn->created_at->format('d M Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

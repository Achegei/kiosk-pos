<div class="bg-gray-50 p-4 rounded shadow mt-6">

    <h2 class="text-xl font-semibold mb-4">Recent Transactions</h2>

    @if($recentTransactions->isEmpty())

        <div class="bg-white border rounded p-6 text-center text-gray-400">
            No transactions yet
        </div>

    @else

        <table class="min-w-full bg-white border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-2 px-4 border text-left">Customer</th>
                    <th class="py-2 px-4 border text-left">Items</th>
                    <th class="py-2 px-4 border">Total</th>
                    <th class="py-2 px-4 border">Status</th>
                    <th class="py-2 px-4 border">Date</th>
                </tr>
            </thead>

            <tbody>

                @foreach($recentTransactions as $txn)

                <tr class="hover:bg-gray-50">

                    <td class="py-2 px-4 border">
                        {{ $txn->customer->name ?? 'Walk-in' }}
                    </td>

                    <td class="py-2 px-4 border">

                        @foreach($txn->items as $item)
                            <div class="text-sm">
                                {{ $item->product->name }} x{{ $item->quantity }}
                            </div>
                        @endforeach

                    </td>

                    <td class="py-2 px-4 border text-center font-semibold">
                        KSh {{ number_format($txn->total_amount, 2) }}
                    </td>

                    <td class="py-2 px-4 border text-center">

                        @if($txn->status === 'Paid')
                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-sm">
                                Paid
                            </span>

                        @elseif($txn->status === 'On Credit')
                            <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-sm">
                                Credit
                            </span>

                        @else
                            <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-sm">
                                {{ $txn->status }}
                            </span>
                        @endif

                    </td>

                    <td class="py-2 px-4 border text-center">
                        {{ $txn->created_at->format('d-M-Y H:i') }}
                    </td>

                </tr>

                @endforeach

            </tbody>
        </table>

    @endif

</div>

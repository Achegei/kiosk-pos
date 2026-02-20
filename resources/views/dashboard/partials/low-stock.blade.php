<div class="bg-gray-50 p-4 rounded shadow mt-6">

    <h2 class="text-xl font-semibold mb-4 text-indigo-700">Low Stock Alerts</h2>

    @if($lowStock->isEmpty())

        <div class="bg-white border rounded p-6 text-center text-gray-400">
            All products have sufficient stock
        </div>

    @else

        <p class="text-gray-600 mb-3">
            Products below the low stock threshold ({{ $lowStockThreshold }} units)
        </p>

        <table class="min-w-full bg-white border">

            <thead class="bg-gray-100">
                <tr>
                    <th class="py-2 px-4 border text-left">Product</th>
                    <th class="py-2 px-4 border text-left">SKU / Barcode</th>
                    <th class="py-2 px-4 border text-center">Quantity</th>
                    <th class="py-2 px-4 border text-center">Threshold</th>
                </tr>
            </thead>

            <tbody>

                @foreach($lowStock as $inventory)

                    @php
                        // Calculate pending offline sales for this product
                        $pendingOffline = DB::table('offline_sales')
                            ->where('synced', 0)
                            ->whereJsonContains('sale_data->products', ['product_id' => $inventory->product_id])
                            ->sum(DB::raw("JSON_EXTRACT(sale_data, '$.quantity')"));

                        // Adjusted available quantity
                        $availableQty = $inventory->quantity - $pendingOffline;
                    @endphp

                    <tr class="hover:bg-gray-50">

                        <td class="py-2 px-4 border font-medium">
                            {{ $inventory->product->name }}
                        </td>

                        <td class="py-2 px-4 border">
                            {{ $inventory->product->sku ?? '-' }}
                        </td>

                        <td class="py-2 px-4 border text-center">

                            @if($availableQty <= 0)
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-sm font-semibold">
                                    OUT
                                </span>

                            @elseif($availableQty <= 3)
                                <span class="bg-red-100 text-red-600 px-2 py-1 rounded text-sm font-semibold">
                                    {{ $availableQty }}
                                </span>

                            @else
                                <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-sm font-semibold">
                                    {{ $availableQty }}
                                </span>
                            @endif

                        </td>

                        <td class="py-2 px-4 border text-center text-gray-600">
                            {{ $lowStockThreshold }}
                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    @endif

</div>

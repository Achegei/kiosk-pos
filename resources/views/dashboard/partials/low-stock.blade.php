<div class="bg-white shadow rounded-lg p-6 mt-6">
    <h3 class="text-lg font-semibold text-gray-700 mb-4">Low Stock Alerts</h3>

    @if($lowStock->isEmpty())
        <p class="text-gray-500">All products have sufficient stock.</p>
    @else
        <p class="text-gray-600 mb-2">
    Products below the low stock threshold ({{ $lowStockThreshold }} units)
        </p>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Product</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">SKU / Barcode</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Quantity</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Threshold</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($lowStock as $inventory)
                        <tr>
                            <td class="px-4 py-2 font-medium">{{ $inventory->product->name }}</td>
                            <td class="px-4 py-2">{{ $inventory->product->sku ?? '-' }}</td>
                            <td class="px-4 py-2 text-red-600 font-semibold">{{ $inventory->quantity }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ $lowStockThreshold }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    @endif
</div>

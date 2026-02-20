<div class="bg-white p-4 rounded shadow mb-6">
    <h2 class="text-xl font-semibold mb-4 text-indigo-700">Active Devices</h2>

    @if($activeDevices->isEmpty())
        <div class="text-gray-400 text-center p-4">
            No active devices
        </div>
    @else
        <table class="w-full border bg-white">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 border text-left">Device Name</th>
                    <th class="p-2 border text-left">UUID</th>
                    <th class="p-2 border text-center">Unsynced Sales</th>
                    <th class="p-2 border text-center">License Expires</th>
                </tr>
            </thead>
            <tbody>
                @foreach($activeDevices as $device)
                <tr class="hover:bg-gray-50">
                    <td class="p-2 border">{{ $device['name'] }}</td>
                    <td class="p-2 border">{{ $device['uuid'] }}</td>
                    <td class="p-2 border text-center">
                        @if($device['unsynced_sales'] > 0)
                            <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-sm">
                                {{ $device['unsynced_sales'] }}
                            </span>
                        @else
                            <span class="text-gray-500">0</span>
                        @endif
                    </td>
                    <td class="p-2 border text-center">{{ $device['license_expires_at']->format('d-M-Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

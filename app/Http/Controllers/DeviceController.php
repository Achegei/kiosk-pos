<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;
use Carbon\Carbon;

class DeviceController extends Controller
{
    public function check(Request $request)
    {
        // Generate or fetch device UUID
        $uuid = $request->header('X-DEVICE-ID') 
            ?? $request->input('device_uuid') 
            ?? md5($request->ip() . $request->userAgent());

        // Sanity check
        if (!$uuid) {
            return response()->json([
                'allowed' => false,
                'message' => 'Missing device UUID'
            ], 403);
        }

        // Auto-register device
        $device = Device::firstOrCreate(
            ['device_uuid' => $uuid],
            [
                'device_name' => $request->userAgent(),
                'license_expires_at' => Carbon::now()->addDays(7) // 7-day free trial
            ]
        );

        // Check if license expired
        if ($device->license_expires_at && Carbon::now()->gt($device->license_expires_at)) {
            return response()->json([
                'allowed' => false,
                'expired' => true,
                'message' => 'Device license expired'
            ]);
        }

        return response()->json([
            'allowed' => true,
            'uuid' => $device->device_uuid,
            'name' => $device->device_name,
            'expires' => $device->license_expires_at?->format('Y-m-d H:i:s')
        ]);
    }
}

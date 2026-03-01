<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;
use Carbon\Carbon;

class DeviceController extends Controller
{
    public function check(Request $request)
{
    try {
        // Generate or fetch device UUID
        $uuid = $request->header('X-DEVICE-ID') 
            ?? $request->input('device_uuid') 
            ?? md5($request->ip() . $request->userAgent());

        // Sanity check
        if (!$uuid) {
            \Log::warning('Device check failed: missing UUID', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'allowed' => false,
                'message' => 'Missing device UUID'
            ], 403);
        }

        // Auto-register device
        try {
            $device = Device::firstOrCreate(
                ['device_uuid' => $uuid],
                [
                    'device_name' => $request->userAgent(),
                    'license_expires_at' => Carbon::now()->addDays(7) // 7-day free trial
                ]
            );
        } catch (\Throwable $e) {
            \Log::error('Device registration failed', [
                'uuid' => $uuid,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'allowed' => false,
                'message' => 'Failed to register device'
            ], 500);
        }

        // Check if license expired
        if ($device->license_expires_at && Carbon::now()->gt($device->license_expires_at)) {
            \Log::info('Device license expired', [
                'uuid' => $device->device_uuid,
                'device_name' => $device->device_name,
            ]);

            return response()->json([
                'allowed' => false,
                'expired' => true,
                'message' => 'Device license expired'
            ]);
        }

        // Success response
        return response()->json([
            'allowed' => true,
            'uuid' => $device->device_uuid,
            'name' => $device->device_name,
            'expires' => $device->license_expires_at?->format('Y-m-d H:i:s')
        ]);

    } catch (\Throwable $e) {
        \Log::error('Device check failed unexpectedly', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'allowed' => false,
            'message' => 'An unexpected error occurred during device check'
        ], 500);
    }
}
}

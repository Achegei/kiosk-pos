<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;
use Carbon\Carbon;

class DeviceController extends Controller
{
    public function check(Request $request)
    {
        $uuid = $request->header('X-DEVICE-ID') ?? $request->input('device_uuid');

        if(!$uuid){
            return response()->json([
                'allowed' => false,
                'message' => 'Missing device UUID'
            ],403);
        }

        // create device if not exists (auto register tablet)
        $device = Device::firstOrCreate(
            ['device_uuid' => $uuid],
            [
                'device_name' => $request->userAgent(),
                'license_expires_at' => Carbon::now()->addDays(7) // free trial
            ]
        );

        // check if expired
        if($device->license_expires_at && Carbon::now()->gt($device->license_expires_at)){
            return response()->json([
                'allowed' => false,
                'expired' => true,
                'message' => 'Device license expired'
            ]);
        }

        return response()->json([
            'allowed' => true,
            'expires' => $device->license_expires_at
        ]);
    }
}

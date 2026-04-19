<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DeviceController extends Controller
{
    public function check(Request $request)
    {
        try {

            /*
            |--------------------------------------------------------------------------
            | 1. GET TENANT (STRICT REQUIRED)
            |--------------------------------------------------------------------------
            */
            $user = $request->user();

            if (!$user || !$user->tenant_id) {
                return response()->json([
                    'allowed' => false,
                    'message' => 'Unauthenticated tenant'
                ], 401);
            }

            $tenantId = $user->tenant_id;

            /*
            |--------------------------------------------------------------------------
            | 2. DEVICE UUID (NO RANDOM GENERATION!)
            |--------------------------------------------------------------------------
            */
            $uuid = $request->header('X-DEVICE-ID')
                ?? $request->input('device_uuid');

            if (!$uuid) {
                Log::warning('Missing device UUID', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                return response()->json([
                    'allowed' => false,
                    'message' => 'Missing device UUID'
                ], 403);
            }
            /*
            |--------------------------------------------------------------------------
            | 3. FETCH DEVICE ONLY (NO CREATION)
            |--------------------------------------------------------------------------
            */
            $device = Device::where('tenant_id', $tenantId)
                ->where('device_uuid', $uuid)
                ->first();

            if (!$device) {
                Log::warning('Device not registered', [
                    'tenant_id' => $tenantId,
                    'uuid' => $uuid
                ]);

                return response()->json([
                    'allowed' => false,
                    'message' => 'Device not registered'
                ], 403);
            }

            /*
            |--------------------------------------------------------------------------
            | 4. DEVICE STATUS CHECK
            |--------------------------------------------------------------------------
            */
            if (!$device->is_active) {
                return response()->json([
                    'allowed' => false,
                    'message' => 'Device disabled'
                ]);
            }

            if ($device->license_expires_at &&
                now()->greaterThan($device->license_expires_at)) {

                return response()->json([
                    'allowed' => false,
                    'expired' => true,
                    'message' => 'Device license expired'
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 5. SUCCESS RESPONSE
            |--------------------------------------------------------------------------
            */
            return response()->json([
                'allowed' => true,
                'device_uuid' => $device->device_uuid,
                'name' => $device->device_name,
                'expires' => optional($device->license_expires_at)->toDateTimeString()
            ]);

        } catch (\Throwable $e) {

            Log::error('Device check crashed', [
                'ip' => $request->ip(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'allowed' => false,
                'message' => 'Unexpected error'
            ], 500);
        }
    }
}
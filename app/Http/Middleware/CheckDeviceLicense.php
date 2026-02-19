<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Device;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckDeviceLicense
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
{
    $uuid = $request->header('X-DEVICE-ID') ?? $request->input('device_uuid');

    if(!$uuid){
        return response()->json(['message'=>'Device ID missing'],403);
    }

    $device = Device::where('device_uuid',$uuid)->first();

    if(!$device){
        return response()->json(['message'=>'Device not registered'],403);
    }

    if(!$device->is_active){
        return response()->json(['message'=>'Device disabled'],403);
    }

    if($device->license_expires_at && Carbon::now()->gt($device->license_expires_at)){
        return response()->json(['message'=>'LICENSE_EXPIRED'],402);
    }

    return $next($request);
}
    }

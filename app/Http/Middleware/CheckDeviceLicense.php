<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Device;
use Carbon\Carbon;

class CheckDeviceLicense
{
    public function handle($request, Closure $next)
    {
        // ✅ allow login + public routes (PREVENT 419 LOOP)
        if(!$request->user()){
            return $next($request);
        }

        $user = $request->user();
        $tenantId = $user->tenant_id;

        // ✅ device UUID from header OR request OR browser fingerprint fallback
        $uuid =
            $request->header('X-DEVICE-ID')
            ?? $request->input('device_uuid')
            ?? md5($request->ip().$request->userAgent());

        if(!$uuid){
            return $this->deny($request,'DEVICE_ID_MISSING');
        }

        // ✅ TENANT SAFE lookup
        $device = Device::where('tenant_id',$tenantId)
            ->where('device_uuid',$uuid)
            ->first();

        // ✅ OPTIONAL AUTO REGISTER (recommended for POS)
        if(!$device){
            $device = Device::create([
                'tenant_id'=>$tenantId,
                'device_uuid'=>$uuid,
                'device_name'=>$request->userAgent(),
                'is_active'=>true,
                'license_expires_at'=>Carbon::now()->addDays(7)
            ]);
        }

        // disabled
        if(!$device->is_active){
            return $this->deny($request,'DEVICE_DISABLED');
        }

        // expired
        if($device->license_expires_at &&
            Carbon::now()->gt($device->license_expires_at)){
            return $this->deny($request,'LICENSE_EXPIRED',402);
        }

        return $next($request);
    }

    private function deny($request,$msg,$code=403)
    {
        // API → JSON
        if($request->expectsJson()){
            return response()->json(['message'=>$msg],$code);
        }

        // WEB → redirect
        return redirect()->route('dashboard')
            ->with('error',$msg);
    }
}
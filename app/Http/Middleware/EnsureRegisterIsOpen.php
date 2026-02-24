<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRegisterIsOpen
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Only enforce for POS-enabled staff
        if ($user && $user->canAccessPos()) {

            // Tenant-safe check
            $openRegister = $user->openRegister;

            if (!$openRegister || $openRegister->status !== 'open') {
                // API → JSON
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Register not open',
                        'action' => 'open_register'
                    ], 403);
                }

                // WEB → redirect to open register page
                return redirect()->route('register.open.form')
                    ->with('error', 'Please open your register first');
            }
        }

        return $next($request);
    }
}
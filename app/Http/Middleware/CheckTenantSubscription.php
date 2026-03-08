<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckTenantSubscription
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('webhooks/intasend')) {
            return $next($request);
        }
        $user = auth()->user();

        // Super Admin is never blocked
        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        if ($user && $user->tenant) {

            $tenant = $user->tenant;

            // Update subscription status automatically
            $tenant->updateSubscriptionStatus();

            /*
            |--------------------------------------------------------------------------
            | If tenant suspended
            |--------------------------------------------------------------------------
            */
            if ($tenant->subscription_status === 'suspended') {

                if (
                    !$request->routeIs('billing') &&
                    !$request->routeIs('tenant.paySaaS') &&
                    !$request->routeIs('logout')
                ) {

                    return redirect()
                        ->route('billing')
                        ->with('error', 'Subscription expired. Please pay to continue.');
                }
            }

            /*
            |--------------------------------------------------------------------------
            | If tenant terminated
            |--------------------------------------------------------------------------
            */
            if ($tenant->subscription_status === 'terminated') {

                auth()->logout();

                return redirect()
                    ->route('login')
                    ->withErrors('Account terminated. Please contact support.');
            }
        }

        return $next($request);
    }
}
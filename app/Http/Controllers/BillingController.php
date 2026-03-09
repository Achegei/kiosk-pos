<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;

class BillingController extends Controller
{
    /**
     * Show the billing page for the tenant.
     */
    public function index()
    {
        $tenant = Auth::user()->tenant;

        return view('billing.index', compact('tenant'));
    }

    /**
     * Show the success page after a successful payment.
     */
    public function success(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $tenant = $user->tenant;

        if (!$tenant) {
            return redirect()->route('billing.index')
                ->with('error', 'Tenant not found.');
        }

        // Get the api_ref from query string
        $apiRef = $request->query('api_ref');

        if (!$apiRef) {
            // fallback: get the latest payment
            $payment = Payment::where('tenant_id', $tenant->id)
                ->latest()
                ->first();
        } else {
            $payment = Payment::where('tenant_id', $tenant->id)
                ->where('api_ref', $apiRef)
                ->first();
        }

        if (!$payment) {
            return redirect()->route('billing.index')
                ->with('error', 'Payment record not found.');
        }

        return view('billing.success', compact('payment'));
    }
}
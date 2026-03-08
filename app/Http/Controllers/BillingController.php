<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BillingController extends Controller
{
    /**
     * Show the billing page for the tenant.
     */
    public function index()
    {
        $tenant = auth()->user()->tenant;

        return view('billing.index', compact('tenant'));
    }

    /**
     * Show the success page after a successful payment.
     */
    public function success(Request $request)
    {
        $apiRef = $request->query('api_ref'); // or from session
        return view('billing.success', compact('apiRef'));
    }
}
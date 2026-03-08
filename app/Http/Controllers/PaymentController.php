<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use IntaSend\IntaSendPHP\Checkout;
use IntaSend\IntaSendPHP\Customer;

class PaymentController extends Controller
{
    /**
     * Start a SaaS subscription payment via IntaSend
     */
    public function paySaaS(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Please log in first.');
        }

        $tenant = $user->tenant;

        if (!$tenant) {
            return back()->with('error', 'Tenant not found.');
        }

        // Clean tenant name for IntaSend
        $sanitize = fn($str) => substr(preg_replace('/[^A-Za-z0-9 _-]/', '', $str), 0, 50);

        $customer = new Customer();
        $customer->first_name = $sanitize($tenant->name);
        $customer->last_name  = $sanitize($tenant->name);
        $customer->email      = $tenant->email;
        $customer->country    = "KE";
        $customer->phone      = $tenant->phone ?? '';

        $amount   = 10; 
        $currency = "KES";

        $apiRef = "saas-tenant{$tenant->id}-payment-" . time();

        // Initialize IntaSend
        $checkout = new Checkout();

        $checkout->init([
            'token'           => config('intasend.secret_key'),
            'publishable_key' => config('intasend.publishable_key'),
            'environment'     => config('intasend.test_mode') ? 'sandbox' : 'live',
        ]);

        // Detect host automatically (works with localhost, ngrok, production)
        $host = $request->getSchemeAndHttpHost();

        $redirect_url = $host . route('billing.success', [], false);

        try {

            $response = $checkout->create(
                $amount,
                $currency,
                $customer,
                $host,
                $redirect_url,
                $apiRef,
                null,
                "M-PESA"
            );

            Log::info("[IntaSend Checkout Created]", (array) $response);

        } catch (\Throwable $e) {

            Log::error("[IntaSend Error] " . $e->getMessage());

            return back()->with('error', 'Could not initialize payment.');
        }

        $invoiceId = $response->invoice->invoice_id ?? $response->id ?? null;

        if (!$invoiceId) {

            Log::error("[IntaSend] Missing invoice_id", (array) $response);

            return back()->with('error', 'Payment could not be started.');
        }

        // Save payment record
        Payment::updateOrCreate(
            [
                'payment_reference' => $invoiceId
            ],
            [
                'tenant_id'        => $tenant->id,
                'payment_provider' => 'intasend',
                'status'           => 'pending',
                'api_ref'          => $apiRef,
                'amount'           => $amount,
                'payload'          => json_encode($response)
            ]
        );

        if (!isset($response->url)) {

            Log::error("[IntaSend] Payment URL missing", (array) $response);

            return back()->with('error', 'Payment URL missing.');
        }

        return redirect($response->url);
    }


    /**
     * Redirect after payment
     */
    public function success()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $tenant = $user->tenant;

        if (!$tenant) {
            return redirect()->route('billing')
                ->with('error', 'Tenant not found.');
        }

        $payment = Payment::where('tenant_id', $tenant->id)
            ->latest()
            ->first();

        if (!$payment) {
            return redirect()->route('billing')
                ->with('error', 'Payment not found.');
        }

        return view('billing.success', compact('payment'))
            ->with(
                $payment->status === 'paid'
                    ? ['success' => '🎉 Payment confirmed! Your subscription is active.']
                    : ['info' => 'Your payment is still processing.']
            );
    }
}
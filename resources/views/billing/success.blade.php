@extends('layouts.admin')

@section('title','Subscription Billing')
@section('page-title','Subscription Billing')

@section('content')
<div class="max-w-3xl mx-auto mt-12 p-8 bg-white shadow-xl rounded-2xl">
    <h1 class="text-3xl font-bold mb-4 text-gray-800">Subscription Payment</h1>

    <div id="status-card" class="p-6 bg-gray-50 rounded-xl text-center">
        <div id="status-icon" class="mb-4">
            <!-- Spinner -->
            <svg class="animate-spin h-12 w-12 text-blue-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
        </div>
        <h2 id="payment-status" class="text-xl font-semibold text-gray-700">Processing your payment...</h2>
        <p class="mt-2 text-gray-500">This usually takes a few seconds. Please stay on this page.</p>
    </div>

    @if(isset($payment))
    <div class="mt-6 p-4 bg-gray-100 rounded-lg">
        <h3 class="font-medium text-gray-700 mb-2">Payment Debug Info:</h3>
        <pre class="text-sm text-gray-800">{{ json_encode($payment, JSON_PRETTY_PRINT) }}</pre>
    </div>
    @endif
</div>

<script>
const apiRef = "{{ $payment->api_ref ?? '' }}"; // pass from controller
if(apiRef) {
    async function checkPayment() {
        try {
            const res = await fetch(`/billing/payment-status/${apiRef}`);
            const data = await res.json();

            const statusEl = document.getElementById('payment-status');
            const iconEl = document.getElementById('status-icon');

            if (data.status === 'paid') {
                statusEl.innerHTML = "<span class='text-green-600 font-bold'>Payment successful! 🎉<br>Subscription unlocked.</span>";
                iconEl.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-green-600 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                `;
                // Optional: redirect to dashboard after 2 seconds
                setTimeout(() => window.location.href = '/dashboard', 2000);
            } else if (data.status === 'failed') {
                statusEl.innerHTML = "<span class='text-red-600 font-bold'>Payment failed. Please try again.</span>";
                iconEl.innerHTML = '';
            } else {
                // still pending, retry
                setTimeout(checkPayment, 3000);
            }
        } catch (e) {
            console.error('Payment check error:', e);
            setTimeout(checkPayment, 5000); // retry after delay if error
        }
    }

    checkPayment();
}
</script>

<style>
/* Optional: smooth background animation for SaaS vibe */
body {
    background: linear-gradient(135deg, #f0f4f8, #e2e8f0);
    min-height: 100vh;
}
</style>
@endsection
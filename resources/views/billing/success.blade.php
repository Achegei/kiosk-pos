@extends('layouts.admin')

@section('title','Subscription Billing')
@section('page-title','Subscription Billing')

@section('content')
<div class="max-w-3xl mx-auto mt-12 p-8 bg-white shadow-xl rounded-2xl text-center">
    <h1 class="text-3xl font-bold mb-4 text-gray-800">Subscription Payment</h1>

    @if($payment->status === 'paid')
        <div class="p-6 bg-green-50 rounded-xl">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-green-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <h2 class="text-xl font-semibold text-green-700">Payment successful! 🎉</h2>
            <p class="mt-2 text-gray-600">Your subscription is now active.</p>
        </div>
        <script>
            // Redirect to dashboard after 2 seconds
            setTimeout(() => window.location.href = '/dashboard', 2000);
        </script>
    @elseif($payment->status === 'failed')
        <div class="p-6 bg-red-50 rounded-xl">
            <h2 class="text-xl font-semibold text-red-600">Payment failed</h2>
            <p class="mt-2 text-gray-600">Please try again or contact support.</p>
        </div>
    @else
        <div class="p-6 bg-yellow-50 rounded-xl">
            <h2 class="text-xl font-semibold text-yellow-700">Payment pending...</h2>
            <p class="mt-2 text-gray-600">Your payment is being processed. Refresh the page if it doesn’t update automatically.</p>
        </div>
    @endif
</div>

<style>
body {
    background: linear-gradient(135deg, #f0f4f8, #e2e8f0);
    min-height: 100vh;
}
</style>
@endsection
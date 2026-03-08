@extends('layouts.admin')

@section('title','Subscription Billing')
@section('page-title','Subscription Billing')

@section('content')
<div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

    <h1 class="text-3xl font-bold text-gray-900 mb-6">
        POS Subscription
    </h1>

    @php
        $tenant = auth()->user()?->tenant;
        $latestPayment = $tenant
            ? \App\Models\Payment::where('tenant_id', $tenant->id)
                ->latest()
                ->first()
            : null;

        $pendingPayment = $latestPayment && $latestPayment->status === 'pending';
    @endphp

    <div class="bg-white shadow rounded-lg p-8 text-center">

        @if ($tenant && $tenant->isActive() && $tenant->expiry_date && $tenant->expiry_date->isFuture())
            <h2 class="text-2xl font-semibold text-green-600 mb-4">Subscription Active</h2>
            <p class="text-gray-700 mb-6">
                Your POS subscription is active until
                <strong>{{ $tenant->expiry_date?->format('d M Y') ?? 'N/A' }}</strong>.
            </p>
            <p class="text-sm text-gray-500">
                Thank you for staying subscribed! 💚
            </p>
        @else
            <h2 class="text-2xl font-bold mb-4 text-red-600">
                Subscription Expired or Suspended
            </h2>

            <p class="text-gray-600 mb-6">
                Your POS subscription has expired or your account has been suspended.
                Please renew to continue using the system.
            </p>

            <p class="text-lg mb-6">
                Plan expired on: <strong>{{ $tenant?->expiry_date?->format('d M Y') ?? 'N/A' }}</strong>
            </p>

            @if ($pendingPayment)
                <div class="mt-4 text-center bg-yellow-100 text-yellow-700 px-4 py-2 rounded-lg">
                    ⏳ Your payment is being processed. Please wait a few minutes.
                </div>
            @else
                <form method="POST" action="{{ route('tenant.paySaaS') }}">
                    @csrf
                    <button class="bg-indigo-600 text-white px-6 py-3 rounded hover:bg-indigo-700 w-full">
                        Pay Subscription via M-PESA
                    </button>
                </form>
            @endif
        @endif

    </div>
</div>
@endsection
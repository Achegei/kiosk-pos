@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto py-10 px-6">

    {{-- PAGE HEADER --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-semibold text-gray-800">New Customer</h1>
            <p class="text-gray-500 text-sm mt-1">
                Add a customer for POS transactions, invoices, and credit tracking.
            </p>
        </div>

        <a href="{{ route('customers.index') }}"
           class="px-4 py-2 rounded-lg border bg-white hover:bg-gray-50 text-gray-700 shadow-sm">
            ← Back
        </a>
    </div>

    {{-- ERROR ALERT --}}
    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm">
            <div class="font-semibold text-red-700 mb-1">Please fix the following:</div>
            <ul class="list-disc pl-6 text-red-600 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- FORM CARD --}}
    <form action="{{ route('customers.store') }}" method="POST"
          class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 space-y-6">
        @csrf

        {{-- NAME --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">
                Customer Name
            </label>
            <input type="text"
                   name="name"
                   value="{{ old('name') }}"
                   placeholder="John Doe"
                   required
                   class="w-full rounded-lg border-2 border-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 px-4 py-2">
        </div>

        {{-- PHONE --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">
                Phone Number
            </label>
            <input type="text"
                   name="phone"
                   value="{{ old('phone') }}"
                   placeholder="07XXXXXXXX"
                   required
                   class="w-full rounded-lg border-2 border-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 px-4 py-2">
        </div>

        {{-- EMAIL --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">
                Email <span class="text-gray-400">(optional)</span>
            </label>
            <input type="email"
                   name="email"
                   value="{{ old('email') }}"
                   placeholder="example@email.com"
                   class="w-full rounded-lg border-2 border-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 px-4 py-2">
        </div>

        {{-- CREDIT --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">
                Opening Credit (KSh)
            </label>
            <input type="number"
                   step="0.01"
                   name="credit"
                   value="{{ old('credit',0) }}"
                   class="w-full rounded-lg border-2 border-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 px-4 py-2">
            <p class="text-xs text-gray-400 mt-1">
                Used for customers allowed to buy on credit.
            </p>
        </div>

        {{-- ACTIONS --}}
        <div class="flex items-center justify-end gap-3 pt-4 border-t">
            <a href="{{ route('customers.index') }}"
               class="px-5 py-2 rounded-lg border bg-white hover:bg-gray-50 text-gray-700">
                Cancel
            </a>

            <button type="submit"
                    class="px-6 py-2 rounded-lg bg-indigo-600 text-white font-semibold
                           hover:bg-indigo-700 active:scale-[.98] transition shadow-sm">
                Save Customer
            </button>
        </div>

    </form>

</div>
@endsection

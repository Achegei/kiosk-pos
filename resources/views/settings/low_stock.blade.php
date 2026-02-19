@extends('layouts.admin')

@section('title', 'Low Stock Settings')

@section('content')
<div class="max-w-3xl mx-auto p-6 bg-white shadow rounded-lg">
    <h1 class="text-xl font-bold mb-4">Set Low Stock Threshold</h1>

    @if(session('success'))
        <div class="bg-green-100 text-green-800 p-2 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('settings.low_stock.update') }}" method="POST">
        @csrf
        <div class="mb-4">
            <label for="low_stock_threshold" class="block text-gray-700 mb-2">Low Stock Threshold</label>
            <input type="number" name="low_stock_threshold" id="low_stock_threshold"
                value="{{ $threshold }}"
                class="w-full border px-3 py-2 rounded" min="1">
        </div>
        <button type="submit"
            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Save
        </button>
    </form>
</div>
@endsection

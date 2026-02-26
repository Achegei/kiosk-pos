@extends('layouts.admin')
@section('title', 'Tenant Settings - Default Notes')

@section('content')
<div class="p-6 max-w-3xl mx-auto bg-white rounded shadow">
    <h1 class="text-2xl font-bold mb-6">Default Notes for Quotes</h1>

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('tenant.settings.default_notes.update') }}">
        @csrf

        <div class="mb-4">
            <label for="default_notes" class="block font-semibold mb-1">Default Notes</label>
            <textarea name="default_notes" id="default_notes" rows="8"
                      class="w-full border rounded p-2"
                      placeholder="Enter each note on a new line. They will be numbered automatically on quotes.">{{ old('default_notes', $tenant->default_notes) }}</textarea>
            <small class="text-gray-500">Each line will become a numbered item in your quotes.</small>
        </div>

        <button type="submit" class="bg-indigo-600 text-white py-2 px-4 rounded mt-2">
            Save Notes
        </button>
    </form>
</div>
@endsection
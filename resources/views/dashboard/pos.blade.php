@extends('layouts.staff') {{-- separate layout for staff --}}

@section('title', 'Staff Dashboard')

@section('content')
<div class="p-6 space-y-6">
    @include('dashboard.partials.pos')
</div>
@endsection

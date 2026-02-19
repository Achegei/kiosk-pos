@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<div class="p-6 space-y-6">
    {{-- POS Section --}}
    <!--@include('dashboard.partials.pos')-->

    {{-- Stats --}}
    @include('dashboard.partials.stats')

    {{-- Low Stock --}}
    @include('dashboard.partials.low-stock')

    {{-- Recent Transactions --}}
    @include('dashboard.partials.recent-transactions')
</div>

{{-- POS JS --}}
@include('dashboard.partials.pos-js')
@endsection

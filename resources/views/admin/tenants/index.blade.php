@extends('layouts.admin')

@section('content')

<div class="p-6">

<div class="flex justify-between mb-4">
    <h1 class="text-2xl font-bold">Tenants</h1>

    <a href="{{ route('admin.tenants.create') }}"
       class="bg-blue-600 text-white px-4 py-2 rounded">
       + Create Tenant
    </a>
</div>

<table class="w-full border">

<tr class="bg-gray-100">
<th class="p-2 text-left">Business</th>
<th>Phone</th>
<th>Status</th>
<th>Created</th>
</tr>

@foreach($tenants as $tenant)

<tr class="border-t">
<td class="p-2">{{ $tenant->name }}</td>
<td>{{ $tenant->phone }}</td>
<td>{{ $tenant->subscription_status }}</td>
<td>{{ $tenant->created_at->format('d M Y') }}</td>
</tr>

@endforeach

</table>

</div>

@endsection
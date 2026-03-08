@extends('layouts.admin')

@section('title', 'Suppliers')

@section('content')
<div class="container mx-auto py-6 max-w-5xl">
    <div class="mb-4 flex justify-between items-center">
        <h1 class="text-2xl font-bold">Suppliers</h1>
        <a href="{{ route('admin.suppliers.create') }}" class="bg-blue-500 text-white px-4 py-2 rounded">Add Supplier</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <table class="w-full border table-auto">
        <thead>
            <tr class="bg-gray-100">
                <th class="px-2 py-1">Name</th>
                <th class="px-2 py-1">Contact</th>
                <th class="px-2 py-1">Email</th>
                <th class="px-2 py-1">Address</th>
                <th class="px-2 py-1">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($suppliers as $supplier)
                <tr>
                    <td>{{ $supplier->name }}</td>
                    <td>{{ $supplier->contact }}</td>
                    <td>{{ $supplier->email }}</td>
                    <td>{{ $supplier->address }}</td>
                    <td>
                        <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="bg-yellow-500 text-white px-2 py-1 rounded">Edit</a>
                        <form action="{{ route('admin.suppliers.destroy', $supplier) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
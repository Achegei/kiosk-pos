@extends('layouts.admin')

@section('content')
<div class="container mx-auto p-4">

    <h1 class="text-2xl font-bold mb-4">Transactions</h1>
    {{-- ---------------- EXISTING TRANSACTIONS ---------------- --}}
    @php $role = auth()->user()->role; @endphp

        @if($role === 'super_admin')
        <a href="{{ route('transactions.create') }}"
        class="bg-blue-500 text-white px-4 py-2 rounded mb-4 inline-block">
        Add Transaction
        </a>
        @endif
    <table class="min-w-full bg-white border">
        <thead class="bg-gray-100">
            <tr>
                <th class="py-2 px-4 border">ID</th>
                <th class="py-2 px-4 border">Customer</th>
                <th class="py-2 px-4 border">Total</th>
                <th class="py-2 px-4 border">Date</th>
                <th class="py-2 px-4 border">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $transaction)
            <tr>
                <td class="py-2 px-4 border">{{ $transaction->id }}</td>
                <td class="py-2 px-4 border">{{ $transaction->customer?->name ?? 'Walk-in' }}</td>
                <td class="py-2 px-4 border">KSh {{ number_format($transaction->total_amount ?? 0, 2) }}</td>
                <td class="py-2 px-4 border">{{ $transaction->created_at->format('d-M-Y H:i') }}</td>
                <td class="py-2 px-4 border">
                    <a href="{{ route('transactions.edit', $transaction) }}" class="text-blue-600 mr-2">Edit</a>
                    <form action="{{ route('transactions.destroy', $transaction) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $transactions->links() }}
    </div>

</div>
@endsection

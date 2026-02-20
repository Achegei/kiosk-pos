@extends('layouts.admin')

@section('title', 'Customers')
@section('content')
<div class="overflow-x-auto">

    <table class="min-w-full bg-white border">

        <thead class="bg-gray-100">
            <tr>
                <th class="py-2 px-4 border text-left">Name</th>
                <th class="py-2 px-4 border text-left">Phone</th>
                <th class="py-2 px-4 border text-left">Email</th>
                <th class="py-2 px-4 border text-center">Credit (KSh)</th>
                <th class="py-2 px-4 border text-center">Actions</th>
            </tr>
        </thead>

        <tbody>

        @forelse($customers as $customer)

            <tr class="hover:bg-gray-50">

                <td class="py-2 px-4 border font-medium">
                    {{ $customer->name }}
                </td>

                <td class="py-2 px-4 border">
                    {{ $customer->phone }}
                </td>

                <td class="py-2 px-4 border">
                    {{ $customer->email ?? '-' }}
                </td>

                <td class="py-2 px-4 border text-center">
                    @if($customer->credit <= 0)
                        <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-sm font-semibold">
                            {{ number_format($customer->credit, 2) }}
                        </span>
                    @else
                        {{ number_format($customer->credit, 2) }}
                    @endif
                </td>

                <td class="py-2 px-4 border text-center whitespace-nowrap">

                    <a href="{{ route('customers.edit', $customer->id) }}"
                       class="text-blue-600 font-semibold mr-3">
                       Edit
                    </a>

                    <form action="{{ route('customers.destroy', $customer->id) }}"
                          method="POST"
                          class="inline">
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                                class="text-red-600 font-semibold"
                                onclick="return confirm('Delete this customer?')">
                            Delete
                        </button>
                    </form>

                </td>

            </tr>

        @empty

            <tr>
                <td colspan="5"
                    class="py-6 text-center text-gray-400 border">
                    No customers found
                </td>
            </tr>

        @endforelse

        </tbody>

    </table>

</div>
@endsection

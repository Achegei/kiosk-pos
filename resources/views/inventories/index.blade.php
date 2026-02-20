@extends('layouts.admin')

@section('title', 'Inventory')
@section('content')
<div class="overflow-x-auto">

<table class="min-w-full bg-white border">

    <thead class="bg-gray-100">
        <tr>
            <th class="py-2 px-4 border text-left">Product</th>
            <th class="py-2 px-4 border text-left">SKU / Barcode</th>
            <th class="py-2 px-4 border text-center">Quantity</th>
            <th class="py-2 px-4 border text-center">Price</th>
            <th class="py-2 px-4 border text-center">Status</th>
            <th class="py-2 px-4 border text-center">Actions</th>
        </tr>
    </thead>

    <tbody>

    @forelse($inventories as $inventory)

        <tr class="hover:bg-gray-50">

            <td class="py-2 px-4 border font-medium">
                {{ $inventory->product->name }}
            </td>

            <td class="py-2 px-4 border">
                {{ $inventory->product->sku ?? '-' }}
            </td>

            {{-- ⭐ SMART QUANTITY COLORS --}}
            <td class="py-2 px-4 border text-center">

                @if($inventory->quantity == 0)

                    <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-sm font-semibold">
                        OUT
                    </span>

                @elseif($inventory->quantity <= 3)

                    <span class="bg-red-100 text-red-600 px-2 py-1 rounded text-sm font-semibold">
                        {{ $inventory->quantity }}
                    </span>

                @elseif($inventory->quantity <= 10)

                    <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-sm font-semibold">
                        {{ $inventory->quantity }}
                    </span>

                @else

                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-sm font-semibold">
                        {{ $inventory->quantity }}
                    </span>

                @endif

            </td>

            <td class="py-2 px-4 border text-center">
                KSh {{ number_format($inventory->product->price, 2) }}
            </td>

            <td class="py-2 px-4 border text-center">

                @if($inventory->product->is_active)

                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-sm">
                        Active
                    </span>

                @else

                    <span class="bg-gray-200 text-gray-700 px-2 py-1 rounded text-sm">
                        Disabled
                    </span>

                @endif

            </td>

            <td class="py-2 px-4 border text-center whitespace-nowrap">

                <a href="{{ route('inventories.edit', $inventory->id) }}"
                   class="text-blue-600 font-semibold mr-3">
                   Edit
                </a>

                <form action="{{ route('inventories.toggle', $inventory->id) }}"
                      method="POST"
                      class="inline">
                    @csrf
                    @method('PATCH')

                    <button type="submit"
                            class="text-purple-600 font-semibold mr-3"
                            onclick="return confirm('Toggle product status?')">

                        {{ $inventory->product->is_active ? 'Disable' : 'Enable' }}

                    </button>
                </form>

                <form action="{{ route('inventories.destroy', $inventory->id) }}"
                      method="POST"
                      class="inline">
                    @csrf
                    @method('DELETE')

                    <button type="submit"
                            class="text-red-600 font-semibold"
                            onclick="return confirm('Delete this product?')">

                        Delete

                    </button>
                </form>

            </td>

        </tr>

    @empty

        <tr>
            <td colspan="6"
                class="py-6 text-center text-gray-400 border">

                No inventory found

            </td>
        </tr>

    @endforelse

    </tbody>

</table>

</div>

@endsection

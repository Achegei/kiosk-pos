@extends('layouts.admin')

@section('content')

<div class="max-w-6xl mx-auto mt-10 bg-white shadow-xl rounded-2xl border border-gray-200">

    {{-- HEADER --}}
    <div class="flex items-center justify-between px-8 py-6 border-b">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Staff Management</h1>
            <p class="text-sm text-gray-500">Manage POS users, roles and permissions</p>
        </div>

        <a href="{{ route('staff.create') }}"
           class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg shadow font-semibold transition">
            + Add Staff
        </a>
    </div>


    {{-- SUCCESS MESSAGE --}}
    @if(session('success'))
        <div class="mx-8 mt-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
            {{ session('success') }}
        </div>
    @endif


    {{-- TABLE --}}
    <div class="p-8 overflow-x-auto">

        <table class="w-full text-left border-collapse">

            <thead>
                <tr class="border-b text-sm text-gray-500 uppercase tracking-wider">
                    <th class="py-3">Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>POS Access</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y">

            @forelse($users as $user)

                <tr class="hover:bg-gray-50 transition">

                    {{-- NAME --}}
                    <td class="py-4 font-semibold text-gray-900">
                        {{ $user->name }}
                    </td>

                    {{-- EMAIL --}}
                    <td class="text-gray-600">
                        {{ $user->email }}
                    </td>

                    {{-- ROLE BADGE --}}
                    <td>

                        @php
                            $colors = [
                                'super_admin' => 'bg-purple-100 text-purple-700',
                                'admin' => 'bg-indigo-100 text-indigo-700',
                                'supervisor' => 'bg-blue-100 text-blue-700',
                                'staff' => 'bg-gray-100 text-gray-700',
                            ];
                        @endphp

                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $colors[$user->role] ?? 'bg-gray-100' }}">
                            {{ ucfirst(str_replace('_',' ',$user->role)) }}
                        </span>

                    </td>

                    {{-- POS ACCESS --}}
                    <td>
                        @if($user->can_pos)
                            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">
                                Enabled
                            </span>
                        @else
                            <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold">
                                Disabled
                            </span>
                        @endif
                    </td>

                    {{-- ACTIONS --}}
                    <td class="text-right space-x-2">

                        <a href="{{ route('staff.edit',$user->id) }}"
                           class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-semibold">
                            Edit
                        </a>

                        <form action="{{ route('staff.destroy',$user->id) }}"
                              method="POST"
                              class="inline-block"
                              onsubmit="return confirm('Delete this staff member?')">

                            @csrf
                            @method('DELETE')

                            <button
                                class="px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg text-sm font-semibold">
                                Delete
                            </button>

                        </form>

                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="5" class="py-12 text-center text-gray-400">
                        No staff created yet.
                    </td>
                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection

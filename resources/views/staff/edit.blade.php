@extends('layouts.admin')

@section('content')

<div class="max-w-3xl mx-auto mt-10 p-8 bg-white shadow-xl rounded-2xl border border-gray-200">

    <h1 class="text-2xl font-bold text-gray-900 mb-6">
        Edit Staff Member
    </h1>

    <form action="{{ route('staff.update',$user->id) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- NAME --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Full Name
            </label>

            <input type="text"
                   name="name"
                   value="{{ old('name',$user->name) }}"
                   required
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
        </div>


        {{-- EMAIL --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Email
            </label>

            <input type="email"
                   name="email"
                   value="{{ old('email',$user->email) }}"
                   required
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
        </div>


        {{-- ROLE --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Role
            </label>

            <select name="role"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg">

                @foreach($roles as $role)

                    <option value="{{ $role }}"
                        {{ $user->role == $role ? 'selected':'' }}>

                        {{ ucfirst(str_replace('_',' ',$role)) }}

                    </option>

                @endforeach

            </select>
        </div>


        {{-- PASSWORD OPTIONAL --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                New Password (optional)
            </label>

            <input type="password"
                   name="password"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg">
        </div>


        {{-- CONFIRM --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Confirm Password
            </label>

            <input type="password"
                   name="password_confirmation"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg">
        </div>


        {{-- POS ACCESS --}}
        <div class="flex items-center space-x-3">

            <input type="checkbox"
                   name="can_pos"
                   value="1"
                   {{ $user->can_pos ? 'checked':'' }}
                   class="h-5 w-5 text-indigo-600">

            <label class="text-sm text-gray-700">

                Allow POS Access (Can Sell)

            </label>

        </div>


        {{-- SAVE --}}
        <button
            class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow transition">

            Update Staff

        </button>

    </form>

</div>

@endsection

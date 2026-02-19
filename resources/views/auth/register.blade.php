@extends('layouts.admin')

@section('content')
<div class="flex justify-center items-center h-screen">
  <div class="bg-white shadow-md p-8 rounded w-full max-w-md">
    <h2 class="text-2xl font-bold mb-6 text-center">Create User</h2>

    <form action="{{ route('register.post') }}" method="POST">
      @csrf
      <input type="text" name="name" placeholder="Full Name" class="w-full p-2 border mb-4 rounded" required>
      <input type="email" name="email" placeholder="Email" class="w-full p-2 border mb-4 rounded" required>
      <input type="password" name="password" placeholder="Password" class="w-full p-2 border mb-4 rounded" required>
      <input type="password" name="password_confirmation" placeholder="Confirm Password" class="w-full p-2 border mb-4 rounded" required>
      <select name="role" class="w-full p-2 border mb-4 rounded">
        <option value="staff">Staff</option>
        <option value="supervisor">Supervisor</option>
        <option value="admin">Admin</option>
        <option value="super_admin">Super Admin</option>
      </select>
      <button class="w-full bg-green-500 text-white py-2 rounded hover:bg-green-600">Create User</button>
    </form>
  </div>
</div>
@endsection

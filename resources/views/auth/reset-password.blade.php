@extends('layouts.admin')
@section('content')
<div class="flex justify-center items-center h-screen">
  <div class="bg-white shadow-md p-8 rounded w-full max-w-md">
    <h2 class="text-2xl font-bold mb-6 text-center">Reset Password</h2>

    <form action="{{ route('password.update') }}" method="POST">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">
      <input type="email" name="email" placeholder="Your email" class="w-full p-2 border mb-4 rounded" required>
      <input type="password" name="password" placeholder="New Password" class="w-full p-2 border mb-4 rounded" required>
      <input type="password" name="password_confirmation" placeholder="Confirm Password" class="w-full p-2 border mb-4 rounded" required>
      <button class="w-full bg-green-500 text-white py-2 rounded hover:bg-green-600">Reset Password</button>
    </form>
  </div>
</div>
@endsection

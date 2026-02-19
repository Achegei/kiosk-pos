@extends('layouts.admin')
@section('content')
<div class="flex justify-center items-center h-screen">
  <div class="bg-white shadow-md p-8 rounded w-full max-w-md">
    <h2 class="text-2xl font-bold mb-6 text-center">Forgot Password</h2>

    <form action="{{ route('password.email') }}" method="POST">
      @csrf
      <input type="email" name="email" placeholder="Your email" class="w-full p-2 border mb-4 rounded" required>
      <button class="w-full bg-yellow-500 text-white py-2 rounded hover:bg-yellow-600">Send Reset Link</button>
    </form>
  </div>
</div>
@endsection

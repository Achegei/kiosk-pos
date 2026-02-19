<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    // Show login page
    public function showLogin() {
        return view('auth.login');
    }

    // Handle login
    public function login(Request $request) {
        $request->validate([
            'email'=>'required|email',
            'password'=>'required',
        ]);

        if (Auth::attempt($request->only('email','password'))) {
            $request->session()->regenerate();

            // Redirect by role
            $role = Auth::user()->role;
            return match($role) {
                'super_admin' => redirect()->route('dashboard'),
                'admin' => redirect()->route('dashboard'),
                'supervisor' => redirect()->route('dashboard'),
                'staff' => redirect()->route('dashboard'),
                default => redirect('/'),
            };
        }

        return back()->withErrors(['email'=>'Invalid credentials'])->withInput();
    }

    // Show registration (admin/staff can create new users)
    public function showRegister() {
        return view('auth.register');
    }

    public function register(Request $request) {
        $request->validate([
            'name'=>'required|string|max:255',
            'email'=>'required|email|unique:users,email',
            'password'=>'required|string|min:6|confirmed',
            'role'=>'required|string',
        ]);

        User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>Hash::make($request->password),
            'role'=>$request->role,
        ]);

        return redirect()->route('login')->with('success','User created successfully.');
    }

    // Logout
    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    // -------------------------------
    // Password reset / forgot password
    // -------------------------------
    public function showForgotPassword() {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request) {
        $request->validate(['email'=>'required|email']);
        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status'=>$status])
            : back()->withErrors(['email'=>__($status)]);
    }

    public function showResetForm($token) {
        return view('auth.reset-password', ['token'=>$token]);
    }

    public function resetPassword(Request $request) {
        $request->validate([
            'token'=>'required',
            'email'=>'required|email',
            'password'=>'required|string|min:6|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email','password','password_confirmation','token'),
            function($user,$password) {
                $user->forceFill([
                    'password'=>Hash::make($password)
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success','Password reset successful.')
            : back()->withErrors(['email'=>[__($status)]]);
    }
}

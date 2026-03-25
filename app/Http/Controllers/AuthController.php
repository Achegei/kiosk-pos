<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // ---------------------------
    // LOGIN
    // ---------------------------
    public function showLogin()
    {
        try {
            return view('auth.login');
        } catch (\Throwable $e) {
            \Log::error('Failed to load login page', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors('Unable to load login page. Please try again later.');
        }
    }

    public function login(Request $request)
        {
            try {
                $request->validate([
                    'email' => 'required|email',
                    'password' => 'required|string',
                ]);

                // Optional: throttle login attempts
                if (method_exists($this, 'hasTooManyLoginAttempts') && $this->hasTooManyLoginAttempts($request)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'email' => ['Too many login attempts. Please try again later.'],
                    ]);
                }

                if (Auth::attempt($request->only('email', 'password'))) {
                    $request->session()->regenerate();

                    // Redirect based on role
                    return match (Auth::user()->role) {
                        'super_admin' => redirect()->route('superadmin.dashboard'),
                        'admin', 'supervisor', 'staff' => redirect()->route('dashboard'),
                        default => redirect('/'),
                    };
                }

                // Increment failed attempts if throttling is enabled
                if (method_exists($this, 'incrementLoginAttempts')) {
                    $this->incrementLoginAttempts($request);
                }

                return back()->withErrors(['email' => 'Invalid credentials'])->withInput();

            } catch (\Illuminate\Validation\ValidationException $e) {
                return back()->withErrors($e->errors())->withInput();
            } catch (\Throwable $e) {
                \Log::error('Login failed', [
                    'email' => $request->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return back()->withErrors(['email' => 'Login failed. Please try again later.'])->withInput();
            }
        }
    // ---------------------------
    // REGISTRATION
    // ---------------------------
    public function showRegister()
    {
        try {
            return view('auth.register');
        } catch (\Throwable $e) {
            \Log::error('Failed to load registration page', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors('Unable to load registration page.');
        }
    }

    public function register(Request $request)
        {
            try {
                $request->validate([
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:users,email',
                    'password' => 'required|string|min:6|confirmed',
                    'role' => 'required|string',
                ]);

                User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'role' => $request->role,
                    'password' => Hash::make($request->password),
                ]);

                return redirect()->route('login')->with('success', 'User created successfully.');

            } catch (\Illuminate\Validation\ValidationException $e) {
                return back()->withErrors($e->errors())->withInput();
            } catch (\Throwable $e) {
                \Log::error('Registration failed', [
                    'request' => $request->all(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return back()->withErrors('Unable to register. Please try again later.')->withInput();
            }
        }
    // ---------------------------
    // LOGOUT
    // ---------------------------
    public function logout(Request $request)
        {
            try {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login');

            } catch (\Throwable $e) {
                \Log::error('Logout failed', [
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return back()->withErrors('Unable to logout. Please try again.');
            }
        }

    // ---------------------------
    // PASSWORD RESET
    // ---------------------------
    public function showForgotPassword()
        {
            try {
                return view('auth.forgot-password');
            } catch (\Throwable $e) {
                \Log::error('Failed to load forgot password page', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return back()->withErrors('Unable to load forgot password page.');
            }
        }

    public function sendResetLink(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email']);

            $status = Password::sendResetLink($request->only('email'));

            return $status === Password::RESET_LINK_SENT
                ? back()->with(['status' => __($status)])
                : back()->withErrors(['email' => __($status)]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Throwable $e) {
            \Log::error('Failed to send password reset link', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors('Unable to send reset link. Please try again later.');
        }
    }

    public function showResetForm($token)
    {
        try {
            return view('auth.reset-password', ['token' => $token]);
        } catch (\Throwable $e) {
            \Log::error('Failed to load password reset form', [
                'token' => $token,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors('Unable to load password reset form.');
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|string|min:6|confirmed',
            ]);

            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password) {
                    $user->forceFill([
                        'password' => Hash::make($password),
                        'remember_token' => Str::random(60),
                    ])->save();
                }
            );

            return $status === Password::PASSWORD_RESET
                ? redirect()->route('login')->with('success', 'Password reset successful.')
                : back()->withErrors(['email' => [__($status)]]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Throwable $e) {
            \Log::error('Password reset failed', [
                'email' => $request->email,
                'token' => $request->token,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors('Unable to reset password. Please try again later.');
        }
    }
    public function adminResetPassword(Request $request)
        {
            $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = auth()->user();
            $user->password = bcrypt($request->password);
            $user->must_reset_password = false;
            $user->save();

            return redirect()->route('dashboard')
                ->with('success', 'Password updated successfully!');
        }
}
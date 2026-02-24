<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AdminLoginController extends Controller
{
    /**
     * Show the admin login form.
     * If already authenticated, redirect to dashboard.
     */
    public function show(Request $request)
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->intended(route('admin.analytics'));
        }

        return view('admin.login');
    }

    /**
     * Handle admin login.
     */
    public function login(Request $request)
    {
        Log::info('Login form submitted', [
            'method' => $request->method(),
            'has_email' => $request->has('email'),
            'email' => $request->input('email'),
        ]);

        // Rate limiting: 5 attempts per 5 minutes per IP
        $key = 'admin-login:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => [
                    'Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.'
                ],
            ]);
        }

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'remember' => 'nullable|boolean',
        ]);

        $remember = $request->boolean('remember', false);

        if (Auth::guard('admin')->attempt(
            [
                'email' => $credentials['email'],
                'password' => $credentials['password'],
                'is_active' => true,
            ],
            $remember
        )) {
            $user = Auth::guard('admin')->user();

            // Verify user can access admin (active + admin/teacher role)
            if (!$user->is_active || !$user->canAccessAdmin()) {
                Auth::guard('admin')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                Log::warning('Admin login failed: Insufficient permissions', [
                    'email' => $credentials['email'],
                    'user_id' => $user->id,
                    'role' => $user->role,
                    'ip' => $request->ip(),
                ]);
                RateLimiter::hit($key, 300);

                return redirect()
                    ->route('admin.login.show')
                    ->with('error', 'Invalid credentials or insufficient permissions.');
            }

            Log::info('Admin login successful', [
                'email' => $credentials['email'],
                'user_id' => $user->id,
                'role' => $user->role,
                'ip' => $request->ip(),
            ]);

            $request->session()->regenerate();
            RateLimiter::clear($key);

            return redirect()->intended(route('admin.analytics'));
        }

        Log::warning('Admin login failed: Invalid credentials', [
            'email' => $credentials['email'],
            'ip' => $request->ip(),
        ]);
        RateLimiter::hit($key, 300);

        return redirect()
            ->route('admin.login.show')
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Invalid credentials.']);
    }

    /**
     * Handle admin logout.
     */
    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'redirect' => route('admin.login.show')]);
        }

        return redirect()->route('admin.login.show');
    }
}

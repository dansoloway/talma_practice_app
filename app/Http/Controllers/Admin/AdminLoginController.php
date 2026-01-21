<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AdminLoginController extends Controller
{
    /**
     * Show the admin login form.
     */
    public function show()
    {
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
            'has_password' => $request->has('password'),
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

        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            Log::warning('Login validation failed', [
                'errors' => $e->errors(),
            ]);
            return redirect()
                ->route('admin.login.show')
                ->withErrors($e->errors())
                ->withInput();
        }

        // Find user by email
        $user = User::where('email', $credentials['email'])->first();

        // Log login attempt
        Log::info('Admin login attempt', [
            'email' => $credentials['email'],
            'ip' => $request->ip(),
            'user_exists' => $user !== null,
            'user_id' => $user?->id,
            'user_active' => $user?->is_active,
            'user_role' => $user?->role,
        ]);

        // Check if user exists, is active, and has admin/teacher role
        if (!$user) {
            Log::warning('Admin login failed: User not found', [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
            ]);
            RateLimiter::hit($key, 300); // 5 minutes
            return redirect()
                ->route('admin.login.show')
                ->with('error', 'Invalid credentials or insufficient permissions.');
        }

        if (!$user->is_active) {
            Log::warning('Admin login failed: User inactive', [
                'email' => $credentials['email'],
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);
            RateLimiter::hit($key, 300); // 5 minutes
            return redirect()
                ->route('admin.login.show')
                ->with('error', 'Invalid credentials or insufficient permissions.');
        }

        if (!$user->canAccessAdmin()) {
            Log::warning('Admin login failed: Insufficient permissions', [
                'email' => $credentials['email'],
                'user_id' => $user->id,
                'role' => $user->role,
                'ip' => $request->ip(),
            ]);
            RateLimiter::hit($key, 300); // 5 minutes
            return redirect()
                ->route('admin.login.show')
                ->with('error', 'Invalid credentials or insufficient permissions.');
        }

        // Verify password
        $passwordMatches = Hash::check($credentials['password'], $user->password);
        Log::info('Password verification', [
            'email' => $credentials['email'],
            'password_provided_length' => strlen($credentials['password']),
            'password_matches' => $passwordMatches,
            'stored_hash' => substr($user->password, 0, 20) . '...',
        ]);
        
        if (!$passwordMatches) {
            Log::warning('Admin login failed: Invalid password', [
                'email' => $credentials['email'],
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);
            RateLimiter::hit($key, 300); // 5 minutes
            return redirect()
                ->route('admin.login.show')
                ->with('error', 'Invalid credentials.');
        }

        Log::info('Admin login successful', [
            'email' => $credentials['email'],
            'user_id' => $user->id,
            'role' => $user->role,
            'ip' => $request->ip(),
        ]);

        // Regenerate session to prevent fixation
        $request->session()->regenerate();
        
        // Store user info in session
        session([
            'admin_authenticated' => true,
            'admin_user_id' => $user->id,
            'admin_user_name' => $user->name,
            'admin_user_role' => $user->role,
        ]);
        
        RateLimiter::clear($key);
        
        return redirect()->route('admin.analytics');
    }

    /**
     * Handle admin logout.
     */
    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return response()->json(['success' => true]);
    }
}


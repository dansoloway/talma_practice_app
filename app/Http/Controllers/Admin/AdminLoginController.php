<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminLoginController extends Controller
{
    /**
     * Show the admin login form.
     * If already authenticated, redirect to dashboard.
     */
    public function show(Request $request)
    {
        // If already authenticated, redirect to dashboard
        if (session('admin_authenticated', false)) {
            return redirect()->route('admin.analytics');
        }
        
        // Check for remember token cookie
        $rememberToken = $request->cookie('admin_remember_token');
        if ($rememberToken) {
            $user = User::where('remember_token', hash('sha256', $rememberToken))
                ->where('is_active', true)
                ->first();
            
            if ($user && $user->canAccessAdmin()) {
                // Auto-login with remember token
                $request->session()->regenerate();
                session([
                    'admin_authenticated' => true,
                    'admin_user_id' => $user->id,
                    'admin_user_name' => $user->name,
                    'admin_user_role' => $user->role,
                ]);
                
                Log::info('Auto-login via remember token', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
                
                return redirect()->route('admin.analytics');
            }
            // Invalid token - cookie will be cleared by middleware if needed
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
                'remember' => 'nullable|boolean',
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
        
        // Handle "Remember Me" functionality
        $remember = $request->boolean('remember', false);
        $response = redirect()->route('admin.analytics');
        
        if ($remember) {
            // Generate a secure remember token
            $rememberToken = Str::random(60);
            $user->remember_token = hash('sha256', $rememberToken);
            $user->save();
            
            // Set cookie for 30 days (works on mobile too)
            // HttpOnly and Secure flags for security, SameSite=Lax for mobile compatibility
            $response->withCookie(cookie(
                'admin_remember_token',
                $rememberToken,
                60 * 24 * 30, // 30 days
                '/',
                null,
                config('session.secure', false), // Use secure flag from config
                true, // HttpOnly
                false, // raw
                'lax' // SameSite for mobile compatibility
            ));
            
            Log::info('Remember me token set', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        } else {
            // Clear any existing remember token
            if ($user->remember_token) {
                $user->remember_token = null;
                $user->save();
            }
            // Clear cookie if it exists
            $response->withCookie(Cookie::forget('admin_remember_token'));
        }
        
        RateLimiter::clear($key);
        
        return $response;
    }

    /**
     * Handle admin logout.
     */
    public function logout(Request $request)
    {
        // Clear remember token from database
        $userId = session('admin_user_id');
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                $user->remember_token = null;
                $user->save();
            }
        }
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        $response = response()->json(['success' => true]);
        
        // Clear remember token cookie
        $response->withCookie(Cookie::forget('admin_remember_token'));
        
        return $response;
    }
}


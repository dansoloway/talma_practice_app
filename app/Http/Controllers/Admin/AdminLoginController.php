<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
        ]);

        // Find user by email
        $user = User::where('email', $credentials['email'])->first();

        // Check if user exists, is active, and has admin/teacher role
        if (!$user || !$user->is_active || !$user->canAccessAdmin()) {
            RateLimiter::hit($key, 300); // 5 minutes
            return redirect()
                ->route('admin.dashboard')
                ->with('error', 'Invalid credentials or insufficient permissions.');
        }

        // Verify password
        if (!Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($key, 300); // 5 minutes
            return redirect()
                ->route('admin.dashboard')
                ->with('error', 'Invalid credentials.');
        }

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


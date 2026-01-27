<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if admin is already authenticated via session
        if (session('admin_authenticated')) {
            // Verify user still exists and is active
            $userId = session('admin_user_id');
            if ($userId) {
                $user = User::find($userId);
                if ($user && $user->is_active && $user->canAccessAdmin()) {
                    return $next($request);
                }
            }
            
            // User no longer valid, clear session
            session()->forget(['admin_authenticated', 'admin_user_id', 'admin_user_name', 'admin_user_role']);
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
                
                return $next($request);
            } else {
                // Invalid token - will be cleared on next response
            }
        }

        // Show login form for unauthenticated users
        return response()->view('admin.login');
    }
}

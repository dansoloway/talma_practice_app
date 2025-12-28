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
        // Check if admin is already authenticated
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

        // Show login form for unauthenticated users
        return response()->view('admin.login');
    }
}
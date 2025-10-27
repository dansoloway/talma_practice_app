<?php

namespace App\Http\Middleware;

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
            return $next($request);
        }

        // Show login form for unauthenticated users
        return response()->view('admin.login');
    }
}
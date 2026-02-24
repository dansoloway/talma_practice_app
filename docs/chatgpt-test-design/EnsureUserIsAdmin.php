<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Only users with role 'admin' can access user management.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('admin')->user();

        if (!$user || $user->role !== 'admin') {
            abort(403, 'Only administrators can manage users.');
        }

        return $next($request);
    }
}

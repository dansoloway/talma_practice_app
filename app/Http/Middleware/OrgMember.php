<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class OrgMember
{
    /**
     * Require org membership (or global admin) for admin routes.
     * Global admins (role=admin) can access any org.
     * Others must have organization_user membership with role org_admin or teacher.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('admin')->user();
        $org = $request->attributes->get('currentOrganization');

        if (!$user || !$org) {
            return $this->unauthorized($request);
        }

        // Global admins can access any org
        if ($user->role === 'admin') {
            return $next($request);
        }

        // Check org membership with admin-eligible role
        $membership = $org->users()
            ->where('users.id', $user->id)
            ->whereIn('organization_user.role', ['org_admin', 'teacher'])
            ->first();

        if (!$membership) {
            return $this->unauthorized($request);
        }

        return $next($request);
    }

    private function unauthorized(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        return abort(403, 'You do not have access to this organization.');
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StudentOrgAccess
{
    /**
     * For /o/{org}/... student routes: enforce access_mode.
     * - open: allow guests; org-wide courses visible.
     * - restricted: require login + org membership.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $org = $request->route('organization');

        if (!$org instanceof Organization) {
            return abort(404);
        }

        if (!$org->is_active) {
            return abort(404);
        }

        if ($org->access_mode !== 'restricted') {
            return $next($request);
        }

        // Restricted: require authentication (admin guard - same login as teachers/org admins)
        if (!Auth::guard('admin')->check()) {
            return redirect()->guest(route('admin.login.show'));
        }

        $user = Auth::guard('admin')->user();

        // Must be member of org (any role in organization_user)
        if (!$user->isMemberOfOrg($org->id)) {
            return abort(403, 'You do not have access to this organization.');
        }

        return $next($request);
    }
}

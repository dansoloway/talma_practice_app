<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrgContext
{
    /**
     * Load the organization from the route and ensure it is active.
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

        $request->attributes->set('currentOrganization', $org);
        // Only share when in org context (this middleware runs only on o/{org}/admin/* routes)
        view()->share('currentOrganization', $org);

        return $next($request);
    }
}

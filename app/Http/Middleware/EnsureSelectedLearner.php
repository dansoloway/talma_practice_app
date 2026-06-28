<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Services\LearnerSessionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSelectedLearner
{
    public function __construct(
        protected LearnerSessionService $learnerSession,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('org.student.select-child', 'org.student.select-child.submit')) {
            return $next($request);
        }

        $org = $request->route('organization');

        if (! $org instanceof Organization || ! $org->usesParentSignup()) {
            return $next($request);
        }

        $user = Auth::guard('admin')->user();

        if (! $user || ! $user->isParent()) {
            return $next($request);
        }

        if ($this->learnerSession->requiresChildSelection($user)) {
            return redirect()->route('org.student.select-child', $org);
        }

        return $next($request);
    }
}

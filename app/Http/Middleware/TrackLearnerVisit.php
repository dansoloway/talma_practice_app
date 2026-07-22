<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Services\LearnerVisitTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackLearnerVisit
{
    public function __construct(
        protected LearnerVisitTracker $tracker,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->route('organization');

        if ($organization instanceof Organization) {
            $this->tracker->touch($organization);
        }

        return $next($request);
    }
}

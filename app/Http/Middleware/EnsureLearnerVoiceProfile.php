<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Services\LearnerVoiceProfileCompletion;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureLearnerVoiceProfile
{
    public function __construct(
        protected LearnerVoiceProfileCompletion $profileCompletion,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs(
            'org.student.complete-voice-profile',
            'org.student.complete-voice-profile.submit',
            'org.student.select-child',
            'org.student.select-child.submit',
        )) {
            return $next($request);
        }

        $org = $request->route('organization');

        if (! $org instanceof Organization || ! $org->collectsVoiceRecordings()) {
            return $next($request);
        }

        $user = Auth::guard('admin')->user();

        if (! $user || ! $this->profileCompletion->requiresCompletion($user, $org)) {
            return $next($request);
        }

        return redirect()->route('org.student.complete-voice-profile', $org);
    }
}

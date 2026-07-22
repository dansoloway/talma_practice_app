<?php

namespace App\Http\Controllers;

use App\Models\ActivityEvent;
use App\Models\LearnerVisit;
use App\Models\Organization;
use App\Services\Geolocation\IpGeolocationService;
use App\Services\LearnerVisitTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ActivityEventController extends Controller
{
    public function __construct(
        protected LearnerVisitTracker $visitTracker,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lesson_id' => 'nullable|exists:lessons,id',
            'activity_type' => ['required', 'string', 'max:50'],
            'activity_id' => 'nullable|integer',
            'status' => ['required', 'string', Rule::in(['started', 'completed'])],
            'meta' => 'nullable|array',
        ]);

        $ipAddress = $request->ip();
        $geolocationService = app(IpGeolocationService::class);
        $location = $geolocationService->getLocationFromIp($ipAddress);

        $event = ActivityEvent::create([
            'session_id' => $request->attributes->get('practice_learner_scope'),
            'device_type' => $this->detectDeviceType($request),
            'ip_address' => $ipAddress,
            'country' => $location['country'],
            'city' => $location['city'],
            'region' => $location['region'],
            'lesson_id' => $data['lesson_id'] ?? null,
            'activity_type' => $data['activity_type'],
            'activity_id' => $data['activity_id'] ?? null,
            'status' => $data['status'],
            'meta' => $data['meta'] ?? null,
        ]);

        $this->touchVisitFromRequest($data['lesson_id'] ?? null);

        return response()->json([
            'success' => true,
            'event_id' => $event->id,
        ]);
    }

    protected function touchVisitFromRequest(?int $lessonId): void
    {
        $user = Auth::guard('admin')->user();
        if (! $user) {
            return;
        }

        $visitId = session(LearnerVisitTracker::SESSION_VISIT_KEY);
        if (! $visitId) {
            return;
        }

        $visit = LearnerVisit::query()
            ->where('id', $visitId)
            ->where('user_id', $user->id)
            ->whereNull('ended_at')
            ->first();

        if (! $visit) {
            return;
        }

        $organization = Organization::find($visit->organization_id);
        if ($organization) {
            $this->visitTracker->touch($organization, $lessonId);
        }
    }

    /**
     * Detect device type from user agent.
     */
    private function detectDeviceType(Request $request): string
    {
        $userAgent = $request->userAgent() ?? '';
        $userAgent = strtolower($userAgent);

        // Check for mobile devices
        $mobilePatterns = [
            'mobile', 'android', 'iphone', 'ipod', 'ipad', 'blackberry',
            'windows phone', 'opera mini', 'iemobile', 'palm', 'kindle',
            'tablet', 'phone'
        ];

        foreach ($mobilePatterns as $pattern) {
            if (str_contains($userAgent, $pattern)) {
                return 'mobile';
            }
        }

        return 'desktop';
    }
}

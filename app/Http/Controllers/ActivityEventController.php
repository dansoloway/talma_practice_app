<?php

namespace App\Http\Controllers;

use App\Models\ActivityEvent;
use App\Services\Geolocation\IpGeolocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityEventController extends Controller
{
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

        return response()->json([
            'success' => true,
            'event_id' => $event->id,
        ]);
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

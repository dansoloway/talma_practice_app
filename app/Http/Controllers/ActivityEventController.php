<?php

namespace App\Http\Controllers;

use App\Models\ActivityEvent;
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

        $event = ActivityEvent::create([
            'session_id' => $request->attributes->get('practice_session_id'),
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
}

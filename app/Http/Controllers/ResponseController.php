<?php

namespace App\Http\Controllers;

use App\Models\Response;
use App\Services\Geolocation\IpGeolocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ResponseController extends Controller
{
    /**
     * Store a student response.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lesson_id' => 'required|exists:lessons,id',
            'prompt_id' => 'required|exists:prompts,id',
            'option_id' => 'required|exists:options,id',
            'generated_sentence' => 'required|string|max:255',
            'recording' => 'nullable|file|mimes:webm,mp3,wav,ogg,m4a|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        $recordingPath = null;

        // Only save recording if uploads are enabled
        if ($request->hasFile('recording') && config('app.allow_recording_upload', false)) {
            $file = $request->file('recording');
            
            // Additional security: validate file content matches declared type
            $allowedMimes = ['audio/webm', 'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/m4a'];
            if (!\App\Services\FileUploadSecurity::validateFileContent($file, $allowedMimes)) {
                return response()->json([
                    'error' => 'Invalid file type. File content does not match declared type.'
                ], 422);
            }
            
            $userId = auth()->id() ?? 'guest';
            // Use secure filename generation to prevent directory traversal
            $filename = \App\Services\FileUploadSecurity::generateSecureFilename($file, 'rec_' . $request->prompt_id);

            $recordingPath = $file->storeAs(
                "recordings/{$userId}",
                $filename,
                config('filesystems.recording_disk', 'local')
            );
        }

        $ipAddress = $request->ip();
        $geolocationService = app(IpGeolocationService::class);
        $location = $geolocationService->getLocationFromIp($ipAddress);

        $response = Response::create([
            'user_id' => auth()->id(),
            'parent_student_id' => session('selected_student_id'),
            'session_id' => $request->attributes->get('practice_session_id'),
            'device_type' => $this->detectDeviceType($request),
            'ip_address' => $ipAddress,
            'country' => $location['country'],
            'city' => $location['city'],
            'region' => $location['region'],
            'lesson_id' => $request->lesson_id,
            'prompt_id' => $request->prompt_id,
            'option_id' => $request->option_id,
            'generated_sentence' => $request->generated_sentence,
            'recording_path' => $recordingPath,
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'response_id' => $response->id,
            'message' => 'Response saved successfully',
        ], 201);
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


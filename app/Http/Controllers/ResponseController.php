<?php

namespace App\Http\Controllers;

use App\Models\Response;
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
            $userId = auth()->id() ?? 'guest';
            $timestamp = now()->format('Ymd_His');
            
            $filename = sprintf(
                'p%d_%s.%s',
                $request->prompt_id,
                $timestamp,
                $file->getClientOriginalExtension()
            );

            $recordingPath = $file->storeAs(
                "recordings/{$userId}",
                $filename,
                config('filesystems.recording_disk', 'local')
            );
        }

        $response = Response::create([
            'user_id' => auth()->id(),
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
}


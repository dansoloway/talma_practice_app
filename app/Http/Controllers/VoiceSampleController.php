<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\CourseAccess;
use App\Services\FileUploadSecurity;
use App\Services\VoiceSampleLearnerProfile;
use App\Services\VoiceSampleStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VoiceSampleController extends Controller
{
    public function store(Request $request, VoiceSampleStorage $storage): JsonResponse
    {
        if (! config('app.allow_recording_upload', false)) {
            return response()->json(['error' => 'Recording uploads are disabled.'], 403);
        }

        $user = auth('admin')->user();
        if (! $user || ! $user->canAccessStudentPortal()) {
            return response()->json(['error' => 'Authentication required.'], 401);
        }

        $profile = VoiceSampleLearnerProfile::resolve($user);
        if (! $profile) {
            return response()->json(['error' => 'Voice recording consent and learner profile required.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'organization_id' => 'required|exists:organizations,id',
            'lesson_id' => 'required|exists:lessons,id',
            'prompt_id' => 'required|exists:prompts,id',
            'option_id' => 'required|exists:options,id',
            'generated_sentence' => 'required|string|max:255',
            'recording' => 'required|file|mimes:webm,mp3,wav,ogg,m4a|max:10240',
            'duration_ms' => 'nullable|integer|min:0|max:120000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $organization = Organization::findOrFail($request->integer('organization_id'));

        if (! $organization->retain_voice_recordings) {
            return response()->json(['error' => 'Voice recording collection is not enabled for this organization.'], 403);
        }

        if (! $user->isAdmin() && ! $user->isMemberOfOrg($organization->id)) {
            return response()->json(['error' => 'You do not have access to this organization.'], 403);
        }

        $courseAccess = app(CourseAccess::class);
        $lesson = \App\Models\Lesson::findOrFail($request->integer('lesson_id'));
        $lesson->loadMissing('course');
        if (! $courseAccess->canAccessLesson($user, $lesson, $organization)) {
            return response()->json(['error' => 'You do not have access to this lesson.'], 403);
        }

        $file = $request->file('recording');
        $allowedMimes = ['audio/webm', 'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/m4a', 'audio/x-wav', 'video/webm'];
        if (! FileUploadSecurity::validateFileContent($file, $allowedMimes)) {
            return response()->json(['error' => 'Invalid file type.'], 422);
        }

        $sample = $storage->store(
            file: $file,
            organization: $organization,
            lessonId: $request->integer('lesson_id'),
            promptId: $request->integer('prompt_id'),
            optionId: $request->integer('option_id'),
            targetText: $request->string('generated_sentence')->toString(),
            age: $profile->age,
            gender: $profile->gender,
            nativeLanguage: $profile->nativeLanguage,
            durationMs: $request->integer('duration_ms') ?: null,
        );

        return response()->json([
            'success' => true,
            'sample_id' => $sample->id,
        ], 201);
    }
}

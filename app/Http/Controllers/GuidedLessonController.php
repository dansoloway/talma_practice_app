<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\LessonFlowStep;
use App\Http\Controllers\Concerns\GuardsRestrictedCourseAccess;
use App\Http\Controllers\Concerns\ProvidesGuidedFlowContext;
use App\Models\Lesson;
use App\Models\Organization;
use App\Services\CourseAccess;
use App\Services\LessonFlowService;
use App\Services\VoiceSampleLearnerProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GuidedLessonController extends Controller
{
    use GuardsRestrictedCourseAccess;
    use ProvidesGuidedFlowContext;

    public function __construct(
        protected CourseAccess $courseAccess,
        protected LessonFlowService $flowService,
    ) {}

    public function vocabulary(Request $request, Lesson $lesson)
    {
        $lesson->load([
            'course',
            'vocabulary' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
        ]);

        if (! $this->flowService->isGuided($lesson)) {
            return redirect()->route('lessons.show', $lesson->slug);
        }

        $org = $request->route('organization');
        if ($org && ! $org instanceof Organization) {
            $org = Organization::where('slug', $org)->first();
        }

        if ($org instanceof Organization) {
            $user = auth('admin')->user();
            if (! $this->courseAccess->canAccessLesson($user, $lesson, $org)) {
                abort(403);
            }
        } else {
            $gate = $this->ensureLegacyCourseAccess($lesson);
            if ($gate instanceof RedirectResponse) {
                return $gate;
            }
            $org = $gate instanceof Organization ? $gate : null;
        }

        $sessionId = $request->attributes->get('practice_session_id');
        $words = $lesson->vocabulary->where('is_active', true)->sortBy('sort_order')->values();

        if ($words->isEmpty()) {
            $next = $this->flowService->nextStep(
                $lesson,
                new LessonFlowStep('vocabulary', null, 'Vocabulary')
            );

            if ($next) {
                return redirect()->to($this->flowService->playUrl($next, $lesson, $org));
            }

            return redirect()->route('lessons.show', $lesson->slug);
        }

        $currentWord = $this->flowService->firstIncompleteVocabulary($lesson, $sessionId) ?? $words->first();
        $wordIndex = $words->search(fn ($w) => $w->id === $currentWord->id);
        $isLastWord = $wordIndex === $words->count() - 1;

        $voiceOrganization = $this->resolveVoiceOrganization($lesson, $org);
        $user = auth('admin')->user();
        $voiceProfile = VoiceSampleLearnerProfile::resolve($user);
        $voiceUploadEnabled = $voiceOrganization
            && $voiceOrganization->retain_voice_recordings
            && $voiceProfile !== null
            && config('app.allow_recording_upload', false);

        $guidedData = $this->guidedFlowViewData($request, $lesson, 'vocabulary', null);
        $guidedFlow = $guidedData['guidedFlow'] ?? null;

        $nextActivityStep = $this->flowService->nextStep(
            $lesson,
            new LessonFlowStep('vocabulary', null, 'Vocabulary')
        );
        $continueUrl = $nextActivityStep
            ? $this->flowService->playUrl($nextActivityStep, $lesson, $org)
            : ($guidedFlow['courseUrl'] ?? route('lessons.show', $lesson->slug));

        $wordsCount = $words->count();

        return view('guided.vocabulary', compact(
            'lesson',
            'org',
            'words',
            'currentWord',
            'wordIndex',
            'isLastWord',
            'wordsCount',
            'voiceOrganization',
            'voiceUploadEnabled',
            'guidedFlow',
            'continueUrl',
        ));
    }

    private function resolveVoiceOrganization(Lesson $lesson, ?Organization $org): ?Organization
    {
        if ($org?->retain_voice_recordings) {
            return $org;
        }

        $lesson->loadMissing('course.organizations');
        $course = $lesson->course;
        if (! $course) {
            return null;
        }

        $tenantOrg = $this->courseAccess->primaryTenantOrgForCourse($course);
        if ($tenantOrg?->retain_voice_recordings) {
            return $tenantOrg;
        }

        return $course->organizations()
            ->where('retain_voice_recordings', true)
            ->first();
    }
}

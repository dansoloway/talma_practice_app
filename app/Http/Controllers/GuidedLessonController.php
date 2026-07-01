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
use Illuminate\Support\Facades\Schema;

class GuidedLessonController extends Controller
{
    use GuardsRestrictedCourseAccess;
    use ProvidesGuidedFlowContext;

    public function __construct(
        protected CourseAccess $courseAccess,
        protected LessonFlowService $flowService,
    ) {}

    public function vocabulary(Request $request, $organizationOrLesson = null, ?Lesson $lesson = null)
    {
        if ($lesson instanceof Lesson) {
            // Org-scoped route: /o/{organization}/lessons/{lesson}/guided/vocabulary
        } elseif ($organizationOrLesson instanceof Lesson) {
            $lesson = $organizationOrLesson;
        } else {
            abort(404);
        }

        $lesson->load([
            'course',
            'vocabulary' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
        ]);

        $org = $request->route('organization');
        if ($org && ! $org instanceof Organization) {
            $org = Organization::where('slug', $org)->first();
        }

        if (! $this->flowService->isGuided($lesson)) {
            return redirect()->to($this->lessonShowUrl($lesson, $org instanceof Organization ? $org : null));
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

        $sessionId = $request->attributes->get('practice_learner_scope');
        $words = $lesson->vocabulary->where('is_active', true)->sortBy('sort_order')->values();

        if ($words->isEmpty()) {
            $next = $this->flowService->nextStep(
                $lesson,
                new LessonFlowStep('vocabulary', null, 'Vocabulary')
            );

            if ($next) {
                return redirect()->to($this->flowService->playUrl($next, $lesson, $org));
            }

            return redirect()->to($this->lessonShowUrl($lesson, $org));
        }

        $currentWord = $this->flowService->firstIncompleteVocabulary($lesson, $sessionId) ?? $words->first();
        $wordIndex = $words->search(fn ($w) => $w->id === $currentWord->id);
        $wordIndex = $wordIndex === false ? 0 : $wordIndex;
        $isLastWord = $wordIndex === $words->count() - 1;

        $voiceOrganization = $this->resolveVoiceOrganization($lesson, $org);
        $user = auth('admin')->user();
        $voiceProfile = VoiceSampleLearnerProfile::resolve($user);
        $voiceRecordingOffered = $voiceOrganization?->collectsVoiceRecordings() && $user !== null;
        $voiceUploadEnabled = $voiceRecordingOffered && $voiceProfile !== null;
        $voiceProfileBlockedReason = null;
        if ($voiceRecordingOffered && ! $voiceUploadEnabled) {
            if ($user->isParent() && ! session('selected_student_id')) {
                $voiceProfileBlockedReason = 'select_child';
            } else {
                $voiceProfileBlockedReason = 'profile_incomplete';
            }
        }

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

        $speechFeedbackEnabled = (bool) config('app.speech_feedback_enabled', true);

        $vocabularyProgress = $this->flowService->vocabularyProgressSummary($lesson, $sessionId);

        return view('guided.vocabulary', compact(
            'lesson',
            'org',
            'words',
            'currentWord',
            'wordIndex',
            'isLastWord',
            'wordsCount',
            'voiceOrganization',
            'voiceRecordingOffered',
            'voiceUploadEnabled',
            'voiceProfileBlockedReason',
            'speechFeedbackEnabled',
            'guidedFlow',
            'continueUrl',
            'vocabularyProgress',
        ));
    }

    private function resolveVoiceOrganization(Lesson $lesson, ?Organization $org): ?Organization
    {
        if (! Schema::hasColumn('organizations', 'retain_voice_recordings')) {
            return null;
        }

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

    private function lessonShowUrl(Lesson $lesson, ?Organization $org): string
    {
        if ($org) {
            return route('org.student.lesson', ['organization' => $org, 'slug' => $lesson->slug]);
        }

        return route('lessons.show', $lesson->slug);
    }
}

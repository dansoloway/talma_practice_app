<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsRestrictedCourseAccess;
use App\Models\Lesson;
use App\Models\Organization;
use App\Services\CourseAccess;
use App\Services\LessonFlowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LessonController extends Controller
{
    use GuardsRestrictedCourseAccess;

    protected CourseAccess $courseAccess;

    public function __construct(
        CourseAccess $courseAccess,
        protected LessonFlowService $flowService,
    ) {
        $this->courseAccess = $courseAccess;
    }
    /**
     * Display a listing of active lessons.
     */
    public function index()
    {
        $lessons = Lesson::active()->ordered()->get();
        
        return view('lessons.index', compact('lessons'));
    }

    /**
     * Display the specified lesson with its activities.
     * Legacy route: /lessons/{slug}. Org route: /o/{organization}/lessons/{lesson}.
     */
    public function show(Request $request)
    {
        $organization = $request->route('organization');
        $slug = $request->route('slug');
        $lessonParam = $request->route('lesson');
        // Build eager loading array
        $withRelations = [
            'vocabulary' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            },
            'prompts' => function ($query) {
                $query->where('is_active', true)
                      ->orderBy('sort_order')
                      ->with(['options' => function ($opt) {
                          $opt->where('is_active', true)->orderBy('sort_order');
                      }]);
            },
            'matchingGames' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            },
            'flashcardGames' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            },
            'spellingGames' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            },
            'clauseExercises' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            },
            'sentenceBuilderGames' => function ($query) {
                $query->where('is_active', true)
                      ->orderBy('sort_order')
                      ->with(['questions' => function ($q) {
                          $q->where('is_active', true)->orderBy('sort_order');
                      }]);
            },
            'trueFalseQuestions' => function ($query) {
                $query->where('is_approved', true)
                      ->where('is_active', true)
                      ->orderBy('sort_order');
            }
        ];
        
        // Only eager load trueFalseGames if table exists (after migrations)
        try {
            // Check if table exists by attempting a simple query
            DB::table('true_false_games')->limit(1)->get();
            $withRelations['trueFalseGames'] = function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            };
        } catch (\Exception $e) {
            // Table doesn't exist yet - migrations not run
            // Skip eager loading trueFalseGames
        }
        
        $lesson = ($lessonParam instanceof Lesson)
            ? $lessonParam
            : Lesson::active()
                ->where('slug', $slug ?? $request->route('slug'))
                ->where('is_active', true)
                ->firstOrFail();

        $lesson->load($withRelations);
        $lesson->load('course');

        if ($organization instanceof Organization) {
            $org = $organization;
        } else {
            $gate = $this->ensureLegacyCourseAccess($lesson);
            if ($gate instanceof RedirectResponse) {
                return $gate;
            }
            $org = $gate instanceof Organization
                ? $gate
                : Organization::where('slug', 'default')->where('is_active', true)->firstOrFail();
        }

        $user = auth('admin')->check() ? auth('admin')->user() : null;
        if (!$this->courseAccess->canAccessLesson($user, $lesson, $org)) {
            abort(403, 'You do not have access to this lesson.');
        }
        
        // Add full URLs for audio paths in prompts
        $lesson->prompts->each(function ($prompt) {
            if ($prompt->prompt_audio_path) {
                $prompt->prompt_audio_path = asset($prompt->prompt_audio_path);
            }
            $prompt->options->each(function ($option) {
                if ($option->word_audio_path) {
                    $option->word_audio_path = asset($option->word_audio_path);
                }
                if ($option->image_path) {
                    $option->image_path = asset($option->image_path);
                }
            });
        });
        
        // For review lessons, load vocabulary from source lessons
        if ($lesson->is_review) {
            $lesson->load('reviewSources');
            $lesson->setRelation('vocabulary', $lesson->getVocabularyForGames());
        }

        $practiceSessionId = $request->attributes->get('practice_session_id');
        $isGuided = $this->flowService->isGuided($lesson);
        $completionSummary = $this->flowService->completionSummary($lesson, $practiceSessionId);
        $isLessonComplete = $completionSummary['isComplete'];
        $completedStepKeys = $completionSummary['completedKeys'];

        $flowSteps = $isGuided ? $this->flowService->steps($lesson) : collect();
        $resumeStep = $isGuided ? $this->flowService->resumeStep($lesson, $practiceSessionId) : null;
        $startStep = null;
        $guidedStartUrl = null;

        if ($isGuided && ! $isLessonComplete) {
            $startStep = $resumeStep ?? $this->flowService->firstStep($lesson);
            $guidedStartUrl = $startStep
                ? $this->flowService->playUrl($startStep, $lesson, $org)
                : null;
        }

        $lessonProgress = $completionSummary['total'] > 0 ? [
            'completed' => $completionSummary['completed'],
            'total' => $completionSummary['total'],
            'percent' => $completionSummary['percent'],
        ] : null;

        $guidedProgress = $isGuided ? $lessonProgress : null;

        $vocabularyProgress = $lesson->vocabulary->where('is_active', true)->isNotEmpty()
            ? $this->flowService->vocabularyProgressSummary($lesson, $practiceSessionId)
            : null;

        return view('lessons.show', compact(
            'lesson',
            'org',
            'isGuided',
            'flowSteps',
            'resumeStep',
            'startStep',
            'guidedProgress',
            'guidedStartUrl',
            'completionSummary',
            'isLessonComplete',
            'completedStepKeys',
            'lessonProgress',
            'vocabularyProgress',
        ));
    }
}


<?php

namespace App\Services;

use App\DataTransferObjects\LessonFlowStep;
use App\Models\ActivityEvent;
use App\Models\Lesson;
use App\Models\Organization;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LessonFlowService
{
    public const FLOW_TYPES = [
        'vocabulary',
        'prompts',
        'matching',
        'flashcard',
        'spelling',
        'true_false',
        'clause_exercise',
    ];

    public const DEFAULT_FLOW = [
        'vocabulary',
        'prompts',
        'matching',
        'flashcard',
        'spelling',
        'true_false',
        'clause_exercise',
    ];

    public function isGuided(Lesson $lesson): bool
    {
        $lesson->loadMissing('course');

        if (! $lesson->course?->guided_mode_enabled) {
            return false;
        }

        return $this->steps($lesson)->isNotEmpty();
    }

    /**
     * @return Collection<int, LessonFlowStep>
     */
    public function steps(Lesson $lesson): Collection
    {
        $lesson->loadMissing([
            'course',
            'vocabulary',
            'prompts',
            'matchingGames',
            'flashcardGames',
            'spellingGames',
            'clauseExercises',
            'trueFalseGames',
        ]);

        $template = $this->flowTemplateForLesson($lesson);
        $steps = collect();
        $order = 0;

        foreach ($template as $type) {
            match ($type) {
                'vocabulary' => $this->appendVocabularySteps($lesson, $steps, $order),
                'prompts' => $this->appendPromptsStep($lesson, $steps, $order),
                'matching' => $this->appendGameSteps($lesson, 'matchingGames', 'matching', 'Matching Game', $steps, $order),
                'flashcard' => $this->appendGameSteps($lesson, 'flashcardGames', 'flashcard', 'Flashcards', $steps, $order),
                'spelling' => $this->appendGameSteps($lesson, 'spellingGames', 'spelling', 'Spelling Practice', $steps, $order),
                'clause_exercise' => $this->appendGameSteps($lesson, 'clauseExercises', 'clause_exercise', 'Clause Exercise', $steps, $order),
                'true_false' => $this->appendTrueFalseSteps($lesson, $steps, $order),
                default => null,
            };
        }

        return $steps->values();
    }

    public function playUrl(LessonFlowStep $step, Lesson $lesson, ?Organization $org = null): string
    {
        $params = ['lesson' => $lesson->id];

        if ($org) {
            $params['organization'] = $org;
        }

        return match ($step->type) {
            'vocabulary' => $org
                ? route('org.student.guided.vocabulary', ['organization' => $org, 'lesson' => $lesson])
                : route('guided.vocabulary', ['lesson' => $lesson]),
            'prompts' => route('prompts.play', $params),
            'matching' => route('matching-games.play', array_merge($params, ['matching_game' => $step->activityId])),
            'flashcard' => route('flashcard-games.play', array_merge($params, ['flashcard_game' => $step->activityId])),
            'spelling' => route('spelling-games.play', array_merge($params, ['spelling_game' => $step->activityId])),
            'clause_exercise' => route('clause-exercises.play', array_merge($params, ['clauseExercise' => $step->activityId])),
            'true_false' => route('true-false-games.play', array_merge($params, ['trueFalseGame' => $step->activityId])),
            default => $org
                ? route('org.student.lesson', ['organization' => $org, 'slug' => $lesson->slug])
                : route('lessons.show', $lesson->slug),
        };
    }

    public function nextStep(Lesson $lesson, LessonFlowStep $current): ?LessonFlowStep
    {
        $steps = $this->steps($lesson);
        $index = $steps->search(fn (LessonFlowStep $step) => $step->key() === $current->key());

        if ($index === false) {
            return null;
        }

        return $steps->get($index + 1);
    }

    public function resumeStep(Lesson $lesson, ?string $practiceSessionId): ?LessonFlowStep
    {
        $steps = $this->steps($lesson);

        if ($steps->isEmpty()) {
            return null;
        }

        $completedKeys = $this->completedKeys($lesson, $practiceSessionId);

        foreach ($steps as $step) {
            if ($step->type === 'vocabulary') {
                if (! $this->isVocabularyStepComplete($lesson, $practiceSessionId)) {
                    return $step;
                }

                continue;
            }

            if (! $completedKeys->contains($step->isCompletedKey())) {
                return $step;
            }
        }

        return null;
    }

    public function firstStep(Lesson $lesson): ?LessonFlowStep
    {
        return $this->steps($lesson)->first();
    }

    public function completionPercent(Lesson $lesson, ?string $practiceSessionId): int
    {
        $steps = $this->steps($lesson);
        $total = $steps->count();

        if ($total === 0) {
            return 0;
        }

        $completedKeys = $this->completedKeys($lesson, $practiceSessionId);
        $completed = 0;

        foreach ($steps as $step) {
            if ($step->type === 'vocabulary') {
                if ($this->isVocabularyStepComplete($lesson, $practiceSessionId)) {
                    $completed++;
                }
            } elseif ($completedKeys->contains($step->isCompletedKey())) {
                $completed++;
            }
        }

        return min(100, (int) round(($completed / $total) * 100));
    }

    public function completedStepCount(Lesson $lesson, ?string $practiceSessionId): int
    {
        $steps = $this->steps($lesson);
        $completedKeys = $this->completedKeys($lesson, $practiceSessionId);
        $completed = 0;

        foreach ($steps as $step) {
            if ($step->type === 'vocabulary') {
                if ($this->isVocabularyStepComplete($lesson, $practiceSessionId)) {
                    $completed++;
                }
            } elseif ($completedKeys->contains($step->isCompletedKey())) {
                $completed++;
            }
        }

        return $completed;
    }

    public function stepIndex(Lesson $lesson, LessonFlowStep $step): int
    {
        $index = $this->steps($lesson)->search(fn (LessonFlowStep $s) => $s->key() === $step->key());

        return $index === false ? 0 : $index + 1;
    }

    public function guidedContext(Lesson $lesson, LessonFlowStep $currentStep, ?Organization $org = null): array
    {
        $steps = $this->steps($lesson);
        $next = $this->nextStep($lesson, $currentStep);
        $lessonShowUrl = $org
            ? route('org.student.lesson', ['organization' => $org, 'slug' => $lesson->slug])
            : route('lessons.show', $lesson->slug);
        $courseUrl = $lesson->course
            ? ($org
                ? route('org.student.course', ['organization' => $org, 'course' => $lesson->course])
                : route('student.course', $lesson->course->slug))
            : $lessonShowUrl;

        return [
            'enabled' => true,
            'currentIndex' => $this->stepIndex($lesson, $currentStep),
            'totalSteps' => $steps->count(),
            'nextStep' => $next,
            'nextUrl' => $next ? $this->playUrl($next, $lesson, $org) : null,
            'nextLabel' => $next?->label,
            'lessonUrl' => $lessonShowUrl,
            'courseUrl' => $courseUrl,
            'isLastStep' => $next === null,
        ];
    }

    public function flowTemplateForLesson(Lesson $lesson): array
    {
        $flow = $lesson->course?->guided_flow;

        if (! is_array($flow) || $flow === []) {
            return self::DEFAULT_FLOW;
        }

        return array_values(array_filter(
            $flow,
            fn ($type) => in_array($type, self::FLOW_TYPES, true)
        ));
    }

    /**
     * @return Collection<int, string>
     */
    private function completedKeys(Lesson $lesson, ?string $practiceSessionId): Collection
    {
        if (! $practiceSessionId) {
            return collect();
        }

        return ActivityEvent::query()
            ->where('session_id', $practiceSessionId)
            ->where('lesson_id', $lesson->id)
            ->where('status', 'completed')
            ->get(['activity_type', 'activity_id'])
            ->map(fn (ActivityEvent $event) => $event->activity_type . ':' . ($event->activity_id ?? 0))
            ->unique()
            ->values();
    }

    private function appendVocabularySteps(Lesson $lesson, Collection $steps, int &$order): void
    {
        $count = $lesson->vocabulary->where('is_active', true)->count();

        if ($count === 0) {
            return;
        }

        $steps->push(new LessonFlowStep(
            type: 'vocabulary',
            activityId: null,
            label: 'Vocabulary',
            subdetail: $count . ' ' . Str::plural('word', $count),
            sortOrder: $order++,
        ));
    }

    private function appendPromptsStep(Lesson $lesson, Collection $steps, int &$order): void
    {
        $activeCount = $lesson->prompts->where('is_active', true)->count();

        if ($activeCount === 0) {
            return;
        }

        $steps->push(new LessonFlowStep(
            type: 'prompts',
            activityId: null,
            label: 'Sentence Completion',
            subdetail: $activeCount . ' ' . Str::plural('question', $activeCount),
            sortOrder: $order++,
        ));
    }

    private function appendGameSteps(
        Lesson $lesson,
        string $relation,
        string $type,
        string $defaultLabel,
        Collection $steps,
        int &$order,
    ): void {
        $games = $lesson->{$relation}->where('is_active', true)->sortBy('sort_order');

        foreach ($games as $game) {
            $steps->push(new LessonFlowStep(
                type: $type,
                activityId: $game->id,
                label: $this->displayTitle($lesson, $type, $game->title, $defaultLabel),
                sortOrder: $order++,
            ));
        }
    }

    private function appendTrueFalseSteps(Lesson $lesson, Collection $steps, int &$order): void
    {
        foreach ($lesson->trueFalseGames->where('is_active', true)->sortBy('sort_order') as $game) {
            try {
                $approvedCount = $game->questions()
                    ->where('is_approved', true)
                    ->where('is_active', true)
                    ->count();
            } catch (\Exception) {
                continue;
            }

            if ($approvedCount === 0) {
                continue;
            }

            $steps->push(new LessonFlowStep(
                type: 'true_false',
                activityId: $game->id,
                label: $this->displayTitle($lesson, 'true_false', $game->title, 'True/False'),
                subdetail: $approvedCount . ' ' . Str::plural('question', $approvedCount),
                sortOrder: $order++,
            ));
        }
    }

    private function displayTitle(Lesson $lesson, string $type, string $title, string $fallback): string
    {
        $lessonTitleEscaped = preg_quote(trim($lesson->title), '/');
        $trimmed = trim($title);

        $patterns = [
            'matching' => '/^' . $lessonTitleEscaped . '\s+Matching\s+Game\s+\d+$/i',
            'flashcard' => '/^' . $lessonTitleEscaped . '\s+Flashcards\s+\d+$/i',
            'spelling' => '/^' . $lessonTitleEscaped . '\s+Spelling\s+Practice\s+\d+$/i',
        ];

        if (isset($patterns[$type]) && preg_match($patterns[$type], $trimmed)) {
            return $fallback;
        }

        return $trimmed !== '' ? $trimmed : $fallback;
    }

    /**
     * @return Collection<int, int>
     */
    public function completedVocabularyIds(Lesson $lesson, ?string $practiceSessionId): Collection
    {
        if (! $practiceSessionId) {
            return collect();
        }

        return ActivityEvent::query()
            ->where('session_id', $practiceSessionId)
            ->where('lesson_id', $lesson->id)
            ->where('activity_type', 'vocabulary')
            ->where('status', 'completed')
            ->whereNotNull('activity_id')
            ->pluck('activity_id')
            ->unique()
            ->values();
    }

    public function isVocabularyStepComplete(Lesson $lesson, ?string $practiceSessionId): bool
    {
        $wordIds = $lesson->vocabulary->where('is_active', true)->pluck('id');

        if ($wordIds->isEmpty()) {
            return true;
        }

        $completed = $this->completedVocabularyIds($lesson, $practiceSessionId);

        return $wordIds->every(fn ($id) => $completed->contains($id));
    }

    public function firstIncompleteVocabulary(Lesson $lesson, ?string $practiceSessionId)
    {
        $completed = $this->completedVocabularyIds($lesson, $practiceSessionId);

        return $lesson->vocabulary
            ->where('is_active', true)
            ->sortBy('sort_order')
            ->first(fn ($vocab) => ! $completed->contains($vocab->id));
    }
}

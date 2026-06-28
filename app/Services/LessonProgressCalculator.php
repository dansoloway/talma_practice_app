<?php

namespace App\Services;

use App\Models\Lesson;
use Illuminate\Support\Collection;

class LessonProgressCalculator
{
    public function __construct(
        protected LessonFlowService $flowService,
    ) {}

    /**
     * @param  Collection<int, Lesson>  $lessons
     * @return array<int, int> lesson_id => completion percent (0–100)
     */
    public function completionPercentsForLessons(Collection $lessons, ?string $practiceSessionId): array
    {
        if ($lessons->isEmpty()) {
            return [];
        }

        $defaults = $lessons->mapWithKeys(fn (Lesson $lesson) => [$lesson->id => 0])->all();

        if (! $practiceSessionId) {
            return $defaults;
        }

        foreach ($lessons as $lesson) {
            $lesson->loadMissing('course');
            $defaults[$lesson->id] = $this->flowService
                ->completionSummary($lesson, $practiceSessionId)['percent'];
        }

        return $defaults;
    }
}

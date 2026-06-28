<?php

namespace App\Services;

use App\Models\ActivityEvent;
use App\Models\Lesson;
use Illuminate\Support\Collection;

class LessonProgressCalculator
{
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

        if (!$practiceSessionId) {
            return $defaults;
        }

        $events = ActivityEvent::query()
            ->where('session_id', $practiceSessionId)
            ->whereIn('lesson_id', $lessons->pluck('id'))
            ->where('status', 'completed')
            ->get(['lesson_id', 'activity_type', 'activity_id']);

        $eventsByLesson = $events->groupBy('lesson_id');

        foreach ($lessons as $lesson) {
            $totalActivities = $lesson->studentActivityCount();
            if ($totalActivities === 0) {
                continue;
            }

            $completedActivities = ($eventsByLesson->get($lesson->id) ?? collect())
                ->unique(fn (ActivityEvent $event) => $event->activity_type . ':' . ($event->activity_id ?? 0))
                ->count();

            $defaults[$lesson->id] = min(100, (int) round(($completedActivities / $totalActivities) * 100));
        }

        return $defaults;
    }
}

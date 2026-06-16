<?php

namespace App\Services;

use App\Models\Lesson;
use Illuminate\Support\Collection;

class LessonSessionGrouper
{
    /**
     * Group lessons by session_number for accordion display.
     *
     * @param  Collection<int, Lesson>  $lessons
     * @return array{sessions: list<array{session_number: int, label: string, lessons: Collection<int, Lesson>}>, review: Collection<int, Lesson>, ungrouped: Collection<int, Lesson>}
     */
    public static function group(Collection $lessons): array
    {
        $review = collect();
        $ungrouped = collect();
        /** @var array<int, Collection<int, Lesson>> $sessionBuckets */
        $sessionBuckets = [];

        foreach ($lessons as $lesson) {
            if ($lesson->is_review) {
                $review->push($lesson);
                continue;
            }

            if ($lesson->session_number === null) {
                $ungrouped->push($lesson);
                continue;
            }

            $key = (int) $lesson->session_number;
            if (!isset($sessionBuckets[$key])) {
                $sessionBuckets[$key] = collect();
            }
            $sessionBuckets[$key]->push($lesson);
        }

        ksort($sessionBuckets);

        $sessions = [];
        foreach ($sessionBuckets as $sessionNumber => $groupLessons) {
            $sorted = $groupLessons
                ->sortBy([
                    ['part_number', 'asc'],
                    ['created_at', 'asc'],
                ])
                ->values();

            $sessions[] = [
                'session_number' => $sessionNumber,
                'label' => static::sessionLabel($sorted, $sessionNumber),
                'lessons' => $sorted,
            ];
        }

        return [
            'sessions' => $sessions,
            'review' => $review->sortBy('created_at')->values(),
            'ungrouped' => $ungrouped->sortBy('created_at')->values(),
        ];
    }

    /**
     * @param  Collection<int, Lesson>  $lessons
     */
    public static function sessionLabel(Collection $lessons, int $sessionNumber): string
    {
        foreach ($lessons as $lesson) {
            if ($lesson->session_title) {
                $title = static::stripPartSuffix($lesson->session_title);
                if ($title !== '') {
                    return $title;
                }
            }
        }

        return "Session {$sessionNumber}";
    }

    public static function stripPartSuffix(string $sessionTitle): string
    {
        return trim((string) preg_replace('/\s*[-–—]\s*part\s+[a-z0-9]+\s*$/i', '', $sessionTitle));
    }
}

<?php

namespace App\Services;

use App\Models\ActivityEvent;
use App\Models\Course;
use App\Models\LearnerLoginEvent;
use App\Models\Lesson;
use App\Models\Organization;
use App\Models\VoiceSample;
use App\Services\Import\SummerVocabAssetArchiver;
use Carbon\Carbon;

class SummerDailyUsageReportService
{
    public function __construct(
        private readonly LessonFlowService $flowService,
    ) {}

    /**
     * @return array{
     *     organization: Organization|null,
     *     date: string,
     *     timezone: string,
     *     window_start: Carbon,
     *     window_end: Carbon,
     *     logins: int,
     *     lessons_completed: int,
     *     voice_recordings: int,
     * }
     */
    public function forDate(?string $date = null): array
    {
        $timezone = (string) config('app.summer_daily_report_timezone', 'Asia/Jerusalem');
        [$start, $end, $dateLabel] = $this->windowForDate($date, $timezone);

        $organization = Organization::query()
            ->where('slug', Organization::SUMMER_PRACTICE_PAL_SLUG)
            ->first();

        if (! $organization) {
            return [
                'organization' => null,
                'date' => $dateLabel,
                'timezone' => $timezone,
                'window_start' => $start,
                'window_end' => $end,
                'logins' => 0,
                'lessons_completed' => 0,
                'voice_recordings' => 0,
            ];
        }

        return [
            'organization' => $organization,
            'date' => $dateLabel,
            'timezone' => $timezone,
            'window_start' => $start,
            'window_end' => $end,
            'logins' => $this->loginCount($organization, $start, $end),
            'lessons_completed' => $this->lessonsCompletedCount($start, $end),
            'voice_recordings' => $this->voiceRecordingCount($organization, $start, $end),
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string} Half-open [start, end) in UTC, plus the calendar date label in the report timezone.
     */
    public function windowForDate(?string $date, ?string $timezone = null): array
    {
        $timezone = $timezone ?: (string) config('app.summer_daily_report_timezone', 'Asia/Jerusalem');

        $day = $date
            ? Carbon::parse($date, $timezone)->startOfDay()
            : Carbon::now($timezone)->subDay()->startOfDay();

        $start = $day->copy()->utc();
        $end = $day->copy()->addDay()->utc();

        return [$start, $end, $day->toDateString()];
    }

    private function loginCount(Organization $organization, Carbon $start, Carbon $end): int
    {
        return LearnerLoginEvent::query()
            ->where('organization_id', $organization->id)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->count();
    }

    private function voiceRecordingCount(Organization $organization, Carbon $start, Carbon $end): int
    {
        return VoiceSample::query()
            ->where('organization_id', $organization->id)
            ->where('recorded_at', '>=', $start)
            ->where('recorded_at', '<', $end)
            ->count();
    }

    private function lessonsCompletedCount(Carbon $start, Carbon $end): int
    {
        $summerCourseIds = Course::query()
            ->whereIn('slug', array_values(SummerVocabAssetArchiver::COURSE_SLUGS))
            ->pluck('id');

        if ($summerCourseIds->isEmpty()) {
            return 0;
        }

        $candidates = ActivityEvent::query()
            ->where('status', 'completed')
            ->whereNotNull('lesson_id')
            ->whereNotNull('session_id')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->whereHas('lesson', fn ($query) => $query->whereIn('course_id', $summerCourseIds))
            ->select('session_id', 'lesson_id')
            ->distinct()
            ->get();

        if ($candidates->isEmpty()) {
            return 0;
        }

        $lessons = Lesson::query()
            ->whereIn('id', $candidates->pluck('lesson_id')->unique()->values())
            ->with([
                'course',
                'vocabulary',
                'prompts',
                'matchingGames',
                'flashcardGames',
                'spellingGames',
                'clauseExercises',
                'trueFalseGames',
            ])
            ->get()
            ->keyBy('id');

        $completed = 0;

        foreach ($candidates as $candidate) {
            $lesson = $lessons->get($candidate->lesson_id);

            if (! $lesson) {
                continue;
            }

            $wasCompleteBefore = $this->flowService->isLessonComplete(
                $lesson,
                $candidate->session_id,
                $start,
            );
            $isCompleteByEnd = $this->flowService->isLessonComplete(
                $lesson,
                $candidate->session_id,
                $end,
            );

            if (! $wasCompleteBefore && $isCompleteByEnd) {
                $completed++;
            }
        }

        return $completed;
    }
}

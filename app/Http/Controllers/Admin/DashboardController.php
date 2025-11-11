<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityEvent;
use App\Models\FlashcardGame;
use App\Models\Lesson;
use App\Models\MatchingGame;
use App\Models\Prompt;
use App\Models\Option;
use App\Models\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        $totalResponses = Response::count();
        $uniqueSessions = Response::whereNotNull('session_id')
            ->distinct('session_id')
            ->count('session_id');
        $responsesLast7Days = Response::where('created_at', '>=', now()->subDays(7))->count();
        $activeSessionsToday = Response::whereDate('created_at', Carbon::today())
            ->distinct('session_id')
            ->count('session_id');

        $sessionStats = $this->buildSessionStats();
        $lessonStats = $this->buildLessonStats();
        $activityStats = $this->buildActivityStats();
        $dailyPractice = $this->buildDailyPracticeSeries();

        $recentLessons = Lesson::latest()->take(5)->get();
        $recentResponses = Response::with(['lesson', 'prompt', 'option'])
            ->latest()
            ->take(10)
            ->get();

        $stats = [
            'total_responses' => $totalResponses,
            'unique_sessions' => $uniqueSessions,
            'responses_last_7_days' => $responsesLast7Days,
            'active_sessions_today' => $activeSessionsToday,
            'average_session_duration' => $sessionStats['average_duration'],
            'median_session_duration' => $sessionStats['median_duration'],
            'average_responses_per_session' => $sessionStats['average_responses'],
            'total_activity_completions' => $activityStats['totals']['completed'],
        ];

        return view('admin.dashboard', compact(
            'stats',
            'recentLessons',
            'recentResponses',
            'lessonStats',
            'activityStats',
            'dailyPractice'
        ));
    }

    protected function buildSessionStats(): array
    {
        $sessionRows = DB::table('responses')
            ->select(
                'session_id',
                DB::raw('TIMESTAMPDIFF(SECOND, MIN(created_at), MAX(created_at)) as duration_seconds'),
                DB::raw('COUNT(*) as responses_count')
            )
            ->whereNotNull('session_id')
            ->groupBy('session_id')
            ->get()
            ->filter(fn ($row) => $row->duration_seconds !== null);

        $durations = $sessionRows->pluck('duration_seconds')->sort()->values();
        $averageDuration = $durations->avg() ?? 0;
        $medianDuration = $this->median($durations->all());
        $averageResponses = $sessionRows->pluck('responses_count')->avg() ?? 0;

        return [
            'average_duration' => (int) round($averageDuration),
            'median_duration' => (int) round($medianDuration),
            'average_responses' => round($averageResponses, 1),
        ];
    }

    protected function buildLessonStats(): array
    {
        $topLessons = Response::select('lesson_id', DB::raw('COUNT(*) as total'))
            ->with('lesson:id,title,slug')
            ->groupBy('lesson_id')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(function ($row) {
                return [
                    'lesson' => $row->lesson,
                    'responses' => $row->total,
                ];
            })
            ->filter(fn ($item) => $item['lesson']);

        $lessonsWithNoResponses = Lesson::doesntHave('responses')->count();

        return [
            'top_lessons' => $topLessons,
            'lessons_without_responses' => $lessonsWithNoResponses,
        ];
    }

    protected function buildActivityStats(): array
    {
        $activityEvents = ActivityEvent::orderByDesc('created_at')->get();

        $flashcardEvents = $activityEvents->where('activity_type', 'flashcard');
        $flashcardIds = $flashcardEvents->pluck('activity_id')->filter()->unique();
        $flashcardGames = FlashcardGame::with('lesson')->whereIn('id', $flashcardIds)->get()->keyBy('id');

        $matchingEvents = $activityEvents->where('activity_type', 'matching');
        $matchingIds = $matchingEvents->pluck('activity_id')->filter()->unique();
        $matchingGames = MatchingGame::with('lesson')->whereIn('id', $matchingIds)->get()->keyBy('id');

        $flashcardStats = $this->aggregateActivityStats($flashcardEvents, $flashcardGames);
        $matchingStats = $this->aggregateActivityStats($matchingEvents, $matchingGames);

        $totalStarted = $activityEvents->where('status', 'started')->count();
        $totalCompleted = $activityEvents->where('status', 'completed')->count();

        return [
            'flashcard' => $flashcardStats,
            'matching' => $matchingStats,
            'totals' => [
                'started' => $totalStarted,
                'completed' => $totalCompleted,
            ],
        ];
    }

    protected function aggregateActivityStats($events, $activities)
    {
        $grouped = $events->groupBy('activity_id');

        return $grouped->map(function ($events, $activityId) use ($activities) {
            $activity = $activities->get($activityId);
            if (!$activity) {
                return null;
            }

            $started = $events->where('status', 'started')->count();
            $completed = $events->where('status', 'completed')->count();
            $durations = $events->where('status', 'completed')
                ->pluck('meta')
                ->map(fn ($meta) => data_get($meta, 'duration_seconds'))
                ->filter();

            $avgDuration = $durations->avg();

            return [
                'activity' => $activity,
                'lesson' => $activity->lesson ?? null,
                'started' => $started,
                'completed' => $completed,
                'conversion_rate' => $started > 0 ? round(($completed / $started) * 100, 1) : null,
                'average_duration' => $avgDuration ? (int) round($avgDuration) : null,
            ];
        })->filter()->values();
    }

    protected function buildDailyPracticeSeries(): array
    {
        $daysBack = 13;
        $startDate = Carbon::today()->subDays($daysBack);

        $raw = Response::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total'),
            DB::raw('COUNT(DISTINCT session_id) as unique_sessions')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $rawSessions = Response::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(DISTINCT session_id) as unique_sessions')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->pluck('unique_sessions', 'date')
            ->toArray();

        $series = [];
        for ($date = $startDate->copy(); $date <= Carbon::today(); $date->addDay()) {
            $key = $date->toDateString();
            $series[] = [
                'date' => $date->format('M j'),
                'responses' => $raw[$key] ?? 0,
                'unique_sessions' => $rawSessions[$key] ?? 0,
            ];
        }

        return $series;
    }

    protected function median(array $values): float
    {
        $count = count($values);
        if ($count === 0) {
            return 0;
        }

        sort($values);
        $middle = (int) floor($count / 2);

        if ($count % 2) {
            return $values[$middle];
        }

        return ($values[$middle - 1] + $values[$middle]) / 2;
    }
}


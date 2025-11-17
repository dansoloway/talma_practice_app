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
        
        // Count unique sessions from both Response and ActivityEvent tables
        $uniqueSessionsFromResponses = Response::whereNotNull('session_id')
            ->distinct('session_id')
            ->pluck('session_id');
        $uniqueSessionsFromActivities = ActivityEvent::whereNotNull('session_id')
            ->distinct('session_id')
            ->pluck('session_id');
        $uniqueSessions = $uniqueSessionsFromResponses->merge($uniqueSessionsFromActivities)->unique()->count();
        
        $responsesLast7Days = Response::where('created_at', '>=', now()->subDays(7))->count();
        
        // Count active sessions today from both tables
        $activeSessionsTodayFromResponses = Response::whereDate('created_at', Carbon::today())
            ->whereNotNull('session_id')
            ->distinct('session_id')
            ->pluck('session_id');
        $activeSessionsTodayFromActivities = ActivityEvent::whereDate('created_at', Carbon::today())
            ->whereNotNull('session_id')
            ->distinct('session_id')
            ->pluck('session_id');
        $activeSessionsToday = $activeSessionsTodayFromResponses->merge($activeSessionsTodayFromActivities)->unique()->count();

        $sessionStats = $this->buildSessionStats();
        $lessonStats = $this->buildLessonStats();
        $activityStats = $this->buildActivityStats();
        $dailyPractice = $this->buildDailyPracticeSeries();
        $deviceStats = $this->buildDeviceStats();

        $recentLessons = Lesson::latest()->take(5)->get();
        $recentResponses = Response::with(['lesson', 'prompt', 'option'])
            ->latest()
            ->take(10)
            ->get();

        $stats = [
            'total_responses' => $totalResponses, // Prompt responses
            'unique_sessions' => $uniqueSessions,
            'responses_last_7_days' => $responsesLast7Days, // Prompt responses last 7 days
            'active_sessions_today' => $activeSessionsToday,
            'average_session_duration' => $sessionStats['average_duration'],
            'median_session_duration' => $sessionStats['median_duration'],
            'average_responses_per_session' => $sessionStats['average_responses'], // Average prompt responses per session
            'total_activity_completions' => $activityStats['totals']['completed'], // Game completions
            'mobile_sessions' => $deviceStats['mobile_sessions'],
            'desktop_sessions' => $deviceStats['desktop_sessions'],
            'mobile_percentage' => $deviceStats['mobile_percentage'],
            'desktop_percentage' => $deviceStats['desktop_percentage'],
        ];

        return view('admin.dashboard', compact(
            'stats',
            'recentLessons',
            'recentResponses',
            'lessonStats',
            'activityStats',
            'dailyPractice',
            'deviceStats'
        ));
    }

    protected function buildDeviceStats(): array
    {
        // Get unique sessions by device type from responses
        $mobileSessionsFromResponses = Response::where('device_type', 'mobile')
            ->whereNotNull('session_id')
            ->distinct('session_id')
            ->pluck('session_id');
        
        $desktopSessionsFromResponses = Response::where('device_type', 'desktop')
            ->whereNotNull('session_id')
            ->distinct('session_id')
            ->pluck('session_id');

        // Get unique sessions by device type from activity events
        $mobileSessionsFromActivities = ActivityEvent::where('device_type', 'mobile')
            ->whereNotNull('session_id')
            ->distinct('session_id')
            ->pluck('session_id');
        
        $desktopSessionsFromActivities = ActivityEvent::where('device_type', 'desktop')
            ->whereNotNull('session_id')
            ->distinct('session_id')
            ->pluck('session_id');

        // Merge and count unique sessions
        $mobileSessions = $mobileSessionsFromResponses->merge($mobileSessionsFromActivities)->unique()->count();
        $desktopSessions = $desktopSessionsFromResponses->merge($desktopSessionsFromActivities)->unique()->count();
        
        $totalSessions = $mobileSessions + $desktopSessions;
        
        $mobilePercentage = $totalSessions > 0 ? round(($mobileSessions / $totalSessions) * 100, 1) : 0;
        $desktopPercentage = $totalSessions > 0 ? round(($desktopSessions / $totalSessions) * 100, 1) : 0;

        return [
            'mobile_sessions' => $mobileSessions,
            'desktop_sessions' => $desktopSessions,
            'mobile_percentage' => $mobilePercentage,
            'desktop_percentage' => $desktopPercentage,
        ];
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
        // Get all lessons with their time spent
        $lessons = Lesson::all();
        
        $lessonTimeStats = $lessons->map(function ($lesson) {
            $totalTimeSeconds = 0;
            
            // Time from completed activities (games) for this lesson
            $completedActivities = ActivityEvent::where('lesson_id', $lesson->id)
                ->where('status', 'completed')
                ->whereNotNull('meta')
                ->get();
            
            foreach ($completedActivities as $activity) {
                $duration = data_get($activity->meta, 'duration_seconds', 0);
                if ($duration && is_numeric($duration)) {
                    $totalTimeSeconds += (int) $duration;
                }
            }
            
            // Time from Response sessions for this lesson - calculate session duration from first to last response
            $sessionDurations = Response::select('session_id')
                ->selectRaw('TIMESTAMPDIFF(SECOND, MIN(created_at), MAX(created_at)) as duration')
                ->where('lesson_id', $lesson->id)
                ->whereNotNull('session_id')
                ->groupBy('session_id')
                ->havingRaw('COUNT(*) > 1') // Only sessions with multiple responses
                ->get();
            
            foreach ($sessionDurations as $session) {
                if ($session->duration && $session->duration > 0) {
                    $totalTimeSeconds += (int) $session->duration;
                }
            }
            
            // Add time for single-response sessions (estimate 30 seconds per response)
            $singleResponseSessions = DB::table('responses')
                ->select('session_id')
                ->where('lesson_id', $lesson->id)
                ->whereNotNull('session_id')
                ->groupBy('session_id')
                ->havingRaw('COUNT(*) = 1')
                ->count();
            $totalTimeSeconds += $singleResponseSessions * 30; // Estimate 30 seconds per single response
            
            return [
                'lesson' => $lesson,
                'time_seconds' => $totalTimeSeconds,
            ];
        })->filter(fn ($item) => $item['time_seconds'] > 0)
          ->sortByDesc('time_seconds')
          ->take(5)
          ->values();

        $lessonsWithNoActivity = Lesson::doesntHave('responses')
            ->whereNotIn('id', ActivityEvent::distinct()->pluck('lesson_id')->filter())
            ->count();

        return [
            'top_lessons' => $lessonTimeStats,
            'lessons_without_responses' => $lessonsWithNoActivity,
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

        // Get unique sessions per day from both tables and calculate time spent
        $series = [];
        for ($date = Carbon::today(); $date >= $startDate; $date->subDay()) {
            $key = $date->toDateString();
            
            // Get unique sessions from Response table for this day
            $sessionsFromResponses = Response::whereDate('created_at', $key)
                ->whereNotNull('session_id')
                ->distinct('session_id')
                ->pluck('session_id');
            
            // Get unique sessions from ActivityEvent table for this day
            $sessionsFromActivities = ActivityEvent::whereDate('created_at', $key)
                ->whereNotNull('session_id')
                ->distinct('session_id')
                ->pluck('session_id');
            
            // Merge and count unique sessions (removing duplicates)
            $uniqueSessions = $sessionsFromResponses->merge($sessionsFromActivities)->unique()->count();
            
            // Calculate total time spent (in seconds)
            $totalTimeSeconds = 0;
            
            // Time from completed activities (games) - sum duration_seconds from meta
            $completedActivities = ActivityEvent::whereDate('created_at', $key)
                ->where('status', 'completed')
                ->whereNotNull('meta')
                ->get();
            
            foreach ($completedActivities as $activity) {
                $duration = data_get($activity->meta, 'duration_seconds', 0);
                if ($duration && is_numeric($duration)) {
                    $totalTimeSeconds += (int) $duration;
                }
            }
            
            // Time from Response sessions - calculate session duration from first to last response
            $sessionDurations = Response::select('session_id')
                ->selectRaw('TIMESTAMPDIFF(SECOND, MIN(created_at), MAX(created_at)) as duration')
                ->whereDate('created_at', $key)
                ->whereNotNull('session_id')
                ->groupBy('session_id')
                ->havingRaw('COUNT(*) > 1') // Only sessions with multiple responses
                ->get();
            
            foreach ($sessionDurations as $session) {
                if ($session->duration && $session->duration > 0) {
                    $totalTimeSeconds += (int) $session->duration;
                }
            }
            
            // Add time for single-response sessions (estimate 30 seconds per response)
            $singleResponseSessions = DB::table('responses')
                ->select('session_id')
                ->whereDate('created_at', $key)
                ->whereNotNull('session_id')
                ->groupBy('session_id')
                ->havingRaw('COUNT(*) = 1')
                ->count();
            $totalTimeSeconds += $singleResponseSessions * 30; // Estimate 30 seconds per single response
            
            $series[] = [
                'date' => $date->format('M j'),
                'total_time_seconds' => $totalTimeSeconds,
                'unique_sessions' => $uniqueSessions,
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


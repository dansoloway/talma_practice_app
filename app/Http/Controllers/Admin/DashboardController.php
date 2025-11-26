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
     * Office IP address to exclude from analytics.
     */
    private const OFFICE_IP = '141.226.32.90';

    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        $totalResponses = $this->excludeOfficeIp(Response::query())->count();
        
        // Count unique sessions from both Response and ActivityEvent tables
        $uniqueSessionsFromResponses = $this->excludeOfficeIp(Response::query())
            ->whereNotNull('session_id')
            ->distinct('session_id')
            ->pluck('session_id');
        $uniqueSessionsFromActivities = $this->excludeOfficeIp(ActivityEvent::query())
            ->whereNotNull('session_id')
            ->distinct('session_id')
            ->pluck('session_id');
        $uniqueSessions = $uniqueSessionsFromResponses->merge($uniqueSessionsFromActivities)->unique()->count();
        
        $responsesLast7Days = $this->excludeOfficeIp(Response::query())
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        
        // Count active sessions today from both tables
        $activeSessionsTodayFromResponses = $this->excludeOfficeIp(Response::query())
            ->whereDate('created_at', Carbon::today())
            ->whereNotNull('session_id')
            ->distinct('session_id')
            ->pluck('session_id');
        $activeSessionsTodayFromActivities = $this->excludeOfficeIp(ActivityEvent::query())
            ->whereDate('created_at', Carbon::today())
            ->whereNotNull('session_id')
            ->distinct('session_id')
            ->pluck('session_id');
        $activeSessionsToday = $activeSessionsTodayFromResponses->merge($activeSessionsTodayFromActivities)->unique()->count();

        $sessionStats = $this->buildSessionStats();
        $lessonStats = $this->buildLessonStats();
        $activityStats = $this->buildActivityStats();
        $dailyPractice = $this->buildDailyPracticeSeries();
        $deviceStats = $this->buildDeviceStats();
        $countryStats = $this->buildCountryStats();
        $israelCityStats = $this->buildIsraelCityStats();

        $recentLessons = Lesson::latest()->take(5)->get();
        $recentResponses = $this->excludeOfficeIp(Response::query())
            ->with(['lesson', 'prompt', 'option'])
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
            'deviceStats',
            'countryStats',
            'israelCityStats'
        ));
    }

    /**
     * Exclude office IP from query.
     */
    protected function excludeOfficeIp($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('ip_address')
              ->orWhere('ip_address', '!=', self::OFFICE_IP);
        });
    }

    /**
     * Build country statistics from user locations.
     */
    protected function buildCountryStats(): array
    {
        // Get unique sessions by country from responses
        $countrySessionsFromResponses = $this->excludeOfficeIp(Response::query())
            ->whereNotNull('country')
            ->whereNotNull('session_id')
            ->select('country', 'session_id')
            ->distinct()
            ->get()
            ->groupBy('country')
            ->map(fn ($group) => $group->pluck('session_id')->unique()->count());

        // Get unique sessions by country from activity events
        $countrySessionsFromActivities = $this->excludeOfficeIp(ActivityEvent::query())
            ->whereNotNull('country')
            ->whereNotNull('session_id')
            ->select('country', 'session_id')
            ->distinct()
            ->get()
            ->groupBy('country')
            ->map(fn ($group) => $group->pluck('session_id')->unique()->count());

        // Merge and combine counts
        $countryStats = [];
        foreach ($countrySessionsFromResponses as $country => $count) {
            $countryStats[$country] = ($countryStats[$country] ?? 0) + $count;
        }
        foreach ($countrySessionsFromActivities as $country => $count) {
            $countryStats[$country] = ($countryStats[$country] ?? 0) + $count;
        }

        // Sort by count descending and take top 10
        arsort($countryStats);
        $topCountries = array_slice($countryStats, 0, 10, true);

        $totalSessions = array_sum($countryStats);

        return [
            'top_countries' => $topCountries,
            'total_sessions' => $totalSessions,
        ];
    }

    /**
     * Build Israeli city statistics from user locations.
     */
    protected function buildIsraelCityStats(): array
    {
        // Get unique sessions by city from responses (Israel only)
        $citySessionsFromResponses = $this->excludeOfficeIp(Response::query())
            ->where('country', 'IL')
            ->whereNotNull('city')
            ->whereNotNull('session_id')
            ->select('city', 'region', 'session_id')
            ->distinct()
            ->get()
            ->groupBy('city')
            ->map(function ($group) {
                return [
                    'sessions' => $group->pluck('session_id')->unique()->count(),
                    'region' => $group->first()->region,
                ];
            });

        // Get unique sessions by city from activity events (Israel only)
        $citySessionsFromActivities = $this->excludeOfficeIp(ActivityEvent::query())
            ->where('country', 'IL')
            ->whereNotNull('city')
            ->whereNotNull('session_id')
            ->select('city', 'region', 'session_id')
            ->distinct()
            ->get()
            ->groupBy('city')
            ->map(function ($group) {
                return [
                    'sessions' => $group->pluck('session_id')->unique()->count(),
                    'region' => $group->first()->region,
                ];
            });

        // Merge and combine counts
        $cityStats = [];
        foreach ($citySessionsFromResponses as $city => $data) {
            $cityStats[$city] = [
                'sessions' => ($cityStats[$city]['sessions'] ?? 0) + $data['sessions'],
                'region' => $data['region'],
            ];
        }
        foreach ($citySessionsFromActivities as $city => $data) {
            $cityStats[$city] = [
                'sessions' => ($cityStats[$city]['sessions'] ?? 0) + $data['sessions'],
                'region' => $data['region'] ?? $cityStats[$city]['region'] ?? null,
            ];
        }

        // Sort by session count descending
        uasort($cityStats, fn($a, $b) => $b['sessions'] <=> $a['sessions']);

        $totalSessions = array_sum(array_column($cityStats, 'sessions'));

        return [
            'cities' => $cityStats,
            'total_sessions' => $totalSessions,
        ];
    }

    protected function buildDeviceStats(): array
    {
        // Get unique sessions by device type from responses
        $mobileSessionsFromResponses = $this->excludeOfficeIp(Response::query())
            ->where('device_type', 'mobile')
            ->whereNotNull('session_id')
            ->distinct('session_id')
            ->pluck('session_id');
        
        $desktopSessionsFromResponses = $this->excludeOfficeIp(Response::query())
            ->where('device_type', 'desktop')
            ->whereNotNull('session_id')
            ->distinct('session_id')
            ->pluck('session_id');

        // Get unique sessions by device type from activity events
        $mobileSessionsFromActivities = $this->excludeOfficeIp(ActivityEvent::query())
            ->where('device_type', 'mobile')
            ->whereNotNull('session_id')
            ->distinct('session_id')
            ->pluck('session_id');
        
        $desktopSessionsFromActivities = $this->excludeOfficeIp(ActivityEvent::query())
            ->where('device_type', 'desktop')
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
        // Get all session durations from responses table
        $responseSessions = DB::table('responses')
            ->select(
                'session_id',
                DB::raw('MIN(created_at) as first_event'),
                DB::raw('MAX(created_at) as last_event'),
                DB::raw('COUNT(*) as responses_count')
            )
            ->whereNotNull('session_id')
            ->where(function ($q) {
                $q->whereNull('ip_address')
                  ->orWhere('ip_address', '!=', self::OFFICE_IP);
            })
            ->groupBy('session_id')
            ->get();

        // Get all session durations from activity_events table
        $activitySessions = DB::table('activity_events')
            ->select(
                'session_id',
                DB::raw('MIN(created_at) as first_event'),
                DB::raw('MAX(created_at) as last_event'),
                DB::raw('COUNT(*) as events_count')
            )
            ->whereNotNull('session_id')
            ->where(function ($q) {
                $q->whereNull('ip_address')
                  ->orWhere('ip_address', '!=', self::OFFICE_IP);
            })
            ->groupBy('session_id')
            ->get();

        // Combine sessions and calculate duration for each unique session
        $sessionDurations = [];
        
        // Process response sessions
        foreach ($responseSessions as $session) {
            $sessionId = $session->session_id;
            if (!isset($sessionDurations[$sessionId])) {
                $sessionDurations[$sessionId] = [
                    'first_event' => $session->first_event,
                    'last_event' => $session->last_event,
                    'responses_count' => $session->responses_count,
                    'events_count' => 0,
                ];
            } else {
                // Update if this session has an earlier first event or later last event
                if ($session->first_event < $sessionDurations[$sessionId]['first_event']) {
                    $sessionDurations[$sessionId]['first_event'] = $session->first_event;
                }
                if ($session->last_event > $sessionDurations[$sessionId]['last_event']) {
                    $sessionDurations[$sessionId]['last_event'] = $session->last_event;
                }
                $sessionDurations[$sessionId]['responses_count'] += $session->responses_count;
            }
        }
        
        // Process activity event sessions
        foreach ($activitySessions as $session) {
            $sessionId = $session->session_id;
            if (!isset($sessionDurations[$sessionId])) {
                $sessionDurations[$sessionId] = [
                    'first_event' => $session->first_event,
                    'last_event' => $session->last_event,
                    'responses_count' => 0,
                    'events_count' => $session->events_count,
                ];
            } else {
                // Update if this session has an earlier first event or later last event
                if ($session->first_event < $sessionDurations[$sessionId]['first_event']) {
                    $sessionDurations[$sessionId]['first_event'] = $session->first_event;
                }
                if ($session->last_event > $sessionDurations[$sessionId]['last_event']) {
                    $sessionDurations[$sessionId]['last_event'] = $session->last_event;
                }
                $sessionDurations[$sessionId]['events_count'] += $session->events_count;
            }
        }
        
        // Calculate duration for each session
        $durations = [];
        $responseCounts = [];
        
        foreach ($sessionDurations as $sessionId => $data) {
            $firstEvent = Carbon::parse($data['first_event']);
            $lastEvent = Carbon::parse($data['last_event']);
            $durationSeconds = $firstEvent->diffInSeconds($lastEvent);
            
            // Only include sessions with valid duration
            if ($durationSeconds >= 0) {
                $durations[] = $durationSeconds;
                $responseCounts[] = $data['responses_count'];
            }
        }
        
        // Sort durations for median calculation
        sort($durations);
        
        $averageDuration = count($durations) > 0 ? array_sum($durations) / count($durations) : 0;
        $medianDuration = $this->median($durations);
        $averageResponses = count($responseCounts) > 0 ? array_sum($responseCounts) / count($responseCounts) : 0;

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
            $completedActivities = $this->excludeOfficeIp(ActivityEvent::query())
                ->where('lesson_id', $lesson->id)
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
            $sessionDurations = $this->excludeOfficeIp(Response::query())
                ->select('session_id')
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
                ->where(function ($q) {
                    $q->whereNull('ip_address')
                      ->orWhere('ip_address', '!=', self::OFFICE_IP);
                })
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
        $activityEvents = $this->excludeOfficeIp(ActivityEvent::query())
            ->orderByDesc('created_at')
            ->get();

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
            $sessionsFromResponses = $this->excludeOfficeIp(Response::query())
                ->whereDate('created_at', $key)
                ->whereNotNull('session_id')
                ->distinct('session_id')
                ->pluck('session_id');
            
            // Get unique sessions from ActivityEvent table for this day
            $sessionsFromActivities = $this->excludeOfficeIp(ActivityEvent::query())
                ->whereDate('created_at', $key)
                ->whereNotNull('session_id')
                ->distinct('session_id')
                ->pluck('session_id');
            
            // Merge and count unique sessions (removing duplicates)
            $uniqueSessions = $sessionsFromResponses->merge($sessionsFromActivities)->unique()->count();
            
            // Calculate total time spent (in seconds)
            $totalTimeSeconds = 0;
            
            // Time from completed activities (games) - sum duration_seconds from meta
            $completedActivities = $this->excludeOfficeIp(ActivityEvent::query())
                ->whereDate('created_at', $key)
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
            $sessionDurations = $this->excludeOfficeIp(Response::query())
                ->select('session_id')
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
                ->where(function ($q) {
                    $q->whereNull('ip_address')
                      ->orWhere('ip_address', '!=', self::OFFICE_IP);
                })
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


@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
@php
    use Illuminate\Support\Str;

    $formatDuration = function (?int $seconds): string {
        if (!$seconds || $seconds <= 0) {
            return '—';
        }
        $minutes = intdiv($seconds, 60);
        $remaining = $seconds % 60;
        if ($minutes === 0) {
            return "{$remaining}s";
        }
        $hours = intdiv($minutes, 60);
        $minutes = $minutes % 60;
        $parts = [];
        if ($hours > 0) {
            $parts[] = "{$hours}h";
        }
        if ($minutes > 0) {
            $parts[] = "{$minutes}m";
        }
        if ($remaining > 0 && $hours === 0) {
            $parts[] = "{$remaining}s";
        }
        return implode(' ', $parts);
    };
@endphp

<div class="container">
    <h1 class="page-title">Practice Analytics</h1>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">{{ number_format($stats['total_responses']) }}</div>
            <div class="stat-label">Total Prompt Responses</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ number_format($stats['unique_sessions']) }}</div>
            <div class="stat-label">Unique Students</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ number_format($stats['responses_last_7_days']) }}</div>
            <div class="stat-label">Prompt Responses · Last 7 Days</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ number_format($stats['active_sessions_today']) }}</div>
            <div class="stat-label">Students Active Today</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $formatDuration($stats['average_session_duration']) }}</div>
            <div class="stat-label">Avg Session Length</div>
            <div class="stat-subtle">Median {{ $formatDuration($stats['median_session_duration']) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ number_format($stats['total_activity_completions']) }}</div>
            <div class="stat-label">Activities Completed</div>
            <div class="stat-subtle">{{ $stats['average_responses_per_session'] }} prompt responses / session</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ number_format($stats['mobile_sessions']) }}</div>
            <div class="stat-label">Mobile Users</div>
            <div class="stat-subtle">{{ $stats['mobile_percentage'] }}% of total</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ number_format($stats['desktop_sessions']) }}</div>
            <div class="stat-label">Desktop Users</div>
            <div class="stat-subtle">{{ $stats['desktop_percentage'] }}% of total</div>
        </div>
    </div>

    <div class="dashboard-sections">
        <div class="dashboard-section wide">
            <h2>Daily Practice (last 14 days)</h2>
            @if(empty($dailyPractice))
                <p class="empty-text">No student activity recorded yet.</p>
            @else
                <table class="table compact">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th class="text-right">Total Time</th>
                            <th class="text-right">Unique Students</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dailyPractice as $day)
                            <tr>
                                <td>{{ $day['date'] }}</td>
                                <td class="text-right">{{ $formatDuration($day['total_time_seconds']) }}</td>
                                <td class="text-right">{{ $day['unique_sessions'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="dashboard-section">
            <h2>Most Practiced Lessons</h2>
            @if($lessonStats['top_lessons']->isEmpty())
                <p class="empty-text">No lessons have activity recorded yet.</p>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>Lesson</th>
                            <th class="text-right">Unique Sessions</th>
                            <th class="text-right">Avg Time/Session</th>
                            <th class="text-right">Total Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lessonStats['top_lessons'] as $lessonStat)
                            <tr>
                                <td>
                                    <strong>{{ $lessonStat['lesson']->title }}</strong>
                                    <div class="muted-text">{{ $lessonStat['lesson']->slug }}</div>
                                </td>
                                <td class="text-right">{{ number_format($lessonStat['unique_sessions']) }}</td>
                                <td class="text-right">{{ $formatDuration($lessonStat['average_time_per_session']) }}</td>
                                <td class="text-right">{{ $formatDuration($lessonStat['time_seconds']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            @if($lessonStats['lessons_without_responses'] > 0)
                <p class="muted-text">{{ $lessonStats['lessons_without_responses'] }} lessons have no activity recorded yet.</p>
            @endif
        </div>

        <div class="dashboard-section">
            <h2>Activity Conversion · Matching Games</h2>
            @if($activityStats['matching']->isEmpty())
                <p class="empty-text">No matching games have been played yet.</p>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>Game</th>
                            <th class="text-right">Started</th>
                            <th class="text-right">Completed</th>
                            <th class="text-right">Conversion</th>
                            <th class="text-right">Avg Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activityStats['matching'] as $stat)
                            <tr>
                                <td>
                                    <strong>{{ $stat['activity']->title }}</strong>
                                    @if($stat['lesson'])
                                        <div class="muted-text">{{ $stat['lesson']->title }}</div>
                                    @endif
                                </td>
                                <td class="text-right">{{ number_format($stat['started']) }}</td>
                                <td class="text-right">{{ number_format($stat['completed']) }}</td>
                                <td class="text-right">{{ $stat['conversion_rate'] !== null ? $stat['conversion_rate'] . '%' : '—' }}</td>
                                <td class="text-right">{{ $formatDuration($stat['average_duration']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="dashboard-section">
            <h2>Activity Conversion · Flashcard Games</h2>
            @if($activityStats['flashcard']->isEmpty())
                <p class="empty-text">No flashcard games have been played yet.</p>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>Game</th>
                            <th class="text-right">Started</th>
                            <th class="text-right">Completed</th>
                            <th class="text-right">Conversion</th>
                            <th class="text-right">Avg Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activityStats['flashcard'] as $stat)
                            <tr>
                                <td>
                                    <strong>{{ $stat['activity']->title }}</strong>
                                    @if($stat['lesson'])
                                        <div class="muted-text">{{ $stat['lesson']->title }}</div>
                                    @endif
                                </td>
                                <td class="text-right">{{ number_format($stat['started']) }}</td>
                                <td class="text-right">{{ number_format($stat['completed']) }}</td>
                                <td class="text-right">{{ $stat['conversion_rate'] !== null ? $stat['conversion_rate'] . '%' : '—' }}</td>
                                <td class="text-right">{{ $formatDuration($stat['average_duration']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="dashboard-section">
            <h2>Recent Lessons</h2>
            @if($recentLessons->isEmpty())
                <p class="empty-text">No lessons yet.</p>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Slug</th>
                            <th>Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentLessons as $lesson)
                            <tr>
                                <td>{{ $lesson->title }}</td>
                                <td>{{ $lesson->slug }}</td>
                                <td>{{ $lesson->is_active ? 'Yes' : 'No' }}</td>
                                <td>
                                    <a href="{{ route('admin.lessons.show', $lesson) }}" class="btn btn-sm">View</a>
                                    <a href="{{ route('admin.lessons.edit', $lesson) }}" class="btn btn-sm">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            <a href="{{ route('admin.lessons.create') }}" class="btn btn-primary">Create New Lesson</a>
        </div>

        <div class="dashboard-section">
            <h2>Recent Prompt Responses</h2>
            @if($recentResponses->isEmpty())
                <p class="empty-text">No prompt responses yet.</p>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>Lesson</th>
                            <th>Prompt</th>
                            <th>Option</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentResponses as $response)
                            <tr>
                                <td>{{ $response->lesson->title }}</td>
                                <td>{{ Str::limit($response->prompt->prompt_text, 60) }}</td>
                                <td>{{ $response->option->label }}</td>
                                <td>{{ $response->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="dashboard-section">
            <h2>Users by Country</h2>
            @if(empty($countryStats['top_countries']))
                <p class="empty-text">No country data available yet.</p>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>Country</th>
                            <th class="text-right">Unique Sessions</th>
                            <th class="text-right">Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($countryStats['top_countries'] as $countryCode => $sessionCount)
                            <tr>
                                <td>
                                    <strong>{{ $countryCode }}</strong>
                                    @php
                                        $countryNames = [
                                            'US' => 'United States',
                                            'CA' => 'Canada',
                                            'GB' => 'United Kingdom',
                                            'AU' => 'Australia',
                                            'DE' => 'Germany',
                                            'FR' => 'France',
                                            'ES' => 'Spain',
                                            'IT' => 'Italy',
                                            'NL' => 'Netherlands',
                                            'BR' => 'Brazil',
                                            'MX' => 'Mexico',
                                            'IN' => 'India',
                                            'CN' => 'China',
                                            'JP' => 'Japan',
                                            'KR' => 'South Korea',
                                            'IL' => 'Israel',
                                        ];
                                        $countryName = $countryNames[$countryCode] ?? null;
                                    @endphp
                                    @if($countryName)
                                        <div class="muted-text">{{ $countryName }}</div>
                                    @endif
                                </td>
                                <td class="text-right">{{ number_format($sessionCount) }}</td>
                                <td class="text-right">
                                    @if($countryStats['total_sessions'] > 0)
                                        {{ number_format(($sessionCount / $countryStats['total_sessions']) * 100, 1) }}%
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($countryStats['total_sessions'] > 0)
                    <p class="muted-text">Total sessions with country data: {{ number_format($countryStats['total_sessions']) }}</p>
                @endif
            @endif
        </div>

        <div class="dashboard-section">
            <h2>Users in Israel by City</h2>
            @if(empty($israelCityStats['cities']))
                <p class="empty-text">No Israeli city data available yet.</p>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>City</th>
                            <th>Region</th>
                            <th class="text-right">Unique Sessions</th>
                            <th class="text-right">Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($israelCityStats['cities'] as $city => $data)
                            <tr>
                                <td><strong>{{ $city }}</strong></td>
                                <td>
                                    @if($data['region'])
                                        <span class="muted-text">{{ $data['region'] }}</span>
                                    @else
                                        <span class="muted-text">—</span>
                                    @endif
                                </td>
                                <td class="text-right">{{ number_format($data['sessions']) }}</td>
                                <td class="text-right">
                                    @if($israelCityStats['total_sessions'] > 0)
                                        {{ number_format(($data['sessions'] / $israelCityStats['total_sessions']) * 100, 1) }}%
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($israelCityStats['total_sessions'] > 0)
                    <p class="muted-text">Total Israeli sessions: {{ number_format($israelCityStats['total_sessions']) }}</p>
                @endif
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}
.stat-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
}
.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-primary);
}
.stat-label {
    font-size: 0.95rem;
    color: var(--color-text-light);
    margin-top: 0.25rem;
}
.stat-subtle {
    font-size: 0.8rem;
    color: var(--color-text-muted);
    margin-top: 0.35rem;
}
.dashboard-sections {
    display: grid;
    gap: 2rem;
}
.dashboard-section {
    background: #ffffff;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
}
.dashboard-section.wide {
    grid-column: span 2;
}
.table.compact td,
.table.compact th {
    padding: 0.5rem 0.75rem;
}
.text-right {
    text-align: right;
}
.muted-text {
    font-size: 0.85rem;
    color: var(--color-text-muted);
}
@media (max-width: 1024px) {
    .dashboard-section.wide {
        grid-column: span 1;
    }
}
</style>
@endpush
@endsection


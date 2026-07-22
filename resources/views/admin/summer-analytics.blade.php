@extends('layouts.admin')

@section('title', 'Summer Program Analytics')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-6xl">
    <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Summer Program Analytics</h1>
            <p class="text-sm text-gray-600 mt-1">{{ $organization->name }} — signups, logins, and practice sessions</p>
        </div>
        <form method="GET" action="{{ route('org.admin.summer-analytics', $organization) }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="start_date" class="block text-xs font-medium text-gray-600 mb-1">Start</label>
                <input type="date" name="start_date" id="start_date" value="{{ $startDate->format('Y-m-d') }}"
                       class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="end_date" class="block text-xs font-medium text-gray-600 mb-1">End</label>
                <input type="date" name="end_date" id="end_date" value="{{ $endDate->format('Y-m-d') }}"
                       class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <button type="submit" class="rounded-lg bg-blue-600 text-white px-4 py-2 text-sm font-medium hover:bg-blue-700">Apply</button>
        </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-gray-200/80 p-4 shadow-sm">
            <div class="text-2xl font-semibold text-gray-900">{{ number_format($signupParentTotal) }}</div>
            <div class="text-sm text-gray-600">Parent signups</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200/80 p-4 shadow-sm">
            <div class="text-2xl font-semibold text-gray-900">{{ number_format($signupChildrenTotal) }}</div>
            <div class="text-sm text-gray-600">Children registered</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200/80 p-4 shadow-sm">
            <div class="text-2xl font-semibold text-gray-900">{{ number_format($loginEventTotal) }}</div>
            <div class="text-sm text-gray-600">Logins</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200/80 p-4 shadow-sm">
            <div class="text-2xl font-semibold text-gray-900">{{ number_format($visitTotal) }}</div>
            <div class="text-sm text-gray-600">Practice sessions</div>
        </div>
    </div>

    {{-- Signups --}}
    <section class="bg-white rounded-xl border border-gray-200/80 shadow-sm mb-8">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Daily signups</h2>
            <a href="{{ route('org.admin.summer-analytics.export-signups', array_merge(['organization' => $organization], request()->only('start_date', 'end_date'))) }}"
               class="text-sm text-blue-600 hover:text-blue-800 font-medium">Export CSV</a>
        </div>
        <div class="p-5">
            @if($dailySignups->isEmpty())
                <p class="text-sm text-gray-500">No parent signups in this date range.</p>
            @else
                <div class="space-y-6">
                    @foreach($dailySignups as $day)
                        <div>
                            <div class="flex flex-wrap items-baseline gap-3 mb-2">
                                <h3 class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($day['date'])->format('M j, Y') }}</h3>
                                <span class="text-sm text-gray-500">
                                    {{ $day['parent_count'] }} parent{{ $day['parent_count'] === 1 ? '' : 's' }},
                                    {{ $day['children_count'] }} child{{ $day['children_count'] === 1 ? '' : 'ren' }}
                                </span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-500 border-b border-gray-100">
                                            <th class="py-2 pr-4 font-medium">Parent</th>
                                            <th class="py-2 pr-4 font-medium">Email</th>
                                            <th class="py-2 font-medium">Children</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($day['parents'] as $parent)
                                            <tr class="border-b border-gray-50">
                                                <td class="py-2 pr-4 text-gray-900">{{ $parent['name'] }}</td>
                                                <td class="py-2 pr-4 text-gray-600">{{ $parent['email'] }}</td>
                                                <td class="py-2 text-gray-900">{{ $parent['children_count'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- Logins --}}
    <section class="bg-white rounded-xl border border-gray-200/80 shadow-sm mb-8">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Daily logins</h2>
            <a href="{{ route('org.admin.summer-analytics.export-logins', array_merge(['organization' => $organization], request()->only('start_date', 'end_date'))) }}"
               class="text-sm text-blue-600 hover:text-blue-800 font-medium">Export CSV</a>
        </div>
        <div class="p-5">
            @if($dailyLogins->isEmpty())
                <p class="text-sm text-gray-500">No logins recorded in this date range.</p>
            @else
                <div class="space-y-6">
                    @foreach($dailyLogins as $day)
                        <div>
                            <div class="flex flex-wrap items-baseline gap-3 mb-2">
                                <h3 class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($day['date'])->format('M j, Y') }}</h3>
                                <span class="text-sm text-gray-500">
                                    {{ $day['login_count'] }} login{{ $day['login_count'] === 1 ? '' : 's' }},
                                    {{ $day['unique_users'] }} unique
                                </span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-500 border-b border-gray-100">
                                            <th class="py-2 pr-4 font-medium">Who</th>
                                            <th class="py-2 pr-4 font-medium">Email</th>
                                            <th class="py-2 pr-4 font-medium">Detail</th>
                                            <th class="py-2 font-medium">Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($day['logins'] as $login)
                                            <tr class="border-b border-gray-50">
                                                <td class="py-2 pr-4 text-gray-900">{{ $login['name'] }}</td>
                                                <td class="py-2 pr-4 text-gray-600">{{ $login['email'] ?? '—' }}</td>
                                                <td class="py-2 pr-4 text-gray-600">{{ $login['detail'] ?? '—' }}</td>
                                                <td class="py-2 text-gray-600">{{ \Carbon\Carbon::parse($login['logged_in_at'])->format('g:i A') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- Sessions --}}
    <section class="bg-white rounded-xl border border-gray-200/80 shadow-sm mb-8">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Practice sessions</h2>
            <a href="{{ route('org.admin.summer-analytics.export-visits', array_merge(['organization' => $organization], request()->only('start_date', 'end_date'))) }}"
               class="text-sm text-blue-600 hover:text-blue-800 font-medium">Export CSV</a>
        </div>
        <div class="p-5">
            @if($visits->isEmpty())
                <p class="text-sm text-gray-500">No practice sessions in this date range.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b border-gray-100">
                                <th class="py-2 pr-4 font-medium">Who</th>
                                <th class="py-2 pr-4 font-medium">Started</th>
                                <th class="py-2 pr-4 font-medium">Duration</th>
                                <th class="py-2 pr-4 font-medium">Lessons</th>
                                <th class="py-2 font-medium">Which lessons</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($visits as $visit)
                                <tr class="border-b border-gray-50 align-top">
                                    <td class="py-3 pr-4">
                                        <div class="text-gray-900 font-medium">{{ $visit['who'] }}</div>
                                        @if($visit['email'])
                                            <div class="text-gray-500 text-xs">{{ $visit['email'] }}</div>
                                        @endif
                                        @if($visit['detail'])
                                            <div class="text-gray-500 text-xs">{{ $visit['detail'] }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-4 text-gray-700 whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($visit['started_at'])->format('M j, g:i A') }}
                                    </td>
                                    <td class="py-3 pr-4 text-gray-700 whitespace-nowrap">
                                        {{ $formatDuration($visit['duration_seconds']) }}
                                        @if(($visit['end_reason'] ?? null) === 'still_open')
                                            <span class="text-xs text-amber-600 block">in progress</span>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-4 text-gray-900">{{ $visit['lesson_count'] }}</td>
                                    <td class="py-3 text-gray-700">
                                        @if(empty($visit['lessons']))
                                            <span class="text-gray-400">—</span>
                                        @else
                                            <ul class="list-disc list-inside space-y-0.5">
                                                @foreach($visit['lessons'] as $lesson)
                                                    <li>{{ $lesson['title'] }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
</div>
@endsection

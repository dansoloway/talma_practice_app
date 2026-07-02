@extends('layouts.app')

@section('title', $course->title . ' - Lessons')

@php
    $courseUrl = isset($org) && $org
        ? route('org.student.course', [$org, $course])
        : route('student.course', $course->slug);
    $backUrl = isset($org) && $org
        ? route('org.student.index', $org)
        : route('student.index');
    $totalLessons = $lessons->count();
@endphp

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-6">
    <div class="container mx-auto px-4 max-w-6xl">
        <header class="flex items-center gap-4 mb-5 min-h-[40px]">
            <a href="{{ $backUrl }}"
               class="inline-flex items-center text-sm text-blue-600 hover:text-blue-700 font-medium shrink-0 transition-colors duration-200">
                <i class="fas fa-arrow-left mr-1.5" aria-hidden="true"></i>
                <span class="hidden sm:inline">{{ __('student-portal.course.back') }}</span>
            </a>

            <h1 class="flex-1 text-center text-lg sm:text-xl font-bold text-gray-800 truncate px-2">
                {{ $course->title }}
            </h1>

            <span class="text-sm text-gray-500 font-medium shrink-0 whitespace-nowrap">
                {{ trans_choice('student-portal.course.lessons_count', $totalLessons, ['count' => $totalLessons]) }}
            </span>
        </header>

        <form id="course-filters"
              method="GET"
              action="{{ $courseUrl }}"
              class="flex flex-wrap items-center gap-2.5 mb-5">
            @if($sessionNumbers->count() > 0)
                <select name="session_number"
                        id="session_number"
                        aria-label="Filter by session"
                        class="h-9 px-3 text-sm border border-gray-300 rounded-lg bg-white text-gray-800 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
                    <option value="">{{ __('student-portal.course.all_sessions') }}</option>
                    @foreach($sessionNumbers as $session)
                        <option value="{{ $session }}" {{ request('session_number') == $session ? 'selected' : '' }}>
                            {{ __('student-portal.course.session', ['number' => $session]) }}
                        </option>
                    @endforeach
                </select>
            @endif

            @if($partNumbers->count() > 0)
                <select name="part_number"
                        id="part_number"
                        aria-label="Filter by part"
                        class="h-9 px-3 text-sm border border-gray-300 rounded-lg bg-white text-gray-800 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
                    <option value="">{{ __('student-portal.course.all_parts') }}</option>
                    @foreach($partNumbers as $part)
                        <option value="{{ $part }}" {{ request('part_number') == $part ? 'selected' : '' }}>
                            {{ __('student-portal.course.part', ['number' => $part]) }}
                        </option>
                    @endforeach
                </select>
            @endif

            <input type="search"
                   name="search"
                   id="search"
                   placeholder="{{ __('student-portal.course.search_placeholder') }}"
                   value="{{ request('search') }}"
                   aria-label="Search lessons"
                   class="flex-1 min-w-[140px] h-9 px-3 text-sm border border-gray-300 rounded-lg bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">

            @if(request()->hasAny(['session_number', 'part_number', 'search']))
                <a href="{{ $courseUrl }}"
                   class="h-9 inline-flex items-center px-3 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                    {{ __('student-portal.course.clear') }}
                </a>
            @endif
        </form>

        @if($lessons->isEmpty() && !request()->hasAny(['session_number', 'part_number', 'search']))
            <div class="bg-white rounded-xl border border-gray-200 p-10 text-center">
                <div class="text-5xl mb-3">📚</div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">{{ __('student-portal.course.no_lessons') }}</h3>
                <p class="text-gray-600 mb-5">{{ __('student-portal.course.check_back') }}</p>
                <a href="{{ $backUrl }}" class="inline-block px-5 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-all duration-200">
                    {{ __('student-portal.course.choose_different') }}
                </a>
            </div>
        @else
            <div id="lessons-list">
                @include('partials.lesson-session-groups', [
                    'lessonGroups' => $lessonGroups,
                    'mode' => 'student',
                    'org' => $org ?? null,
                    'course' => $course,
                    'lessonProgress' => $lessonProgress ?? [],
                    'clearFiltersUrl' => $courseUrl,
                ])
            </div>
        @endif
    </div>
</div>

@push('styles')
<style>
    .lesson-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 10px;
    }

    @media (prefers-reduced-motion: reduce) {
        .lesson-card-compact,
        .lesson-card-compact * {
            transition: none !important;
        }
    }

    a.lesson-card-compact:focus-visible {
        outline: 3px solid #3b82f6;
        outline-offset: 2px;
    }

    #course-filters select:focus-visible,
    #course-filters input:focus-visible {
        outline: 3px solid #3b82f6;
        outline-offset: 2px;
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        const form = document.getElementById('course-filters');
        if (!form) return;

        form.querySelectorAll('select').forEach((select) => {
            select.addEventListener('change', () => form.submit());
        });

        const searchInput = form.querySelector('#search');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => form.submit(), 400);
            });
        }
    })();
</script>
@endpush

@endsection

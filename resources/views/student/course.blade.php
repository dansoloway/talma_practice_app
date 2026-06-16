@extends('layouts.app')

@section('title', $course->title . ' - Lessons')

@php
    $isAdmin = auth('admin')->check();
@endphp

@section('content')
<!-- Warm background with subtle gradient -->
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-8">
    <div class="container mx-auto px-4 max-w-5xl">
        <!-- Header Section - Stronger visual hierarchy -->
        <div class="mb-8">
            <a href="{{ isset($org) && $org ? route('org.student.index', $org) : route('student.index') }}" class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium mb-6 transition-colors duration-200">
                <i class="fas fa-arrow-left mr-2"></i>
                <span>Back to Courses</span>
            </a>
            
            <div class="text-center mb-2">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-3 tracking-tight">
                    {{ $course->title }}
                </h1>
                @if($course->description)
                    <p class="text-lg text-gray-600 font-medium mb-4">
                        {{ $course->description }}
                    </p>
                @endif
                <p class="text-lg text-gray-600 font-medium">
                    Choose a lesson to start practicing
                </p>
            </div>
        </div>

        <!-- Filters Section - Reduced visual weight, secondary feel -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-5 mb-8">
            <form method="GET" action="{{ isset($org) && $org ? route('org.student.course', [$org, $course]) : route('student.course', $course->slug) }}" class="flex flex-wrap gap-4 items-end">
                @if($sessionNumbers->count() > 0)
                    <div class="flex-1 min-w-[150px]">
                        <label for="session_number" class="block text-sm font-semibold text-gray-700 mb-2">Session:</label>
                        <select name="session_number" id="session_number" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-white text-gray-800 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
                            <option value="">All Sessions</option>
                            @foreach($sessionNumbers as $session)
                                <option value="{{ $session }}" {{ request('session_number') == $session ? 'selected' : '' }}>
                                    Session {{ $session }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                
                @if($partNumbers->count() > 0)
                    <div class="flex-1 min-w-[150px]">
                        <label for="part_number" class="block text-sm font-semibold text-gray-700 mb-2">Part:</label>
                        <select name="part_number" id="part_number" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-white text-gray-800 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
                            <option value="">All Parts</option>
                            @foreach($partNumbers as $part)
                                <option value="{{ $part }}" {{ request('part_number') == $part ? 'selected' : '' }}>
                                    Part {{ $part }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                
                <div class="flex-[2] min-w-[200px]">
                    <label for="search" class="block text-sm font-semibold text-gray-700 mb-2">Search:</label>
                    <input type="text" name="search" id="search" 
                           placeholder="Search by title..." 
                           value="{{ request('search') }}" 
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
                </div>
                
                <div class="flex gap-2">
                    <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">
                        Filter
                    </button>
                    @if(request()->hasAny(['session_number', 'part_number', 'search']))
                        <a href="{{ isset($org) && $org ? route('org.student.course', [$org, $course]) : route('student.course', $course->slug) }}" class="px-5 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 active:scale-95 transition-all duration-200">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        @if(request()->hasAny(['session_number', 'part_number', 'search']))
            <div class="mb-6 text-center">
                <p class="text-gray-600 font-medium">
                    Showing {{ $lessons->count() }} lesson{{ $lessons->count() !== 1 ? 's' : '' }} matching your filters
                </p>
            </div>
        @endif

        @if($lessons->isEmpty() && !request()->hasAny(['session_number', 'part_number', 'search']))
            <div class="bg-white rounded-2xl border-2 border-gray-200 p-12 text-center">
                <div class="text-6xl mb-4">📚</div>
                <h3 class="text-2xl font-bold text-gray-800 mb-3">No lessons available for this course</h3>
                <p class="text-gray-600 mb-6">Please check back later for new lessons!</p>
                <a href="{{ isset($org) && $org ? route('org.student.index', $org) : route('student.index') }}" class="inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200 shadow-sm hover:shadow-md">
                    Choose Different Course
                </a>
            </div>
        @else
            <!-- Lessons grouped by session -->
            <div id="lessons-list">
                @include('partials.lesson-session-groups', [
                    'lessonGroups' => $lessonGroups,
                    'mode' => 'student',
                    'org' => $org ?? null,
                    'course' => $course,
                    'clearFiltersUrl' => isset($org) && $org
                        ? route('org.student.course', [$org, $course])
                        : route('student.course', $course->slug),
                ])
            </div>
        @endif
    </div>
</div>

@push('styles')
<style>
    /* Ensure smooth transitions and maintain accessibility */
    @media (prefers-reduced-motion: reduce) {
        * {
            transition: none !important;
            animation: none !important;
        }
    }
    
    /* Focus states for accessibility */
    a:focus-visible {
        outline: 3px solid #3b82f6;
        outline-offset: 2px;
        border-radius: 1rem;
    }
    
    button:focus-visible,
    select:focus-visible,
    input:focus-visible {
        outline: 3px solid #3b82f6;
        outline-offset: 2px;
    }
</style>
@endpush

@endsection

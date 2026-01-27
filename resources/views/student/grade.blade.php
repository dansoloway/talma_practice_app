@extends('layouts.app')

@section('title', 'Grade ' . $gradeLevel . ' Lessons')

@php
    $isAdmin = session('admin_authenticated', false) === true;
@endphp

@section('content')
<!-- Warm background with subtle gradient -->
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-8">
    <div class="container mx-auto px-4 max-w-5xl">
        <!-- Header Section - Stronger visual hierarchy -->
        <div class="mb-8">
            <a href="{{ route('student.index') }}" class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium mb-6 transition-colors duration-200">
                <i class="fas fa-arrow-left mr-2"></i>
                <span>Back to Grades</span>
            </a>
            
            <div class="text-center mb-2">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-3 tracking-tight">
                    Grade {{ $gradeLevel }} Lessons
                </h1>
                <p class="text-lg text-gray-600 font-medium">
                    Choose a lesson to start practicing
                </p>
            </div>
        </div>

        <!-- Filters Section - Reduced visual weight, secondary feel -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-5 mb-8">
            <form method="GET" action="{{ route('student.grade', $gradeLevel) }}" class="flex flex-wrap gap-4 items-end">
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
                        <a href="{{ route('student.grade', $gradeLevel) }}" class="px-5 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 active:scale-95 transition-all duration-200">
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

        <!-- Lessons Grid - Interactive tiles with warmth -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5" id="lessons-list">
            @forelse($lessons as $lesson)
                <a href="{{ route('lessons.show', $lesson->slug) }}" 
                   class="group relative bg-white rounded-2xl border-2 border-gray-200 p-6 shadow-sm hover:shadow-xl hover:border-blue-300 hover:-translate-y-1 transition-all duration-300 cursor-pointer block">
                    
                    <!-- Card Content -->
                    <div class="flex flex-col h-full">
                        <!-- Header Row: Badges + Arrow -->
                        <div class="flex justify-between items-start mb-4">
                            @if($lesson->session_number || $lesson->part_number)
                                <div class="flex flex-wrap gap-2">
                                    @if($lesson->session_number)
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 border border-blue-200">
                                            Session {{ $lesson->session_number }}
                                        </span>
                                    @endif
                                    @if($lesson->part_number)
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700 border border-purple-200">
                                            Part {{ $lesson->part_number }}
                                        </span>
                                    @endif
                                </div>
                            @else
                                <div></div>
                            @endif
                            
                            <!-- Chevron Arrow - More intentional -->
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-50 group-hover:bg-blue-100 flex items-center justify-center transition-all duration-300 group-hover:translate-x-1">
                                <i class="fas fa-chevron-right text-blue-600 text-sm"></i>
                            </div>
                        </div>
                        
                        <!-- Lesson Title - Clear focal point -->
                        <h3 class="text-xl font-bold text-gray-800 mb-4 group-hover:text-blue-700 transition-colors duration-200 leading-tight">
                            {{ $lesson->title }}
                        </h3>
                        
                        <!-- Lesson Stats - Subtle and informative -->
                        <div class="flex flex-wrap gap-4 mt-auto pt-4 border-t border-gray-100">
                            @if($lesson->vocabulary && $lesson->vocabulary->count() > 0)
                                <span class="inline-flex items-center text-sm text-gray-600">
                                    <i class="fas fa-book mr-2 text-blue-500"></i>
                                    <span class="font-medium">{{ $lesson->vocabulary->count() }}</span>
                                    <span class="ml-1">words</span>
                                </span>
                            @endif
                            
                            @php
                                $activityCount = $lesson->prompts->count() + $lesson->matchingGames->count() + $lesson->flashcardGames->count();
                            @endphp
                            
                            @if($activityCount > 0)
                                <span class="inline-flex items-center text-sm text-gray-600">
                                    <i class="fas fa-gamepad mr-2 text-purple-500"></i>
                                    <span class="font-medium">{{ $activityCount }}</span>
                                    <span class="ml-1">activities</span>
                                </span>
                            @endif
                        </div>
                    </div>
                </a>
            @empty
                <div class="col-span-full">
                    <div class="bg-white rounded-2xl border-2 border-gray-200 p-12 text-center">
                        @if(request()->hasAny(['session_number', 'part_number', 'search']))
                            <div class="text-6xl mb-4">🔍</div>
                            <h3 class="text-2xl font-bold text-gray-800 mb-3">No lessons found</h3>
                            <p class="text-gray-600 mb-6">No lessons match your current filters. Try adjusting your search criteria.</p>
                            <a href="{{ route('student.grade', $gradeLevel) }}" class="inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200 shadow-sm hover:shadow-md">
                                Clear All Filters
                            </a>
                        @else
                            <div class="text-6xl mb-4">📚</div>
                            <h3 class="text-2xl font-bold text-gray-800 mb-3">No lessons available for Grade {{ $gradeLevel }}</h3>
                            <p class="text-gray-600 mb-6">Please check back later for new lessons!</p>
                            <a href="{{ route('student.index') }}" class="inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200 shadow-sm hover:shadow-md">
                                Choose Different Grade
                            </a>
                        @endif
                    </div>
                </div>
            @endforelse
        </div>
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

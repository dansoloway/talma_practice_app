@extends('layouts.app')

@section('title', 'Grade ' . $gradeLevel . ' Lessons')

@php
    $isAdmin = session('admin_authenticated', false) === true;
@endphp

@section('content')
<div class="container">
    <div class="student-grade-page">
        <div class="grade-header">
            <a href="{{ route('student.index') }}" class="back-link">← Back to Grades</a>
            <div class="header-content">
                <div>
                    <h1 class="grade-title">Grade {{ $gradeLevel }} Lessons</h1>
                    <p class="grade-subtitle">Choose a lesson to start practicing</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section" style="background: var(--color-white); padding: 1.5rem; border-radius: var(--radius-lg); border: 2px solid var(--color-border); margin-bottom: 2rem;">
            <form method="GET" action="{{ route('student.grade', $gradeLevel) }}" class="filters-form" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
                @if($sessionNumbers->count() > 0)
                    <div class="filter-group" style="flex: 1; min-width: 150px;">
                        <label for="session_number" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-text-dark);">Session:</label>
                        <select name="session_number" id="session_number" class="form-control" style="width: 100%;">
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
                    <div class="filter-group" style="flex: 1; min-width: 150px;">
                        <label for="part_number" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-text-dark);">Part:</label>
                        <select name="part_number" id="part_number" class="form-control" style="width: 100%;">
                            <option value="">All Parts</option>
                            @foreach($partNumbers as $part)
                                <option value="{{ $part }}" {{ request('part_number') == $part ? 'selected' : '' }}>
                                    Part {{ $part }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                
                <div class="filter-group" style="flex: 2; min-width: 200px;">
                    <label for="search" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-text-dark);">Search:</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="Search by title..." value="{{ request('search') }}" style="width: 100%;">
                </div>
                
                <div class="filter-actions" style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    @if(request()->hasAny(['session_number', 'part_number', 'search']))
                        <a href="{{ route('student.grade', $gradeLevel) }}" class="btn btn-secondary btn-sm">Clear</a>
                    @endif
                </div>
            </form>
        </div>

        @if(request()->hasAny(['session_number', 'part_number', 'search']))
            <div class="results-info" style="margin-bottom: 1rem; color: var(--color-text-light);">
                <p>Showing {{ $lessons->count() }} lesson{{ $lessons->count() !== 1 ? 's' : '' }} matching your filters</p>
            </div>
        @endif

        <div class="lessons-list" id="lessons-list">
            @forelse($lessons as $lesson)
                <div class="lesson-card-wrapper" data-lesson-id="{{ $lesson->id }}">
                    <div class="lesson-card">
                        <a href="{{ route('lessons.show', $lesson->slug) }}" class="lesson-card-link">
                            <div class="lesson-content">
                                <div class="lesson-header-row">
                                    @if($lesson->session_number || $lesson->part_number)
                                        <div class="lesson-badge">
                                            @if($lesson->session_number)
                                                <span class="badge-session">Session {{ $lesson->session_number }}</span>
                                            @endif
                                            @if($lesson->part_number)
                                                <span class="badge-part">Part {{ $lesson->part_number }}</span>
                                            @endif
                                        </div>
                                    @endif
                                    <div class="lesson-arrow">
                                        <i class="fas fa-chevron-right"></i>
                                    </div>
                                </div>
                                <h3 class="lesson-title">{{ $lesson->title }}</h3>
                                
                                <div class="lesson-stats">
                                    @if($lesson->vocabulary && $lesson->vocabulary->count() > 0)
                                        <span class="stat">
                                            <i class="fas fa-book"></i>
                                            {{ $lesson->vocabulary->count() }} words
                                        </span>
                                    @endif
                                    
                                    @php
                                        $activityCount = $lesson->prompts->count() + $lesson->matchingGames->count() + $lesson->flashcardGames->count();
                                    @endphp
                                    
                                    @if($activityCount > 0)
                                        <span class="stat">
                                            <i class="fas fa-gamepad"></i>
                                            {{ $activityCount }} activities
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    @if(request()->hasAny(['session_number', 'part_number', 'search']))
                        <h3>No lessons found</h3>
                        <p>No lessons match your current filters. Try adjusting your search criteria or <a href="{{ route('student.grade', $gradeLevel) }}">clear all filters</a>.</p>
                    @else
                        <h3>No lessons available for Grade {{ $gradeLevel }}</h3>
                        <p>Please check back later for new lessons!</p>
                        <a href="{{ route('student.index') }}" class="btn btn-primary">Choose Different Grade</a>
                    @endif
                </div>
            @endforelse
        </div>
    </div>
</div>

@push('styles')
<style>
    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .lesson-card-wrapper {
        margin-bottom: 1rem;
    }

    /* Override the base .lesson-card styles since we changed the structure */
    .lessons-list .lesson-card {
        display: block;
        padding: 0;
        background: transparent;
        border: none;
        border-radius: 0;
        box-shadow: none;
        position: relative;
    }

    .lessons-list .lesson-card:hover {
        transform: none;
        box-shadow: none;
        border-color: transparent;
    }

    .lesson-card-link {
        display: flex;
        align-items: center;
        padding: 1.5rem;
        background: var(--color-white);
        border: 2px solid var(--color-border);
        border-radius: var(--radius-lg);
        text-decoration: none;
        color: inherit;
        transition: var(--transition-fast);
        box-shadow: var(--shadow-sm);
        width: 100%;
    }

    .lesson-card-link:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        border-color: var(--color-primary-light);
        text-decoration: none;
    }

</style>
@endpush

@endsection

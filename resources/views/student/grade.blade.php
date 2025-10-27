@extends('layouts.app')

@section('title', 'Grade ' . $gradeLevel . ' Lessons')

@section('content')
<div class="container">
    <div class="student-grade-page">
        <div class="grade-header">
            <a href="{{ route('student.index') }}" class="back-link">← Back to Grades</a>
            <h1 class="grade-title">Grade {{ $gradeLevel }} Lessons</h1>
            <p class="grade-subtitle">Choose a lesson to start practicing</p>
        </div>

        <div class="lessons-list">
            @forelse($lessons as $lesson)
                <a href="{{ route('lessons.show', $lesson->slug) }}" class="lesson-card">
                    <div class="lesson-session">
                        @if($lesson->session_number)
                            Session {{ $lesson->session_number }}
                        @else
                            Lesson
                        @endif
                    </div>
                    <div class="lesson-content">
                        <h3 class="lesson-title">{{ $lesson->title }}</h3>
                        @if($lesson->session_title)
                            <p class="lesson-session-title">{{ $lesson->session_title }}</p>
                        @endif
                        @if($lesson->instructions)
                            <p class="lesson-description">{{ Str::limit($lesson->instructions, 100) }}</p>
                        @endif
                        
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
                    <div class="lesson-arrow">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
            @empty
                <div class="empty-state">
                    <h3>No lessons available for Grade {{ $gradeLevel }}</h3>
                    <p>Please check back later for new lessons!</p>
                    <a href="{{ route('student.index') }}" class="btn btn-primary">Choose Different Grade</a>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

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
                @if($isAdmin)
                    <button id="toggle-sort-mode" class="btn btn-secondary btn-sm">
                        <i class="fas fa-grip-vertical"></i> Sort Lessons
                    </button>
                @endif
            </div>
        </div>

        <div class="lessons-list" id="lessons-list">
            @forelse($lessons as $lesson)
                <div class="lesson-card-wrapper" data-lesson-id="{{ $lesson->id }}">
                    <div class="lesson-card {{ $isAdmin ? 'sortable' : '' }}">
                        @if($isAdmin)
                            <div class="drag-handle">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                        @endif
                        <a href="{{ route('lessons.show', $lesson->slug) }}" class="lesson-card-link">
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
                    </div>
                </div>
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

@if($isAdmin)
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

        /* Reduce spacing between cards in sort mode */
        #lessons-list.sort-mode .lesson-card-wrapper {
            margin-bottom: 0.5rem;
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

        .lesson-card.sort-mode {
            cursor: move;
        }

        .lesson-card.sort-mode .lesson-card-link {
            pointer-events: none;
            padding: 0.75rem 1rem;
            padding-left: 3rem;
        }

        /* Compact styling for sort mode */
        .lesson-card.sort-mode .lesson-session {
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            margin-right: 1rem;
        }

        .lesson-card.sort-mode .lesson-title {
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .lesson-card.sort-mode .lesson-session-title {
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }

        .lesson-card.sort-mode .lesson-description {
            font-size: 0.8rem;
            display: none; /* Hide description in sort mode */
        }

        .lesson-card.sort-mode .lesson-stats {
            display: none; /* Hide stats in sort mode */
        }

        .lesson-card.sort-mode .lesson-arrow {
            font-size: 0.9rem;
            margin-left: 0.5rem;
        }

        .drag-handle {
            position: absolute;
            left: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--color-text-muted);
            font-size: 1rem;
            padding: 0.25rem;
            cursor: grab;
            z-index: 10;
            display: none;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: var(--radius-sm);
            transition: all 0.2s ease;
        }

        .drag-handle:active {
            cursor: grabbing;
        }

        .lesson-card.sort-mode .drag-handle {
            display: flex;
        }

        .lesson-card.sort-mode .drag-handle:hover {
            background-color: var(--color-gray-100);
            color: var(--color-primary);
        }

        .sortable-ghost {
            opacity: 0.4;
            background-color: var(--color-primary-bg);
        }

        .sortable-drag {
            opacity: 0.8;
            transform: rotate(2deg);
        }

        #toggle-sort-mode.active {
            background-color: var(--color-primary);
            color: white;
        }

        .save-order-btn {
            margin-top: 1rem;
            display: none;
        }

        .save-order-btn.visible {
            display: inline-block;
        }
    </style>
    @endpush

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleSortBtn = document.getElementById('toggle-sort-mode');
            const lessonsList = document.getElementById('lessons-list');
            let sortable = null;
            let sortModeActive = false;
            let hasChanges = false;

            if (!toggleSortBtn || !lessonsList) return;

            toggleSortBtn.addEventListener('click', function() {
                sortModeActive = !sortModeActive;
                
                if (sortModeActive) {
                    // Enable sort mode
                    toggleSortBtn.classList.add('active');
                    toggleSortBtn.innerHTML = '<i class="fas fa-check"></i> Done Sorting';
                    
                    // Add sort-mode class to lessons list container
                    lessonsList.classList.add('sort-mode');
                    
                    // Add sort-mode class to all cards
                    document.querySelectorAll('.lesson-card').forEach(card => {
                        card.classList.add('sort-mode');
                    });

                    // Initialize Sortable
                    if (!sortable) {
                        sortable = new Sortable(lessonsList, {
                            handle: '.drag-handle',
                            animation: 150,
                            ghostClass: 'sortable-ghost',
                            dragClass: 'sortable-drag',
                            onEnd: function() {
                                hasChanges = true;
                            }
                        });
                    }
                } else {
                    // Disable sort mode
                    toggleSortBtn.classList.remove('active');
                    toggleSortBtn.innerHTML = '<i class="fas fa-grip-vertical"></i> Sort Lessons';
                    
                    // Remove sort-mode class from lessons list container
                    lessonsList.classList.remove('sort-mode');
                    
                    // Remove sort-mode class from all cards
                    document.querySelectorAll('.lesson-card').forEach(card => {
                        card.classList.remove('sort-mode');
                    });

                    // Save order if changes were made
                    if (hasChanges) {
                        saveOrder();
                        hasChanges = false;
                    }
                }
            });

            function saveOrder() {
                const lessonIds = Array.from(lessonsList.querySelectorAll('.lesson-card-wrapper'))
                    .map(wrapper => wrapper.dataset.lessonId);

                fetch('{{ route("student.grade.update-order", $gradeLevel) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        lesson_ids: lessonIds
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-success';
                        alert.textContent = 'Lesson order saved successfully!';
                        alert.style.position = 'fixed';
                        alert.style.top = '20px';
                        alert.style.right = '20px';
                        alert.style.zIndex = '9999';
                        document.body.appendChild(alert);
                        
                        setTimeout(() => {
                            alert.remove();
                        }, 3000);
                    } else {
                        throw new Error(data.error || 'Failed to save order');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to save lesson order. Please try again.');
                });
            }
        });
    </script>
    @endpush
@endif
@endsection

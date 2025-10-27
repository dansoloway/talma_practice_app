@extends('layouts.admin')

@section('title', 'Student Preview: ' . $lesson->title)

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.lessons.index') }}" class="back-link">&larr; Back to Lessons</a>
            <h1 class="page-title">Student Preview</h1>
            <p class="page-subtitle">{{ $lesson->title }}</p>
        </div>
        <div>
            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn btn-primary">Manage Lesson</a>
            <a href="{{ route('lessons.show', $lesson->slug) }}" class="btn btn-success" target="_blank">Play as Student</a>
        </div>
    </div>

    <!-- Student View Preview -->
    <div class="student-preview">
        <div class="lesson-header">
            <h1 class="lesson-title">{{ $lesson->title }}</h1>
            @if($lesson->session_title)
                <p class="session-info">{{ $lesson->session_title }}</p>
            @endif
            @if($lesson->grade_level)
                <p class="grade-level">Grade {{ $lesson->grade_level }}</p>
            @endif
            @if($lesson->instructions)
                <div class="lesson-instructions">
                    <h3>Instructions:</h3>
                    <p>{{ $lesson->instructions }}</p>
                </div>
            @endif
        </div>

        @php
            // Get all activities in order
            $allActivities = collect();
            
            foreach($lesson->prompts as $prompt) {
                $allActivities->push((object)[
                    'id' => $prompt->id,
                    'type' => 'prompt',
                    'title' => $prompt->prompt_text,
                    'sort_order' => $prompt->sort_order ?? 999,
                    'is_active' => $prompt->is_active ?? true,
                    'model' => $prompt
                ]);
            }
            
            foreach($lesson->matchingGames as $game) {
                $allActivities->push((object)[
                    'id' => $game->id,
                    'type' => 'matching',
                    'title' => $game->title,
                    'sort_order' => $game->sort_order ?? 999,
                    'is_active' => $game->is_active ?? true,
                    'model' => $game
                ]);
            }
            
            foreach($lesson->flashcardGames as $game) {
                $allActivities->push((object)[
                    'id' => $game->id,
                    'type' => 'flashcard',
                    'title' => $game->title,
                    'sort_order' => $game->sort_order ?? 999,
                    'is_active' => $game->is_active ?? true,
                    'model' => $game
                ]);
            }
            
            $allActivities = $allActivities->where('is_active', true)->sortBy('sort_order');
        @endphp

        @if($allActivities->count() > 0)
            <div class="activities-preview">
                <h2>Activities in this lesson:</h2>
                <div class="activities-flow">
                    @foreach($allActivities as $index => $activity)
                        <div class="activity-preview-card">
                            <div class="activity-number">{{ $index + 1 }}</div>
                            <div class="activity-preview-content">
                                <div class="activity-type-badge {{ $activity->type }}">
                                    {{ ucfirst($activity->type) }}
                                </div>
                                <h3 class="activity-preview-title">{{ $activity->title }}</h3>
                                
                                @if($activity->type === 'prompt')
                                    <div class="activity-details">
                                        <p><strong>Template:</strong> {{ $activity->model->template }}</p>
                                        @if($activity->model->options->count() > 0)
                                            <p><strong>Options:</strong> 
                                                @foreach($activity->model->options->take(3) as $option)
                                                    <span class="option-preview">{{ $option->label }}</span>
                                                @endforeach
                                                @if($activity->model->options->count() > 3)
                                                    <span class="more-options">+{{ $activity->model->options->count() - 3 }} more</span>
                                                @endif
                                            </p>
                                        @endif
                                    </div>
                                @elseif($activity->type === 'matching')
                                    <div class="activity-details">
                                        <p><strong>Grid Size:</strong> {{ $activity->model->grid_size }}x{{ $activity->model->grid_size }}</p>
                                        <p><strong>Vocabulary Words:</strong> {{ count($activity->model->vocabulary_ids) }}</p>
                                    </div>
                                @elseif($activity->type === 'flashcard')
                                    <div class="activity-details">
                                        <p><strong>Cards per Game:</strong> {{ $activity->model->cards_per_game }}</p>
                                        <p><strong>Game Types:</strong> 
                                            @foreach($activity->model->game_types as $type)
                                                <span class="game-type-preview">{{ \App\Models\FlashcardGame::getGameTypes()[$type] }}</span>
                                            @endforeach
                                        </p>
                                    </div>
                                @endif
                            </div>
                            
                            @if($index < $allActivities->count() - 1)
                                <div class="activity-arrow">→</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="lesson-stats">
                <div class="stat-card">
                    <div class="stat-number">{{ $allActivities->count() }}</div>
                    <div class="stat-label">Total Activities</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ $lesson->vocabulary->count() }}</div>
                    <div class="stat-label">Vocabulary Words</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">~{{ $allActivities->count() * 2 }}</div>
                    <div class="stat-label">Minutes</div>
                </div>
            </div>
        @else
            <div class="empty-state">
                <h2>No Activities Yet</h2>
                <p>This lesson doesn't have any activities yet. Students won't see anything when they access this lesson.</p>
                <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn btn-primary">Add Activities</a>
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
.student-preview {
    background: var(--color-white);
    border-radius: var(--radius-xl);
    padding: var(--spacing-3xl);
    margin-top: var(--spacing-xl);
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--color-border-light);
}

.lesson-header {
    text-align: center;
    margin-bottom: var(--spacing-3xl);
    padding-bottom: var(--spacing-2xl);
    border-bottom: 2px solid var(--color-border-light);
}

.lesson-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--color-primary);
    margin-bottom: var(--spacing-md);
}

.session-info {
    font-size: 1.25rem;
    color: var(--color-gray-600);
    margin-bottom: var(--spacing-sm);
}

.grade-level {
    font-size: 1rem;
    color: var(--color-gray-500);
    margin-bottom: var(--spacing-lg);
}

.lesson-instructions {
    background: var(--color-primary-bg);
    padding: var(--spacing-xl);
    border-radius: var(--radius-lg);
    border-left: 4px solid var(--color-primary);
    text-align: left;
    max-width: 600px;
    margin: 0 auto;
}

.lesson-instructions h3 {
    color: var(--color-primary-dark);
    margin-bottom: var(--spacing-md);
}

.activities-preview h2 {
    text-align: center;
    color: var(--color-gray-800);
    margin-bottom: var(--spacing-2xl);
}

.activities-flow {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xl);
    margin-bottom: var(--spacing-3xl);
}

.activity-preview-card {
    display: flex;
    align-items: center;
    background: var(--color-gray-50);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-xl);
    padding: var(--spacing-xl);
    position: relative;
}

.activity-number {
    width: 3rem;
    height: 3rem;
    background: var(--color-primary);
    color: var(--color-white);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.25rem;
    margin-right: var(--spacing-xl);
    flex-shrink: 0;
}

.activity-preview-content {
    flex: 1;
}

.activity-preview-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--color-gray-900);
    margin-bottom: var(--spacing-md);
}

.activity-details {
    color: var(--color-gray-600);
    font-size: 0.9rem;
}

.activity-details p {
    margin-bottom: var(--spacing-sm);
}

.option-preview {
    background: var(--color-white);
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-sm);
    margin-right: var(--spacing-xs);
    font-size: 0.8rem;
}

.more-options {
    color: var(--color-gray-500);
    font-style: italic;
}

.game-type-preview {
    background: var(--color-info-bg);
    color: var(--color-info-dark);
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    margin-right: var(--spacing-xs);
}

.activity-arrow {
    position: absolute;
    bottom: -1.5rem;
    left: 50%;
    transform: translateX(-50%);
    font-size: 1.5rem;
    color: var(--color-primary);
    background: var(--color-white);
    padding: 0 var(--spacing-sm);
}

.lesson-stats {
    display: flex;
    justify-content: center;
    gap: var(--spacing-2xl);
    margin-top: var(--spacing-3xl);
}

.stat-card {
    text-align: center;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: var(--spacing-xl);
    min-width: 120px;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-primary);
    margin-bottom: var(--spacing-sm);
}

.stat-label {
    color: var(--color-gray-600);
    font-size: 0.9rem;
}

.page-subtitle {
    color: var(--color-gray-600);
    font-size: 1.1rem;
    margin: 0;
}
</style>
@endpush


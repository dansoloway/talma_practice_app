@extends('layouts.app')

@section('title', $lesson->title)

@section('content')
<div class="container">
    <div class="lesson-header">
        <a href="{{ route('lessons.index') }}" class="back-link">&larr; Back to Lessons</a>
        <h1 class="page-title">{{ $lesson->title }}</h1>
        
        @if($lesson->grade_level || $lesson->session_number || $lesson->session_title)
            <div class="lesson-metadata">
                @if($lesson->grade_level)
                    <span class="grade-level">Grade {{ $lesson->grade_level }}</span>
                @endif
                @if($lesson->session_number)
                    <span class="session-number">Session {{ $lesson->session_number }}</span>
                @endif
                @if($lesson->session_title)
                    <span class="session-title">{{ $lesson->session_title }}</span>
                @endif
            </div>
        @endif
        
        @if($lesson->instructions)
            <div class="lesson-instructions">
                <h3>Instructions</h3>
                <p>{{ $lesson->instructions }}</p>
            </div>
        @endif

        @if($lesson->vocabulary && $lesson->vocabulary->count() > 0)
            <div class="vocabulary-section">
                <h3>Vocabulary for this lesson</h3>
                <div class="vocabulary-grid">
                    @foreach($lesson->vocabulary as $vocab)
                        <div class="vocabulary-item">
                            @if($vocab->image_path)
                                <img src="{{ asset('storage/' . $vocab->image_path) }}" alt="{{ $vocab->english_word }}" class="vocab-image">
                            @endif
                            <div class="vocab-content">
                                @if($vocab->word_audio_path)
                                    <button class="vocab-audio-btn" onclick="playVocabAudio('{{ asset('storage/' . $vocab->word_audio_path) }}')" title="Listen to word">
                                        <i class="fas fa-volume-up"></i>
                                    </button>
                                @endif
                                <div class="vocab-word">{{ $vocab->english_word }}</div>
                                @if($vocab->hebrew_translation || $vocab->arabic_translation)
                                    <div class="vocab-translations">
                                        @if($vocab->hebrew_translation)
                                            <button class="translation-toggle-btn hebrew" onclick="toggleTranslation(this, 'hebrew')">
                                                עברית
                                            </button>
                                            <div class="translation hebrew" style="display: none;">{{ $vocab->hebrew_translation }}</div>
                                        @endif
                                        @if($vocab->arabic_translation)
                                            <button class="translation-toggle-btn arabic" onclick="toggleTranslation(this, 'arabic')">
                                                عربي
                                            </button>
                                            <div class="translation arabic" style="display: none;">{{ $vocab->arabic_translation }}</div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @php
            // Get all activities in order (no vocabulary activity since it's already shown above)
            $allActivities = collect();
            
            // Add prompts as a single group if there are any
            if($lesson->prompts->count() > 0) {
                $activePrompts = $lesson->prompts->where('is_active', true);
                $minSortOrder = $lesson->prompts->min('sort_order') ?? 999;
                
                $allActivities->push((object)[
                    'id' => 'prompts',
                    'type' => 'prompts',
                    'title' => 'Sentence Completion (' . $activePrompts->count() . ' questions)',
                    'sort_order' => $minSortOrder,
                    'is_active' => $activePrompts->count() > 0,
                    'model' => $lesson->prompts,
                    'count' => $activePrompts->count()
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
            <div class="activities-section">
                <h3>Activities</h3>
                <p class="activities-description">Choose an activity to practice:</p>
                <div class="activities-menu">
                    @foreach($allActivities as $index => $activity)
                        <div class="activity-menu-item" onclick="startActivity('{{ $activity->type }}', '{{ $activity->id }}')">
                            <div class="activity-menu-icon">
                                @if($activity->type === 'prompts')
                                    📝
                                @elseif($activity->type === 'matching')
                                    🔗
                                @elseif($activity->type === 'flashcard')
                                    🎴
                                @endif
                            </div>
                            <div class="activity-menu-content">
                                <div class="activity-menu-type">{{ ucfirst($activity->type) }} Activity</div>
                                <div class="activity-menu-title">{{ $activity->title }}</div>
                                @if($activity->type === 'prompts')
                                    <div class="activity-menu-details">Complete sentences with the correct words</div>
                                @elseif($activity->type === 'matching')
                                    <div class="activity-menu-details">{{ $activity->model->grid_size }}x{{ $activity->model->grid_size }} matching grid</div>
                                @elseif($activity->type === 'flashcard')
                                    <div class="activity-menu-details">{{ $activity->model->cards_per_game }} flashcards</div>
                                @endif
                            </div>
                            <div class="activity-menu-arrow">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>


</div>

<audio id="prompt-audio" preload="auto"></audio>
<audio id="model-audio" preload="auto"></audio>
<audio id="playback-audio"></audio>

<script>
const lessonData = @json($lesson);
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// Vocabulary audio function
function playVocabAudio(audioPath) {
    const audio = new Audio(audioPath);
    audio.play().catch(error => {
        console.error('Error playing audio:', error);
    });
}

// Toggle translation display
function toggleTranslation(btn, lang) {
    const translation = btn.nextElementSibling;
    if (translation && translation.classList.contains('translation') && translation.classList.contains(lang)) {
        if (translation.style.display === 'none') {
            translation.style.display = 'block';
            btn.classList.add('active');
        } else {
            translation.style.display = 'none';
            btn.classList.remove('active');
        }
    }
}

// Activity selection function
function startActivity(type, id) {
    switch(type) {
        case 'prompts':
            // Go to prompts activity (all prompts for this lesson)
            window.location.href = `/lessons/{{ $lesson->id }}/prompts/play`;
            break;
        case 'matching':
            // Go to matching game
            window.location.href = `/lessons/{{ $lesson->id }}/matching-games/${id}/play`;
            break;
        case 'flashcard':
            // Go to flashcard game
            window.location.href = `/lessons/{{ $lesson->id }}/flashcard-games/${id}/play`;
            break;
    }
}

</script>
@endsection



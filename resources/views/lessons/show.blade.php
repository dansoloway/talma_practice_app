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
                                <div class="vocab-word">{{ $vocab->english_word }}</div>
                                    @if($vocab->word_audio_path)
                                    <button class="vocab-audio-btn" onclick="playVocabAudio('{{ asset('storage/' . $vocab->word_audio_path) }}')" title="Listen to word">
                                        <i class="fas fa-volume-up"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @php
            // Get all activities in order
            $allActivities = collect();
            
            // Always add vocabulary presentation as first activity if vocabulary exists
            if($lesson->vocabulary && $lesson->vocabulary->count() > 0) {
                $allActivities->push((object)[
                    'id' => 'vocab',
                    'type' => 'vocabulary',
                    'title' => 'Learn New Words',
                    'sort_order' => 0, // Always first
                    'is_active' => true,
                    'model' => null
                ]);
            }
            
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
            <div class="activities-section">
                <h3>Activities</h3>
                <p class="activities-description">Choose an activity to practice:</p>
                <div class="activities-menu">
                    @foreach($allActivities as $index => $activity)
                        <div class="activity-menu-item" onclick="startActivity('{{ $activity->type }}', '{{ $activity->id }}')">
                            <div class="activity-menu-number">{{ $index + 1 }}</div>
                            <div class="activity-menu-content">
                                <div class="activity-menu-type">{{ ucfirst($activity->type) }} Activity</div>
                                <div class="activity-menu-title">{{ $activity->title }}</div>
                                @if($activity->type === 'vocabulary')
                                    <div class="activity-menu-details">{{ $lesson->vocabulary->count() }} vocabulary words</div>
                                @elseif($activity->type === 'prompt' && $activity->model && $activity->model->options->count() > 0)
                                    <div class="activity-menu-details">{{ $activity->model->options->count() }} answer choices</div>
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


    <!-- Vocabulary Presentation Modal -->
    <div id="vocabulary-presentation" class="vocabulary-presentation hidden">
        <div class="vocab-presentation-header">
            <h1>{{ $lesson->title }} - Vocabulary</h1>
            <button class="close-vocab-btn" onclick="closeVocabularyPresentation()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="vocab-presentation-content">
            @if($lesson->vocabulary && $lesson->vocabulary->count() > 0)
                <div class="vocab-presentation-grid">
                    @foreach($lesson->vocabulary as $vocab)
                        <div class="vocab-presentation-item">
                            @if($vocab->image_path)
                                <div class="vocab-image-container">
                                    <img src="{{ asset('storage/' . $vocab->image_path) }}" alt="{{ $vocab->english_word }}" class="vocab-presentation-image">
                                </div>
                            @endif
                            <div class="vocab-presentation-word">{{ $vocab->english_word }}</div>
                            @if($vocab->word_audio_path)
                                <button class="vocab-presentation-audio" onclick="playVocabAudio('{{ asset('storage/' . $vocab->word_audio_path) }}')" title="Listen to word">
                                    <i class="fas fa-volume-up"></i>
                                    <span>Listen</span>
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
                
                <div class="vocab-presentation-footer">
                    <button class="btn btn-primary btn-large" onclick="closeVocabularyPresentation()">
                        Continue to Activities
                    </button>
                </div>
            @endif
        </div>
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

// Activity selection function
function startActivity(type, id) {
    switch(type) {
        case 'vocabulary':
            // Show vocabulary presentation modal
            showVocabularyPresentation();
            break;
        case 'prompt':
            // Start the lesson runner with this specific prompt
            window.location.href = `{{ route('lessons.show', $lesson->slug) }}?start_prompt=${id}`;
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

// Show vocabulary presentation
function showVocabularyPresentation() {
    // Hide the main lesson content
    document.querySelector('.lesson-header').style.display = 'none';
    
    // Show vocabulary presentation
    const vocabPresentation = document.getElementById('vocabulary-presentation');
    if (vocabPresentation) {
        vocabPresentation.classList.remove('hidden');
    }
}

// Close vocabulary presentation
function closeVocabularyPresentation() {
    // Show the main lesson content
    document.querySelector('.lesson-header').style.display = 'block';
    
    // Hide vocabulary presentation
    const vocabPresentation = document.getElementById('vocabulary-presentation');
    if (vocabPresentation) {
        vocabPresentation.classList.add('hidden');
    }
}
</script>
@endsection



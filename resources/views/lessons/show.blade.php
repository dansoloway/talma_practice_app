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

        @if($lesson->vocabulary && $lesson->vocabulary->count() > 0)
            <div class="vocabulary-section">
                <div class="vocab-header">
                    <h3>Vocabulary for this lesson</h3>
                    @php
                        $hasHebrew = $lesson->vocabulary->contains(fn($v) => !empty($v->hebrew_translation));
                        $hasArabic = $lesson->vocabulary->contains(fn($v) => !empty($v->arabic_translation));
                    @endphp
                    <div class="vocab-translation-buttons">
                        @if($hasHebrew)
                            <button class="vocab-lang-toggle-btn" data-lang="hebrew" onclick="toggleVocabLanguage('hebrew')">
                                עברית
                            </button>
                        @endif
                        @if($hasArabic)
                            <button class="vocab-lang-toggle-btn" data-lang="arabic" onclick="toggleVocabLanguage('arabic')">
                                عربي
                            </button>
                        @endif
                    </div>
                </div>
                <div class="vocabulary-grid">
                    @foreach($lesson->vocabulary as $vocab)
                        <div class="vocabulary-item">
                            @if($vocab->image_path)
                                <img src="{{ asset('storage/' . $vocab->image_path) }}" alt="{{ $vocab->english_word }}" class="vocab-image">
                            @endif
                            <div class="vocab-content">
                                @if($vocab->word_audio_path)
                                    <button class="vocab-audio-btn" onclick="playVocabAudio('{{ $vocab->word_audio_url }}')" title="Listen to word">
                                        <i class="fas fa-volume-up"></i>
                                    </button>
                                @endif
                                <div class="vocab-word">{{ $vocab->english_word }}</div>
                                @if($vocab->hebrew_translation)
                                    <div class="translation hebrew vocab-translation-hidden">{{ $vocab->hebrew_translation }}</div>
                                @endif
                                @if($vocab->arabic_translation)
                                    <div class="translation arabic vocab-translation-hidden">{{ $vocab->arabic_translation }}</div>
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
            
            foreach($lesson->spellingGames as $game) {
                $allActivities->push((object)[
                    'id' => $game->id,
                    'type' => 'spelling',
                    'title' => $game->title,
                    'sort_order' => $game->sort_order ?? 999,
                    'is_active' => $game->is_active ?? true,
                    'model' => $game
                ]);
            }
            
            // Add clause exercises
            foreach($lesson->clauseExercises as $exercise) {
                $allActivities->push((object)[
                    'id' => $exercise->id,
                    'type' => 'clause_exercise',
                    'title' => $exercise->title,
                    'sort_order' => $exercise->sort_order ?? 999,
                    'is_active' => $exercise->is_active ?? true,
                    'model' => $exercise
                ]);
            }
            
            // Sentence Builder Games (DISABLED)
            // foreach($lesson->sentenceBuilderGames as $game) {
            //     // Only show if game has active questions
            //     if($game->questions->count() > 0) {
            //         $allActivities->push((object)[
            //             'id' => $game->id,
            //             'type' => 'sentence_builder',
            //             'title' => $game->title,
            //             'sort_order' => $game->sort_order ?? 999,
            //             'is_active' => $game->is_active ?? true,
            //             'model' => $game
            //         ]);
            //     }
            // }
            
            // Add True/False game - check each version
            $trueFalseVersions = [];
            foreach(['easy', 'medium', 'hard'] as $version) {
                $count = $lesson->trueFalseQuestions()
                    ->forVersion($version)
                    ->where('is_approved', true)
                    ->where('is_active', true)
                    ->count();
                if($count > 0) {
                    $trueFalseVersions[$version] = $count;
                }
            }
            
            if(!empty($trueFalseVersions)) {
                if(count($trueFalseVersions) === 1) {
                    // Single version - show as single activity
                    $version = array_key_first($trueFalseVersions);
                    $allActivities->push((object)[
                        'id' => null,
                        'type' => 'true_false',
                        'title' => 'True/False Game (' . ucfirst($version) . ')',
                        'sort_order' => 999,
                        'is_active' => true,
                        'model' => (object)[
                            'question_count' => $trueFalseVersions[$version],
                            'version' => $version,
                            'available_versions' => $trueFalseVersions
                        ]
                    ]);
                } else {
                    // Multiple versions - show separate activities
                    foreach($trueFalseVersions as $version => $count) {
                        $allActivities->push((object)[
                            'id' => null,
                            'type' => 'true_false',
                            'title' => 'True/False Game (' . ucfirst($version) . ')',
                            'sort_order' => 999 + ($version === 'easy' ? 0 : ($version === 'medium' ? 1 : 2)),
                            'is_active' => true,
                            'model' => (object)[
                                'question_count' => $count,
                                'version' => $version,
                                'available_versions' => $trueFalseVersions
                            ]
                        ]);
                    }
                }
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
                                @elseif($activity->type === 'spelling')
                                    ✍️
                                @elseif($activity->type === 'clause_exercise')
                                    📄
                                {{-- @elseif($activity->type === 'sentence_builder')
                                    🏗️ --}}
                                @elseif($activity->type === 'true_false')
                                    ✓✗
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
                                @elseif($activity->type === 'spelling')
                                    <div class="activity-menu-details">{{ count($activity->model->vocabulary_ids ?? []) }} words</div>
                                @elseif($activity->type === 'clause_exercise')
                                    <div class="activity-menu-details">Fill in the blanks with vocabulary words</div>
                                @elseif($activity->type === 'true_false')
                                    <div class="activity-menu-details">{{ $activity->model->question_count }} questions</div>
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

// Toggle vocabulary language display
function toggleVocabLanguage(lang) {
    const btn = document.querySelector(`[data-lang="${lang}"]`);
    const translations = document.querySelectorAll(`.translation.${lang}`);
    
    btn.classList.toggle('active');
    
    translations.forEach(translation => {
        translation.classList.toggle('vocab-translation-hidden');
    });
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
        case 'spelling':
            // Go to spelling game
            window.location.href = `/lessons/{{ $lesson->id }}/spelling-games/${id}/play`;
            break;
        case 'clause_exercise':
            // Go to clause exercise
            window.location.href = `/lessons/{{ $lesson->id }}/clause-exercises/${id}/play`;
            break;
        // case 'sentence_builder':
        //     // Go to sentence builder game
        //     window.location.href = `/lessons/{{ $lesson->id }}/sentence-builder-games/${id}/play`;
        //     break;
        case 'true_false':
            // Go to True/False game with version
            const version = activity.model?.version || 'easy';
            window.location.href = `/lessons/{{ $lesson->id }}/true-false/play?version=${version}`;
            break;
    }
}

</script>
@endsection



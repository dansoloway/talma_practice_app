@extends('layouts.app')

@section('title', $lesson->title)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-8">
    <div class="container mx-auto px-4 max-w-5xl">
        <!-- Back Link -->
        <a href="{{ route('lessons.index') }}" 
           class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium mb-6 transition-colors duration-200 group">
            <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform duration-200"></i>
            <span>Back to Lessons</span>
        </a>

        <!-- Lesson Header -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 md:p-6 mb-8">
            @if($lesson->cover_image_path)
                <div class="mb-4 text-center">
                    <img src="{{ $lesson->cover_image_url }}" 
                         alt="{{ $lesson->title }}" 
                         class="max-w-full max-h-64 md:max-h-80 mx-auto rounded-xl shadow-md object-cover">
                </div>
            @endif
            
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-3 text-center tracking-tight">
                {{ $lesson->title }}
            </h1>
            
            @if($lesson->grade_level || $lesson->session_number || $lesson->course)
                <div class="flex flex-wrap justify-center gap-3 mb-4">
                    @if($lesson->grade_level)
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-blue-100 text-blue-700 border border-blue-200">
                            Grade {{ $lesson->grade_level }}
                        </span>
                    @endif
                    @if($lesson->session_number)
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-purple-100 text-purple-700 border border-purple-200">
                            Session {{ $lesson->session_number }}
                        </span>
                    @endif
                    @if($lesson->course)
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                            {{ $lesson->course->title }}
                        </span>
                    @endif
                </div>
            @endif
        </div>

        @if($lesson->vocabulary && $lesson->vocabulary->count() > 0)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 mb-8 overflow-hidden">
                <!-- Accordion Header -->
                <div class="p-6 md:p-8">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <h3 class="text-2xl font-bold text-gray-800">Vocabulary for this lesson</h3>
                            <span class="text-sm text-gray-500">({{ $lesson->vocabulary->count() }} words)</span>
                        </div>
                        <div class="flex items-center gap-3">
                            @php
                                $hasHebrew = $lesson->vocabulary->contains(fn($v) => !empty($v->hebrew_translation));
                                $hasArabic = $lesson->vocabulary->contains(fn($v) => !empty($v->arabic_translation));
                            @endphp
                            @if($hasHebrew || $hasArabic)
                                <div class="flex gap-3" onclick="event.stopPropagation()">
                                    @if($hasHebrew)
                                        <button class="vocab-lang-toggle-btn px-4 py-2 rounded-xl border-2 border-gray-300 bg-white text-gray-700 font-semibold hover:border-blue-400 hover:bg-blue-50 transition-all duration-200 active:scale-95" 
                                                data-lang="hebrew" onclick="toggleVocabLanguage('hebrew')">
                                            עברית
                                        </button>
                                    @endif
                                    @if($hasArabic)
                                        <button class="vocab-lang-toggle-btn px-4 py-2 rounded-xl border-2 border-gray-300 bg-white text-gray-700 font-semibold hover:border-green-400 hover:bg-green-50 transition-all duration-200 active:scale-95" 
                                                data-lang="arabic" onclick="toggleVocabLanguage('arabic')">
                                            عربي
                                        </button>
                                    @endif
                                </div>
                            @endif
                            <button onclick="toggleVocabAccordion()" class="flex items-center justify-center w-8 h-8 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                                <i id="vocab-accordion-icon" class="fas fa-chevron-down text-gray-400 transition-transform duration-300"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Accordion Content -->
                <div id="vocab-accordion-content" class="hidden px-6 md:px-8 pt-6 md:pt-8 pb-6 md:pb-8 border-t border-gray-100">
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                        @foreach($lesson->vocabulary as $vocab)
                            <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 text-center hover:shadow-md hover:border-blue-300 transition-all duration-200">
                                @if($vocab->image_path)
                                    <img src="{{ asset('storage/' . $vocab->image_path) }}" 
                                         alt="{{ $vocab->english_word }}" 
                                         class="w-full h-24 md:h-32 object-cover rounded-lg mb-3">
                                @endif
                                <div class="flex flex-col items-center gap-2">
                                    @if($vocab->word_audio_path)
                                        <button class="w-10 h-10 rounded-full bg-blue-600 text-white hover:bg-blue-700 active:scale-95 transition-all duration-200 flex items-center justify-center shadow-sm" 
                                                onclick="playVocabAudio('{{ $vocab->word_audio_url }}')" 
                                                title="Listen to word">
                                            <i class="fas fa-volume-up text-sm"></i>
                                        </button>
                                    @endif
                                    <div class="text-lg font-bold text-gray-800">{{ $vocab->english_word }}</div>
                                    @if($vocab->hebrew_translation)
                                        <div class="translation hebrew vocab-translation-hidden text-sm font-semibold px-3 py-1 rounded-lg bg-blue-100 text-blue-800 border border-blue-200">
                                            {{ $vocab->hebrew_translation }}
                                        </div>
                                    @endif
                                    @if($vocab->arabic_translation)
                                        <div class="translation arabic vocab-translation-hidden text-sm font-semibold px-3 py-1 rounded-lg bg-green-100 text-green-800 border border-green-200">
                                            {{ $vocab->arabic_translation }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
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
            
            // Add True/False games (new game-based approach)
            try {
                foreach($lesson->trueFalseGames as $game) {
                    if(!$game->is_active) continue;
                    
                    $approvedCount = $game->questions()
                        ->where('is_approved', true)
                        ->where('is_active', true)
                        ->count();
                    
                    if($approvedCount > 0) {
                        $allActivities->push((object)[
                            'id' => $game->id,
                            'type' => 'true_false',
                            'title' => $game->title . ' (' . $approvedCount . ' questions)',
                            'sort_order' => $game->sort_order ?? 999,
                            'is_active' => $game->is_active ?? true,
                            'model' => $game
                        ]);
                    }
                }
            } catch (\Exception $e) {
                // Table doesn't exist yet - skip
            }
            
            $allActivities = $allActivities->where('is_active', true)->sortBy('sort_order');
        @endphp

        @if($allActivities->count() > 0)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 mb-8 overflow-hidden">
                <!-- Accordion Header -->
                <div class="p-6 md:p-8">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <h3 class="text-2xl font-bold text-gray-800">Activities</h3>
                            <span class="text-sm text-gray-500">({{ $allActivities->count() }} activities)</span>
                        </div>
                        <button onclick="toggleActivitiesAccordion()" class="flex items-center justify-center w-8 h-8 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                            <i id="activities-accordion-icon" class="fas fa-chevron-down text-gray-400 transition-transform duration-300"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Accordion Content -->
                <div id="activities-accordion-content" class="hidden px-6 md:px-8 pt-6 md:pt-8 pb-6 md:pb-8 border-t border-gray-100">
                    <p class="text-gray-600 text-center mb-6">Choose an activity to practice:</p>
                    <div class="space-y-3">
                        @foreach($allActivities as $index => $activity)
                            @php
                                // Determine display title: use default game name if title matches pattern, otherwise use customized title
                                $displayTitle = $activity->title;
                                
                                // Check if title matches default pattern (starts with lesson title + default game name pattern)
                                $lessonTitleEscaped = preg_quote(trim($lesson->title), '/');
                                
                                if ($activity->type === 'matching') {
                                    // Pattern: "{Lesson Title} Matching Game {number}"
                                    $pattern = '/^' . $lessonTitleEscaped . '\s+Matching\s+Game\s+\d+$/i';
                                    if (preg_match($pattern, trim($activity->title))) {
                                        $displayTitle = 'Matching Game';
                                    }
                                } elseif ($activity->type === 'flashcard') {
                                    // Pattern: "{Lesson Title} Flashcards {number}"
                                    $pattern = '/^' . $lessonTitleEscaped . '\s+Flashcards\s+\d+$/i';
                                    if (preg_match($pattern, trim($activity->title))) {
                                        $displayTitle = 'Flashcards';
                                    }
                                } elseif ($activity->type === 'spelling') {
                                    // Pattern: "{Lesson Title} Spelling Practice {number}"
                                    $pattern = '/^' . $lessonTitleEscaped . '\s+Spelling\s+Practice\s+\d+$/i';
                                    if (preg_match($pattern, trim($activity->title))) {
                                        $displayTitle = 'Spelling Practice';
                                    }
                                }
                            @endphp
                            <div class="group bg-gray-50 rounded-xl border-2 border-gray-200 p-4 hover:border-blue-300 hover:bg-blue-50 hover:shadow-md transition-all duration-300 cursor-pointer flex items-center gap-4" 
                                 onclick="startActivity('{{ $activity->type }}', '{{ $activity->id }}')">
                                <div class="flex-shrink-0 w-14 h-14 md:w-16 md:h-16 rounded-xl bg-white border-2 border-gray-200 flex items-center justify-center text-2xl md:text-3xl group-hover:border-blue-300 transition-colors duration-200">
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
                                    @elseif($activity->type === 'true_false')
                                        ✓✗
                                    @endif
                                </div>
                                <div class="flex-1 text-base md:text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors duration-200">
                                    {{ $displayTitle }}
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="fas fa-chevron-right text-gray-400 group-hover:text-blue-600 group-hover:translate-x-1 transition-all duration-200"></i>
                                </div>
                            </div>
                        @endforeach
                    </div>
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

// Toggle vocabulary accordion
function toggleVocabAccordion() {
    const content = document.getElementById('vocab-accordion-content');
    const icon = document.getElementById('vocab-accordion-icon');
    
    content.classList.toggle('hidden');
    icon.classList.toggle('rotate-180');
}

// Toggle activities accordion
function toggleActivitiesAccordion() {
    const content = document.getElementById('activities-accordion-content');
    const icon = document.getElementById('activities-accordion-icon');
    
    content.classList.toggle('hidden');
    icon.classList.toggle('rotate-180');
}

// Toggle vocabulary language display
function toggleVocabLanguage(lang) {
    const btn = document.querySelector(`[data-lang="${lang}"]`);
    const translations = document.querySelectorAll(`.translation.${lang}`);
    
    btn.classList.toggle('active');
    if (btn.classList.contains('active')) {
        btn.classList.add('border-2', 'scale-105');
        if (lang === 'hebrew') {
            btn.classList.add('border-blue-400', 'bg-blue-100');
        } else if (lang === 'arabic') {
            btn.classList.add('border-green-400', 'bg-green-100');
        }
    } else {
        btn.classList.remove('border-2', 'scale-105', 'border-blue-400', 'bg-blue-100', 'border-green-400', 'bg-green-100');
    }
    
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
            // Go to True/False game
            window.location.href = `/lessons/{{ $lesson->id }}/true-false-games/${id}/play`;
            break;
    }
}

</script>
@endsection



@extends('layouts.app')

@section('title', 'Vocabulary Matching Game')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-6 md:py-8">
    <div class="container mx-auto px-4 max-w-6xl">
        @include('partials.student-game-locale-bar')
        <!-- Game Header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                @include('partials.student-lesson-back-link', ['lesson' => $lesson, 'org' => $org ?? null])
                @include('partials.admin-edit-lesson', [
                    'lesson' => $lesson,
                    'activityEditUrl' => route('admin.lessons.matching-games.edit', [$lesson, $matching_game]),
                    'activityEditLabel' => 'Edit Game',
                ])
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 text-center mb-6">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">{{ $matching_game->title }}</h1>
            </div>
        </div>
        
        @if(isset($gameData['available_modes']) && count($gameData['available_modes']) > 1)
            <div class="flex justify-center mb-6">
                <div class="bg-white/80 backdrop-blur-sm rounded-xl border border-gray-200 shadow-sm p-4 flex items-center gap-4">
                    <label for="mode-select" class="font-semibold text-gray-700">{{ __('student-portal.games.match_english_with') }}</label>
                    <select id="mode-select" 
                            onchange="changeMode(this.value)"
                            class="px-4 py-2 border border-gray-300 rounded-xl bg-white text-gray-800 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
                        @foreach($gameData['available_modes'] as $modeKey => $modeLabel)
                            <option value="{{ $modeKey }}" {{ $mode === $modeKey ? 'selected' : '' }}>
                                {{ $modeLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif

        <!-- Game Stats -->
        <div class="bg-white/80 backdrop-blur-sm rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
            <div class="flex justify-center gap-6 md:gap-12 flex-wrap">
                <div class="text-center">
                    <div class="text-sm font-semibold text-gray-600 mb-1">{{ __('student-portal.games.matches_found') }}</div>
                    <div class="text-2xl font-bold text-blue-600" id="matches-found">0</div>
                </div>
                <div class="text-center">
                    <div class="text-sm font-semibold text-gray-600 mb-1">{{ __('student-portal.games.total_pairs') }}</div>
                    <div class="text-2xl font-bold text-purple-600" id="total-pairs">{{ count($gameData['cards']) / 2 }}</div>
                </div>
                <div class="text-center">
                    <div class="text-sm font-semibold text-gray-600 mb-1">{{ __('student-portal.games.time') }}</div>
                    <div class="text-2xl font-bold text-green-600" id="game-time">0:00</div>
                </div>
            </div>
        </div>

        <!-- Game Area - Responsive grid with content-sized cards -->
        <div class="relative bg-white rounded-2xl shadow-sm border border-gray-200 p-4 md:p-8 min-h-[300px] game-area-container{{ $mode === 'image' ? ' has-image-cards' : '' }}" id="game-area">
            @if(isset($gameData['cards']) && count($gameData['cards']) > 0)
                @foreach($gameData['cards'] as $index => $card)
                    <div class="game-card cursor-pointer bg-white rounded-xl border-2 border-blue-500 shadow-md hover:shadow-xl transition-all duration-200" 
                         data-card-id="{{ $card['id'] }}" 
                         data-vocab-id="{{ $card['vocab_id'] }}" 
                         data-type="{{ $card['type'] }}"
                         data-index="{{ $index }}">
                        <div class="card-content w-full h-full flex items-center justify-center p-3 md:p-4">
                            @if($card['type'] === 'audio')
                                @if(!empty($card['audio_path']))
                                    <button type="button" class="play-audio-strip talma-audio-btn w-full h-full bg-blue-600 text-white rounded-lg hover:bg-blue-700 active:scale-95 transition-all duration-200 flex items-center justify-center" 
                                            data-audio="{{ $card['audio_path'] }}" 
                                            title="{{ __('student-portal.games.play_audio') }}">
                                        <i class="fas fa-play text-3xl md:text-4xl talma-audio-icon"></i>
                                    </button>
                                @else
                                    <div class="text-red-600 font-semibold">{{ __('student-portal.games.no_audio') }}</div>
                                @endif
                            @elseif($card['type'] === 'image' && $card['content'])
                                <img src="{{ $card['content'] }}" alt="{{ $card['word'] }}" class="w-full h-full object-cover rounded-lg">
                            @elseif($card['type'] === 'hebrew')
                                <div class="card-translation hebrew w-full h-full bg-blue-100 text-blue-800 border-2 border-blue-300 rounded-lg flex items-center justify-center font-semibold text-base md:text-lg px-3 py-2 text-center" dir="rtl" lang="he">
                                    {{ $card['content'] }}
                                </div>
                            @elseif($card['type'] === 'arabic')
                                <div class="card-translation arabic w-full h-full bg-green-100 text-green-800 border-2 border-green-300 rounded-lg flex items-center justify-center font-semibold text-base md:text-lg px-3 py-2 text-center" dir="rtl" lang="ar">
                                    {{ $card['content'] }}
                                </div>
                            @else
                                <div class="card-word student-learning-ltr w-full h-full flex items-center justify-center font-bold text-gray-800 text-base md:text-lg px-3 py-2 text-center" dir="ltr" lang="en">
                                    {{ $card['content'] }}
                                </div>
                                @if($card['audio_path'] && $mode !== 'image' && $mode !== 'audio')
                                    <button type="button" class="play-audio-btn talma-audio-btn absolute top-2 right-2 w-8 h-8 rounded-full bg-blue-600 text-white hover:bg-blue-700 active:scale-95 transition-all duration-200 flex items-center justify-center shadow-sm" 
                                            data-audio="{{ $card['audio_path'] }}" 
                                            data-talma-audio-icon="volume-up"
                                            title="{{ __('student-portal.games.play_audio') }}">
                                        <i class="fas fa-volume-up text-xs talma-audio-icon"></i>
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-center py-12">
                    <div class="text-6xl mb-4">🔍</div>
                    <p class="text-gray-600 text-lg">{{ __('student-portal.games.no_cards') }}</p>
                </div>
            @endif
        </div>

        <!-- Game Completion Modal -->
        <div id="game-completion" class="hidden fixed inset-0 z-50 p-4" style="display: none; background: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
            <div class="bg-white rounded-2xl shadow-xl p-8 md:p-12 text-center max-w-md w-full">
                <div class="text-6xl mb-4">🎉</div>
                <h2 class="text-3xl font-bold text-gray-800 mb-3">{{ __('student-portal.games.congratulations') }}</h2>
                <p class="text-lg text-gray-600 mb-6">{{ __('student-portal.games.matching_complete_message') }}</p>
                <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-xl p-6 border-2 border-blue-200 mb-6">
                    <div class="flex justify-around gap-4">
                        <div>
                            <div class="text-sm font-semibold text-gray-600 mb-1">{{ __('student-portal.games.final_time') }}</div>
                            <div class="text-2xl font-bold text-blue-600" id="final-time">0:00</div>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-600 mb-1">{{ __('student-portal.games.total_matches') }}</div>
                            <div class="text-2xl font-bold text-purple-600" id="final-matches">{{ count($gameData['cards']) / 2 }}</div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <button onclick="location.reload()" 
                            class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">
                        {{ __('student-portal.games.play_again') }}
                    </button>
                    @include('partials.guided-flow-nav', ['guidedFlow' => $guidedFlow ?? null, 'lesson' => $lesson, 'fallbackLabel' => __('student-portal.games.continue_lesson')])
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.matching-game-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
}

.game-header {
    text-align: center;
    margin-bottom: 2rem;
}

.game-title {
    color: var(--color-primary);
    margin: 1rem 0 0.5rem;
}

.game-description {
    color: var(--color-text-light);
    font-size: 1.1rem;
}

/* Stats styling moved to Tailwind classes in HTML */

#game-area {
    --card-width: 100px;
    --card-height: 90px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(var(--card-width), 1fr));
    gap: 0.75rem;
    padding: 0.75rem;
    align-content: start;
    justify-items: stretch;
    min-height: auto;
    overflow: visible;
}

#game-area.has-image-cards {
    --card-height: var(--card-width);
}

#game-area.has-image-cards .game-card {
    aspect-ratio: 1 / 1;
    height: auto;
}

#game-area.has-image-cards .game-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    aspect-ratio: 1 / 1;
}

.game-card {
    position: relative;
    width: 100%;
    height: var(--card-height);
    min-width: 0;
    cursor: pointer;
    overflow: hidden;
    transition: all 0.3s ease;
}

/* Mobile: compact header/stats */
@media (max-width: 768px) {
    .min-h-screen {
        min-height: 100vh;
        padding: 0.5rem 0 !important;
    }
    
    .container {
        padding: 0.5rem !important;
        max-width: 100% !important;
    }
    
    .mb-6 {
        margin-bottom: 1rem !important;
    }
    
    .mb-4 {
        margin-bottom: 0.75rem !important;
    }
    
    .text-2xl {
        font-size: 1.25rem !important;
    }
    
    .text-3xl {
        font-size: 1.5rem !important;
    }
    
    #game-area {
        gap: 0.5rem;
        padding: 0.75rem;
    }
    
    .game-card .card-content {
        padding: 0.5rem !important;
    }
    
    .game-card img {
        max-width: 100%;
        max-height: 100%;
        object-fit: cover;
    }
    
    .play-audio-strip {
        padding: 0.5rem !important;
    }
    
    .play-audio-strip i {
        font-size: 1.5rem !important;
    }
    
    .play-audio-btn {
        width: 24px !important;
        height: 24px !important;
        top: 2px !important;
        right: 2px !important;
    }
    
    .play-audio-btn i {
        font-size: 0.625rem !important;
    }
    
    .bg-white\/80 {
        padding: 0.75rem !important;
    }
    
    .text-sm {
        font-size: 0.75rem !important;
    }
}

.game-card:hover {
    z-index: 10;
}

.game-card.matched {
    transition: all 0.6s ease;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-3px); }
    20%, 40%, 60%, 80% { transform: translateX(3px); }
}

.card-content {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

/* Card content styling handled by Tailwind classes */

.card-word,
.card-translation {
    white-space: normal;
    word-break: break-word;
    overflow-wrap: break-word;
    line-height: 1.2;
    text-align: center;
    max-width: 100%;
    overflow: visible;
}

/* Audio button styling handled by Tailwind classes */

/* Completion modal styling moved to Tailwind classes in HTML */

/* Mobile grid styles handled above */
</style>

@include('partials.student-game-i18n')

<script>
const activityEventEndpoint = '{{ route('activity-events.store') }}';
const activityEventPayload = {
    lesson_id: {{ $lesson->id }},
    activity_type: 'matching',
    activity_id: {{ $matching_game->id }},
};

function logActivityEvent(status, meta = {}) {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    fetch(activityEventEndpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            ...(token ? { 'X-CSRF-TOKEN': token } : {}),
        },
        body: JSON.stringify({
            ...activityEventPayload,
            status,
            meta,
        }),
    }).catch(() => {});
}

class MatchingGame {
    constructor(config) {
        this.cards = document.querySelectorAll('.game-card');
        this.flippedCards = [];
        this.matches = 0;
        this.startTime = Date.now();
        this.gameInterval = null;
        this.mode = config.mode;
        
        this.init();

        logActivityEvent('started', {
            mode: this.mode,
        });
    }
    
    layoutCards() {
        const gameArea = document.getElementById('game-area');
        if (!gameArea) return;

        Array.from(this.cards).forEach((card) => {
            card.style.left = '';
            card.style.top = '';
            card.style.position = '';
            card.style.width = '100%';
            card.style.zIndex = '';
        });
    }
    
    init() {
        this.cards.forEach(card => {
            card.addEventListener('click', (e) => {
                // Don't flip if click was on regular audio button (for word cards in other modes)
                if (e.target.closest('.play-audio-btn')) {
                    return;
                }
                // For audio strip buttons, allow the click to proceed (will play audio and select card)
                this.flipCard(card);
            });
        });
        
        this.startTimer();
    }
    
    flipCard(card) {
        // Don't select if already matched
        if (card.classList.contains('matched')) {
            return;
        }
        
        // Don't select if already selected
        if (card.classList.contains('selected')) {
            return;
        }
        
        // Don't select if already have 2 cards selected
        if (this.flippedCards.length >= 2) {
            return;
        }
        
        // Add selection highlight with Tailwind classes
        card.classList.add('border-green-500', 'ring-4', 'ring-green-200', 'selected');
        card.classList.remove('border-blue-500');
        this.flippedCards.push(card);
        
        // Check for match when 2 cards are selected
        if (this.flippedCards.length === 2) {
            setTimeout(() => this.checkMatch(), 300);
        }
    }
    
    checkMatch() {
        const [card1, card2] = this.flippedCards;
        const vocabId1 = card1.dataset.vocabId;
        const vocabId2 = card2.dataset.vocabId;
        
        if (vocabId1 === vocabId2) {
            // Match found! Show green highlight first
            card1.classList.remove('selected', 'border-green-500', 'ring-4', 'ring-green-200');
            card2.classList.remove('selected', 'border-green-500', 'ring-4', 'ring-green-200');
            card1.classList.add('border-green-600', 'bg-green-100', 'ring-4', 'ring-green-300');
            card2.classList.add('border-green-600', 'bg-green-100', 'ring-4', 'ring-green-300');
            
            // After showing green, mark as matched and fade out
            setTimeout(() => {
                card1.classList.remove('border-green-600', 'bg-green-100', 'ring-4', 'ring-green-300');
                card2.classList.remove('border-green-600', 'bg-green-100', 'ring-4', 'ring-green-300');
                card1.classList.add('opacity-0', 'scale-75', 'pointer-events-none', 'matched');
                card2.classList.add('opacity-0', 'scale-75', 'pointer-events-none', 'matched');
                this.matches++;
                this.updateStats();
                
                // Check if game is complete
                if (this.matches === this.cards.length / 2) {
                    this.completeGame();
                }
            }, 600);
        } else {
            // No match - show red highlight and shake
            card1.classList.add('border-red-500', 'bg-red-50', 'animate-pulse');
            card2.classList.add('border-red-500', 'bg-red-50', 'animate-pulse');
            
            // After animation, remove incorrect styling and selection
            setTimeout(() => {
                card1.classList.remove('incorrect', 'selected', 'border-green-500', 'ring-4', 'ring-green-200', 'border-red-500', 'bg-red-50', 'animate-pulse');
                card2.classList.remove('incorrect', 'selected', 'border-green-500', 'ring-4', 'ring-green-200', 'border-red-500', 'bg-red-50', 'animate-pulse');
                card1.classList.add('border-blue-500');
                card2.classList.add('border-blue-500');
            }, 500);
        }
        
        this.flippedCards = [];
    }
    
    updateStats() {
        document.getElementById('matches-found').textContent = this.matches;
    }
    
    startTimer() {
        this.gameInterval = setInterval(() => {
            const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
            const minutes = Math.floor(elapsed / 60);
            const seconds = elapsed % 60;
            document.getElementById('game-time').textContent = 
                `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }, 1000);
    }
    
    completeGame() {
        clearInterval(this.gameInterval);
        
        const durationSeconds = Math.floor((Date.now() - this.startTime) / 1000);

        // Show completion screen
        const finalTime = document.getElementById('game-time').textContent;
        document.getElementById('final-time').textContent = finalTime;
        document.getElementById('final-matches').textContent = this.matches;
        const completionModal = document.getElementById('game-completion');
        completionModal.classList.remove('hidden');
        completionModal.style.display = 'flex';

        logActivityEvent('completed', {
            mode: this.mode,
            matches: this.matches,
            duration_seconds: durationSeconds,
        });
    }
}

function getCardTextMeasurer() {
    let measurer = document.getElementById('card-text-measurer');
    if (!measurer) {
        measurer = document.createElement('div');
        measurer.id = 'card-text-measurer';
        document.body.appendChild(measurer);
    }
    return measurer;
}

function measureTextElement(element, fontRem, maxTextWidth, padding) {
    const measurer = getCardTextMeasurer();
    measurer.className = element.className;
    measurer.style.cssText = [
        'position:absolute',
        'left:-9999px',
        'top:-9999px',
        'visibility:hidden',
        'pointer-events:none',
        `font-size:${fontRem}rem`,
        'white-space:normal',
        'word-break:break-word',
        'overflow-wrap:break-word',
        'line-height:1.2',
        'text-align:center',
        `max-width:${maxTextWidth}px`,
        `padding:${padding}`,
    ].join(';');
    measurer.textContent = element.textContent.trim();

    return {
        width: measurer.offsetWidth,
        height: measurer.offsetHeight,
    };
}

function fitCardsToContent() {
    const gameArea = document.getElementById('game-area');
    if (!gameArea) return;

    const textElements = gameArea.querySelectorAll('.card-word, .card-translation');
    const cards = gameArea.querySelectorAll('.game-card');
    if (cards.length === 0) return;

    const isMobile = window.innerWidth <= 768;
    const targetFontRem = isMobile ? 0.875 : 1;
    const minFontRem = 0.75;
    const minCardWidth = 100;
    const minCardHeight = 72;
    const maxCardWidth = 220;
    const maxCardHeight = 120;
    const contentPaddingX = isMobile ? 16 : 24;
    const contentPaddingY = isMobile ? 16 : 24;
    const textPadding = isMobile ? '0.25rem 0.5rem' : '0.5rem 0.75rem';
    const maxTextWidth = maxCardWidth - contentPaddingX;

    let optimalFontRem = targetFontRem;

    const measureAll = (fontRem) => {
        let maxWidth = minCardWidth;
        let maxHeight = minCardHeight;
        let allFit = true;

        textElements.forEach((element) => {
            const size = measureTextElement(element, fontRem, maxTextWidth, textPadding);
            const cardWidth = size.width + contentPaddingX;
            const cardHeight = size.height + contentPaddingY;

            if (cardWidth > maxCardWidth || cardHeight > maxCardHeight) {
                allFit = false;
            }

            maxWidth = Math.max(maxWidth, Math.min(cardWidth, maxCardWidth));
            maxHeight = Math.max(maxHeight, Math.min(cardHeight, maxCardHeight));
        });

        return { maxWidth, maxHeight, allFit };
    };

    let { maxWidth, maxHeight, allFit } = measureAll(targetFontRem);

    if (!allFit) {
        let lo = minFontRem;
        let hi = targetFontRem;
        let best = minFontRem;

        for (let i = 0; i < 12; i++) {
            const mid = (lo + hi) / 2;
            if (measureAll(mid).allFit) {
                best = mid;
                lo = mid;
            } else {
                hi = mid;
            }
            if (hi - lo < 0.02) break;
        }

        optimalFontRem = best;
        ({ maxWidth, maxHeight } = measureAll(optimalFontRem));
    }

    maxWidth = Math.max(minCardWidth, Math.min(maxWidth, maxCardWidth));
    maxHeight = Math.max(minCardHeight, Math.min(maxHeight, maxCardHeight));

    const hasImageCards = gameArea.classList.contains('has-image-cards');
    if (hasImageCards) {
        const size = Math.max(maxWidth, maxHeight);
        maxWidth = size;
        maxHeight = size;
    }

    gameArea.style.setProperty('--card-width', maxWidth + 'px');
    gameArea.style.setProperty('--card-height', maxHeight + 'px');

    cards.forEach((card) => {
        card.style.height = hasImageCards ? 'auto' : maxHeight + 'px';
    });

    textElements.forEach((element) => {
        element.style.fontSize = optimalFontRem + 'rem';
        element.style.whiteSpace = 'normal';
        element.style.wordBreak = 'break-word';
        element.style.overflowWrap = 'break-word';
        element.style.lineHeight = '1.2';
        element.style.textOverflow = '';
        element.style.overflow = 'visible';
    });
}

function layoutMatchingGame() {
    if (!matchingGameInstance) return;
    matchingGameInstance.layoutCards();
    fitCardsToContent();
}

// Start the game when page loads
let matchingGameInstance = null;

document.addEventListener('DOMContentLoaded', function() {
    matchingGameInstance = new MatchingGame({
        mode: '{{ $mode }}',
    });

    requestAnimationFrame(() => {
        layoutMatchingGame();
    });
    
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            layoutMatchingGame();
        }, 300);
    });
});

// Function to change matching mode
function changeMode(mode) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('mode', mode);
    window.location.href = currentUrl.toString();
}
</script>

<style>
.mode-selector-container {
    display: flex;
    justify-content: center;
    margin-bottom: 1rem;
}

.mode-selector {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.mode-selector label {
    font-weight: 600;
    color: #374151;
    margin: 0;
}

.mode-selector select {
    padding: 0.5rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    font-size: 1rem;
    cursor: pointer;
}

.mode-selector select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Card translation and content styling handled by Tailwind classes in HTML */
</style>
@endsection

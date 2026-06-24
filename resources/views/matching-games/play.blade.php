@extends('layouts.app')

@section('title', 'Vocabulary Matching Game')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-6 md:py-8">
    <div class="container mx-auto px-4 max-w-6xl">
        <!-- Game Header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <a href="{{ route('lessons.show', $lesson->slug) }}" 
                   class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium transition-colors duration-200 group">
                    <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform duration-200"></i>
                    <span>Back to Lesson</span>
                </a>
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
                    <label for="mode-select" class="font-semibold text-gray-700">Match English with:</label>
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
                    <div class="text-sm font-semibold text-gray-600 mb-1">Matches Found</div>
                    <div class="text-2xl font-bold text-blue-600" id="matches-found">0</div>
                </div>
                <div class="text-center">
                    <div class="text-sm font-semibold text-gray-600 mb-1">Total Pairs</div>
                    <div class="text-2xl font-bold text-purple-600" id="total-pairs">{{ count($gameData['cards']) / 2 }}</div>
                </div>
                <div class="text-center">
                    <div class="text-sm font-semibold text-gray-600 mb-1">Time</div>
                    <div class="text-2xl font-bold text-green-600" id="game-time">0:00</div>
                </div>
            </div>
        </div>

        <!-- Game Area - Randomly positioned cards on desktop, grid on mobile -->
        <div class="relative bg-white rounded-2xl shadow-sm border border-gray-200 p-4 md:p-8 min-h-[500px] md:min-h-[600px] game-area-container" id="game-area">
            @if(isset($gameData['cards']) && count($gameData['cards']) > 0)
                @foreach($gameData['cards'] as $index => $card)
                    <div class="game-card absolute md:absolute cursor-pointer bg-white rounded-xl border-2 border-blue-500 shadow-md hover:shadow-xl hover:scale-105 transition-all duration-200" 
                         data-card-id="{{ $card['id'] }}" 
                         data-vocab-id="{{ $card['vocab_id'] }}" 
                         data-type="{{ $card['type'] }}"
                         data-index="{{ $index }}">
                        <div class="card-content w-full h-full flex items-center justify-center p-3 md:p-4">
                            @if($card['type'] === 'audio')
                                @if(!empty($card['audio_path']))
                                    <button type="button" class="play-audio-strip talma-audio-btn w-full h-full bg-blue-600 text-white rounded-lg hover:bg-blue-700 active:scale-95 transition-all duration-200 flex items-center justify-center" 
                                            data-audio="{{ $card['audio_path'] }}" 
                                            title="Play audio">
                                        <i class="fas fa-play text-3xl md:text-4xl talma-audio-icon"></i>
                                    </button>
                                @else
                                    <div class="text-red-600 font-semibold">No audio</div>
                                @endif
                            @elseif($card['type'] === 'image' && $card['content'])
                                <img src="{{ $card['content'] }}" alt="{{ $card['word'] }}" class="w-full h-full object-cover rounded-lg">
                            @elseif($card['type'] === 'hebrew')
                                <div class="card-translation hebrew w-full h-full bg-blue-100 text-blue-800 border-2 border-blue-300 rounded-lg flex items-center justify-center font-semibold text-base md:text-lg px-3 py-2">
                                    {{ $card['content'] }}
                                </div>
                            @elseif($card['type'] === 'arabic')
                                <div class="card-translation arabic w-full h-full bg-green-100 text-green-800 border-2 border-green-300 rounded-lg flex items-center justify-center font-semibold text-base md:text-lg px-3 py-2">
                                    {{ $card['content'] }}
                                </div>
                            @else
                                <div class="card-word w-full h-full flex items-center justify-center font-bold text-gray-800 text-base md:text-lg px-3 py-2 text-center">
                                    {{ $card['content'] }}
                                </div>
                                @if($card['audio_path'] && $mode !== 'image' && $mode !== 'audio')
                                    <button type="button" class="play-audio-btn talma-audio-btn absolute top-2 right-2 w-8 h-8 rounded-full bg-blue-600 text-white hover:bg-blue-700 active:scale-95 transition-all duration-200 flex items-center justify-center shadow-sm" 
                                            data-audio="{{ $card['audio_path'] }}" 
                                            data-talma-audio-icon="volume-up"
                                            title="Play audio">
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
                    <p class="text-gray-600 text-lg">No game cards found. Please check that vocabulary items have images.</p>
                </div>
            @endif
        </div>

        <!-- Game Completion Modal -->
        <div id="game-completion" class="hidden fixed inset-0 z-50 p-4" style="display: none; background: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
            <div class="bg-white rounded-2xl shadow-xl p-8 md:p-12 text-center max-w-md w-full">
                <div class="text-6xl mb-4">🎉</div>
                <h2 class="text-3xl font-bold text-gray-800 mb-3">Congratulations!</h2>
                <p class="text-lg text-gray-600 mb-6">You completed the matching game!</p>
                <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-xl p-6 border-2 border-blue-200 mb-6">
                    <div class="flex justify-around gap-4">
                        <div>
                            <div class="text-sm font-semibold text-gray-600 mb-1">Final Time</div>
                            <div class="text-2xl font-bold text-blue-600" id="final-time">0:00</div>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-600 mb-1">Total Matches</div>
                            <div class="text-2xl font-bold text-purple-600" id="final-matches">{{ count($gameData['cards']) / 2 }}</div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <button onclick="location.reload()" 
                            class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">
                        Play Again
                    </button>
                    <a href="{{ route('lessons.show', $lesson->slug) }}" 
                       class="px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-300 active:scale-95 transition-all duration-200 shadow-sm">
                        Continue Lesson
                    </a>
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
    position: relative;
    overflow: visible;
}

/* Mobile: Grid layout - flexible columns that adapt to card count */
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
        display: grid;
        /* Use auto-fit to create as many columns as fit, minimum 90px each */
        grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));
        gap: 0.5rem;
        padding: 0.75rem;
        min-height: auto;
        overflow: visible;
        align-content: start;
        justify-items: stretch;
    }
    
    .game-card {
        position: relative !important;
        width: 100% !important;
        height: 85px !important;
        left: auto !important;
        top: auto !important;
        margin: 0;
        min-width: 0; /* Allow cards to shrink if needed */
    }
    
    .game-card .card-content {
        padding: 0.5rem !important;
    }
    
    /* Ensure cards maintain aspect ratio */
    .game-card .card-content {
        width: 100%;
        height: 100%;
    }
    
    .game-card img {
        max-width: 100%;
        max-height: 100%;
        object-fit: cover;
    }
    
    .card-word, .card-translation {
        font-size: 0.75rem !important;
        padding: 0.25rem !important;
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
    
    /* Stats section */
    .bg-white\/80 {
        padding: 0.75rem !important;
    }
    
    .text-2xl {
        font-size: 1.25rem !important;
    }
    
    .text-sm {
        font-size: 0.75rem !important;
    }
    
    /* If we have an odd number of cards, the last row will have fewer cards - that's fine */
    /* The grid will automatically center or left-align based on available space */
}

/* Desktop: Random positioning */
@media (min-width: 769px) {
    #game-area {
        display: block;
    }
    
    .game-card {
        position: absolute;
    }
}

.game-card {
    cursor: pointer;
    overflow: hidden;
    transition: all 0.3s ease;
}

.game-card:hover {
    z-index: 20 !important;
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

.card-word {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

/* Audio button styling handled by Tailwind classes */

/* Completion modal styling moved to Tailwind classes in HTML */

/* Mobile grid styles handled above */
</style>

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
        this.positionCardsRandomly();

        logActivityEvent('started', {
            mode: this.mode,
        });
    }
    
    positionCardsRandomly() {
        const gameArea = document.getElementById('game-area');
        if (!gameArea) return;
        
        // Check if mobile - if so, let CSS grid handle positioning
        const isMobile = window.innerWidth <= 768;
        if (isMobile) {
            // On mobile, just set card sizes and let CSS grid handle layout
            const cards = Array.from(this.cards);
            cards.forEach((card) => {
                // Remove any absolute positioning styles
                card.style.left = 'auto';
                card.style.top = 'auto';
                card.style.width = '100%';
                card.style.height = '90px';
                card.style.zIndex = '10';
            });
            return;
        }
        
        // Desktop: Random positioning
        // Wait for layout to be ready
        requestAnimationFrame(() => {
            const areaRect = gameArea.getBoundingClientRect();
            const areaWidth = areaRect.width - 40; // Padding
            const areaHeight = areaRect.height - 40; // Padding
            
            const cardWidth = 120;
            const cardHeight = 100;
            
            const cards = Array.from(this.cards);
            const positions = [];
            
            // Generate random positions with collision detection
            cards.forEach((card, index) => {
                let attempts = 0;
                let x, y;
                let validPosition = false;
                
                while (!validPosition && attempts < 50) {
                    x = Math.random() * (areaWidth - cardWidth);
                    y = Math.random() * (areaHeight - cardHeight);
                    
                    // Check for collisions with existing positions
                    validPosition = true;
                    for (const pos of positions) {
                        const distance = Math.sqrt(Math.pow(x - pos.x, 2) + Math.pow(y - pos.y, 2));
                        if (distance < Math.max(cardWidth, cardHeight) * 1.2) {
                            validPosition = false;
                            break;
                        }
                    }
                    
                    attempts++;
                }
                
                // If we couldn't find a good position, use a grid-like distribution with randomness
                if (!validPosition) {
                    const cols = Math.ceil(Math.sqrt(cards.length));
                    const col = index % cols;
                    const row = Math.floor(index / cols);
                    const cellWidth = areaWidth / cols;
                    const cellHeight = areaHeight / cols;
                    x = col * cellWidth + Math.random() * (cellWidth * 0.3);
                    y = row * cellHeight + Math.random() * (cellHeight * 0.3);
                }
                
                positions.push({ x, y });
                
                // Set card size and position
                card.style.width = cardWidth + 'px';
                card.style.height = cardHeight + 'px';
                card.style.left = x + 'px';
                card.style.top = y + 'px';
                card.style.zIndex = '10';
            });
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

// Function to dynamically adjust font size based on text length and card size
function adjustCardTextSizes() {
    const cardWords = document.querySelectorAll('.card-word, .card-translation');
    
    cardWords.forEach(element => {
        const card = element.closest('.game-card');
        if (!card) return;
        
        const cardContent = element.closest('.card-content');
        if (!cardContent) return;
        
        // Get default font sizes
        const isMobile = window.innerWidth <= 768;
        const defaultSize = element.classList.contains('card-word') 
            ? (isMobile ? 1.0 : 1.4) 
            : (isMobile ? 0.9 : 1.2);
        
        // Reset to default first and prevent wrapping - keep words on single line
        element.style.fontSize = defaultSize + 'rem';
        element.style.whiteSpace = 'nowrap';
        element.style.wordBreak = 'normal';
        element.style.overflowWrap = 'normal';
        element.style.textOverflow = 'ellipsis';
        
        // Reduce padding on mobile for longer words
        const isMobileDevice = window.innerWidth <= 768;
        const defaultPadding = isMobileDevice ? '0.4rem' : '1rem';
        element.style.padding = defaultPadding;
        
        // Force reflow
        void element.offsetWidth;
        
        // Get card dimensions
        const contentRect = cardContent.getBoundingClientRect();
        const availableWidth = contentRect.width - (parseFloat(getComputedStyle(element).paddingLeft) || 0) * 2;
        const availableHeight = contentRect.height - (parseFloat(getComputedStyle(element).paddingTop) || 0) * 2;
        
        // Get current font size
        let currentFontSize = parseFloat(getComputedStyle(element).fontSize);
        const minFontSize = isMobile ? 0.4 : 0.6; // Lower minimum for mobile
        
        // Binary search for optimal font size that fits on single line
        let minSize = minFontSize;
        let maxSize = defaultSize;
        let optimalSize = defaultSize;
        
        // Test if text fits at current size (single line, no wrap)
        const testFit = (fontSize) => {
            element.style.fontSize = fontSize + 'rem';
            element.style.whiteSpace = 'nowrap';
            void element.offsetWidth;
            const elementRect = element.getBoundingClientRect();
            return elementRect.width <= availableWidth * 0.95;
        };
        
        // Binary search for best fit
        for (let i = 0; i < 12; i++) { // Max 12 iterations for precision
            const testSize = (minSize + maxSize) / 2;
            if (testFit(testSize)) {
                optimalSize = testSize;
                minSize = testSize;
            } else {
                maxSize = testSize;
            }
            if (maxSize - minSize < 0.03) break; // Stop when close enough
        }
        
        // Apply optimal size
        const finalSize = Math.max(minFontSize, optimalSize);
        element.style.fontSize = finalSize + 'rem';
        element.style.whiteSpace = 'nowrap';
        void element.offsetWidth;
        
        // If text is still too long, reduce padding further and try again
        const finalRect = element.getBoundingClientRect();
        if (finalRect.width > availableWidth * 0.95 && isMobileDevice) {
            // Reduce padding to minimum
            element.style.padding = '0.2rem';
            void element.offsetWidth;
            const newAvailableWidth = cardContent.getBoundingClientRect().width - 0.4; // 0.2rem * 2
            
            // Recalculate with reduced padding
            if (finalRect.width > newAvailableWidth * 0.95) {
                const scaleFactor = (newAvailableWidth * 0.95) / finalRect.width;
                const scaledSize = Math.max(minFontSize, finalSize * scaleFactor);
                element.style.fontSize = scaledSize + 'rem';
            }
        }
    });
}

// Start the game when page loads
let matchingGameInstance = null;

document.addEventListener('DOMContentLoaded', function() {
    matchingGameInstance = new MatchingGame({
        mode: '{{ $mode }}',
    });
    
    // Reposition cards on window resize
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            if (matchingGameInstance) {
                matchingGameInstance.positionCardsRandomly();
            }
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

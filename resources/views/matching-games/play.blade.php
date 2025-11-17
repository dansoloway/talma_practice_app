@extends('layouts.app')

@section('title', 'Vocabulary Matching Game')

@section('content')
<style>
/* Hide footer on game pages for mobile */
@media (max-width: 768px) {
    .footer {
        display: none;
    }
    
    .game-header {
        padding: 0.5rem 0;
    }
    
    .game-title {
        font-size: 1.2rem;
        margin: 0.5rem 0;
    }
    
    .game-header .back-link {
        font-size: 0.9rem;
    }
}
</style>
<div class="matching-game-container">
    <div class="game-header">
        <a href="{{ route('lessons.show', $lesson->slug) }}" class="back-link">&larr; Back to Lesson</a>
        <h1 class="game-title">{{ $matching_game->title }}</h1>
    </div>
    
    @if(isset($gameData['available_modes']) && count($gameData['available_modes']) > 1)
        <div class="mode-selector-container">
            <div class="mode-selector">
                <label for="mode-select">Match English with:</label>
                <select id="mode-select" onchange="changeMode(this.value)">
                    @foreach($gameData['available_modes'] as $modeKey => $modeLabel)
                        <option value="{{ $modeKey }}" {{ $mode === $modeKey ? 'selected' : '' }}>
                            {{ $modeLabel }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif

    <div class="game-stats">
        <div class="stat">
            <span class="stat-label">Matches Found:</span>
            <span class="stat-value" id="matches-found">0</span>
        </div>
        <div class="stat">
            <span class="stat-label">Total Pairs:</span>
            <span class="stat-value" id="total-pairs">{{ count($gameData['cards']) / 2 }}</span>
        </div>
        <div class="stat">
            <span class="stat-label">Time:</span>
            <span class="stat-value" id="game-time">0:00</span>
        </div>
    </div>

    <div class="game-grid" id="game-grid" style="grid-template-columns: repeat({{ $gameData['grid_size'] }}, 1fr);">
        @if(isset($gameData['cards']) && count($gameData['cards']) > 0)
            @foreach($gameData['cards'] as $index => $card)
                <div class="game-card" data-card-id="{{ $card['id'] }}" data-vocab-id="{{ $card['vocab_id'] }}" data-type="{{ $card['type'] }}">
                    <div class="card-content">
                        @if($card['type'] === 'audio')
                            @if(!empty($card['audio_path']))
                                <button class="play-audio-strip" data-audio="{{ $card['audio_path'] }}" title="Play audio">
                                    <i class="fas fa-play"></i>
                                </button>
                            @else
                                <div class="card-word" style="color: red;">No audio</div>
                            @endif
                        @elseif($card['type'] === 'image' && $card['content'])
                            <img src="{{ $card['content'] }}" alt="{{ $card['word'] }}" class="card-image">
                        @elseif($card['type'] === 'hebrew')
                            <div class="card-translation hebrew">{{ $card['content'] }}</div>
                        @elseif($card['type'] === 'arabic')
                            <div class="card-translation arabic">{{ $card['content'] }}</div>
                        @else
                            <div class="card-word">{{ $card['content'] }}</div>
                            @if($card['audio_path'] && $mode !== 'image' && $mode !== 'audio')
                                <button class="play-audio-btn" data-audio="{{ $card['audio_path'] }}" title="Play audio">
                                    <i class="fas fa-volume-up"></i>
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: #666;">
                <p>No game cards found. Please check that vocabulary items have images.</p>
            </div>
        @endif
    </div>

    <div class="game-completion" id="game-completion" style="display: none;">
        <div class="completion-content">
            <h2>🎉 Congratulations!</h2>
            <p>You completed the matching game!</p>
            <div class="completion-stats">
                <div class="completion-stat">
                    <span class="stat-label">Final Time:</span>
                    <span class="stat-value" id="final-time">0:00</span>
                </div>
                <div class="completion-stat">
                    <span class="stat-label">Total Matches:</span>
                    <span class="stat-value" id="final-matches">{{ count($gameData['cards']) / 2 }}</span>
                </div>
            </div>
            <div class="completion-actions">
                <button onclick="location.reload()" class="btn btn-primary">Play Again</button>
                <a href="{{ route('lessons.show', $lesson->slug) }}" class="btn btn-secondary">Continue Lesson</a>
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

.game-stats {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-bottom: 2rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.stat {
    text-align: center;
}

.stat-label {
    display: block;
    font-size: 0.875rem;
    color: var(--color-text-light);
    margin-bottom: 0.25rem;
}

.stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--color-primary);
}

.game-grid {
    display: grid;
    gap: 0.75rem;
    margin-bottom: 2rem;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.game-card {
    aspect-ratio: 1;
    cursor: pointer;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    border: 2px solid var(--color-primary);
    background: white;
}

.game-card:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}

.game-card.selected {
    border: 5px solid var(--color-success, #28a745);
    background: var(--color-success-light, #d4edda);
    transform: scale(1.05);
    box-shadow: 0 8px 20px rgba(40, 167, 69, 0.4);
}

.game-card.correct {
    border-color: var(--color-success);
    background: var(--color-success-light, #d4edda);
    transform: scale(1.05);
}

.game-card.incorrect {
    border-color: var(--color-danger);
    background: var(--color-danger-light, #f8d7da);
    animation: shake 0.3s ease-in-out;
}

.game-card.matched {
    opacity: 0;
    transform: scale(0.8);
    transition: all 0.3s ease;
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
    padding: 0.5rem;
    overflow: hidden;
}

/* For audio cards, use column layout with no padding */
.game-card[data-type="audio"] .card-content {
    flex-direction: column;
    padding: 0;
}

.card-image {
    width: 80%;
    height: 80%;
    object-fit: cover;
    border-radius: 4px;
}

.card-word {
    font-size: 1.4rem;
    font-weight: 700;
    color: #1f2937;
    padding: 1rem;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 60px;
    word-wrap: break-word;
    overflow-wrap: break-word;
    hyphens: none;
    -webkit-hyphens: none;
    -moz-hyphens: none;
    -ms-hyphens: none;
    max-width: 100%;
    width: 100%;
    box-sizing: border-box;
    line-height: 1.2;
}

.play-audio-btn {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: var(--color-primary);
    color: white;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    z-index: 10;
}

.play-audio-btn:hover {
    background: var(--color-primary-dark);
    transform: scale(1.1);
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
}

.play-audio-btn i {
    font-size: 1.2rem;
}

.play-audio-btn:active {
    transform: scale(0.95);
}

.game-completion {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.completion-content {
    background: white;
    padding: 3rem;
    border-radius: 12px;
    text-align: center;
    max-width: 400px;
    width: 90%;
}

.completion-content h2 {
    color: var(--color-success);
    margin-bottom: 1rem;
}

.completion-stats {
    display: flex;
    justify-content: space-around;
    margin: 1.5rem 0;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.completion-stat {
    text-align: center;
}

.completion-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 1.5rem;
}

@media (max-width: 768px) {
    .game-grid {
        gap: 0.5rem;
    }
    
    .game-stats {
        flex-direction: column;
        gap: 1rem;
    }
    
    .completion-actions {
        flex-direction: column;
    }
    
    .card-word {
        font-size: 1rem;
        padding: 0.5rem;
        min-height: 50px;
    }
    
    .card-translation {
        font-size: 0.9rem;
        padding: 0.5rem;
        min-height: 50px;
    }
    
    .card-content {
        padding: 0.25rem;
    }
}
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
        this.gridSize = config.gridSize;
        
        this.init();

        logActivityEvent('started', {
            mode: this.mode,
            grid_size: this.gridSize,
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
        
        // Add selection highlight (blue for first selection)
        card.classList.add('selected');
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
            card1.classList.remove('selected');
            card2.classList.remove('selected');
            card1.classList.add('correct');
            card2.classList.add('correct');
            
            // After showing green, mark as matched and hide
            setTimeout(() => {
                card1.classList.remove('correct');
                card2.classList.remove('correct');
                card1.classList.add('matched');
                card2.classList.add('matched');
                this.matches++;
                this.updateStats();
                
                // Check if game is complete
                if (this.matches === this.cards.length / 2) {
                    this.completeGame();
                }
            }, 400);
        } else {
            // No match - show red highlight and shake
            card1.classList.add('incorrect');
            card2.classList.add('incorrect');
            
            // After animation, remove incorrect styling and selection
            setTimeout(() => {
                card1.classList.remove('incorrect', 'selected');
                card2.classList.remove('incorrect', 'selected');
            }, 300);
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
        document.getElementById('game-completion').style.display = 'flex';

        logActivityEvent('completed', {
            mode: this.mode,
            grid_size: this.gridSize,
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
        
        // Reset to default first
        element.style.fontSize = defaultSize + 'rem';
        
        // Force reflow
        void element.offsetWidth;
        
        // Get card dimensions
        const contentRect = cardContent.getBoundingClientRect();
        const availableWidth = contentRect.width;
        const availableHeight = contentRect.height;
        
        // Measure text width without wrapping by temporarily disabling wrap
        const originalWhiteSpace = element.style.whiteSpace;
        element.style.whiteSpace = 'nowrap';
        void element.offsetWidth;
        const textWidth = element.getBoundingClientRect().width;
        element.style.whiteSpace = originalWhiteSpace;
        
        // Get current font size
        let currentFontSize = parseFloat(getComputedStyle(element).fontSize);
        
        // If text is wider than available space, reduce font size
        if (textWidth > availableWidth * 0.95) {
            const minFontSize = isMobile ? 0.6 : 0.8; // Minimum font size in rem
            
            // Calculate proportional font size
            const scaleFactor = (availableWidth * 0.95) / textWidth;
            const newFontSize = Math.max(minFontSize, currentFontSize * scaleFactor);
            
            element.style.fontSize = newFontSize + 'rem';
            void element.offsetWidth;
            
            // Check if height also fits after resizing
            const elementRect = element.getBoundingClientRect();
            if (elementRect.height > availableHeight * 0.95) {
                const heightScaleFactor = (availableHeight * 0.95) / elementRect.height;
                const finalFontSize = Math.max(minFontSize, parseFloat(getComputedStyle(element).fontSize) * heightScaleFactor);
                element.style.fontSize = finalFontSize + 'rem';
            }
        } else {
            // Text fits, but check if we can make it larger (if it was previously reduced)
            // Only increase if it's significantly smaller than default
            if (currentFontSize < defaultSize * 0.9) {
                // Try increasing gradually
                let testSize = Math.min(defaultSize, currentFontSize * 1.1);
                element.style.fontSize = testSize + 'rem';
                element.style.whiteSpace = 'nowrap';
                void element.offsetWidth;
                const testWidth = element.getBoundingClientRect().width;
                element.style.whiteSpace = originalWhiteSpace;
                
                if (testWidth <= availableWidth * 0.95) {
                    element.style.fontSize = testSize + 'rem';
                } else {
                    element.style.fontSize = currentFontSize + 'rem';
                }
            }
        }
    });
}

// Start the game when page loads
document.addEventListener('DOMContentLoaded', function() {
    new MatchingGame({
        mode: '{{ $mode }}',
        gridSize: {{ $gameData['grid_size'] }},
    });
    
    // Adjust text sizes after a brief delay to ensure layout is complete
    setTimeout(adjustCardTextSizes, 200);
    
    // Adjust on window resize
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(adjustCardTextSizes, 150);
    });
    
    // Handle audio playback
    document.addEventListener('click', function(e) {
        // Check if click is on regular audio button (for word cards in other modes)
        const btn = e.target.closest('.play-audio-btn');
        if (btn) {
            e.stopPropagation(); // Prevent card selection for regular audio buttons
            const audioPath = btn.dataset.audio;
            if (audioPath) {
                const audio = new Audio(audioPath);
                audio.play().catch(err => console.log('Audio play failed:', err));
            }
        }
        // For audio strip buttons, play audio but allow card selection to proceed
        const audioStrip = e.target.closest('.play-audio-strip');
        if (audioStrip) {
            const audioPath = audioStrip.dataset.audio;
            if (audioPath) {
                const audio = new Audio(audioPath);
                audio.play().catch(err => console.log('Audio play failed:', err));
            }
            // Don't stop propagation - let the card selection happen
        }
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

.card-translation {
    font-size: 1.2rem;
    font-weight: 600;
    text-align: center;
    padding: 1rem;
    border-radius: 8px;
    min-height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    word-wrap: break-word;
    overflow-wrap: break-word;
    hyphens: none;
    -webkit-hyphens: none;
    -moz-hyphens: none;
    -ms-hyphens: none;
    max-width: 100%;
    width: 100%;
    box-sizing: border-box;
    line-height: 1.2;
}

.card-translation.hebrew {
    background-color: #e8f4fd;
    color: #1e40af;
    border: 2px solid #bfdbfe;
}

.card-translation.arabic {
    background-color: #f0fdf4;
    color: #166534;
    border: 2px solid #bbf7d0;
}

.game-card[data-type="hebrew"] .card-content {
    background: linear-gradient(135deg, #e8f4fd 0%, #dbeafe 100%);
}

.game-card[data-type="arabic"] .card-content {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
}

.game-card[data-type="word"] .card-content {
    background: white;
    border: 2px solid #e5e7eb;
    color: #374151;
}

/* Audio card styles - button fills entire card */
.game-card[data-type="audio"] .card-content {
    justify-content: center;
    padding: 0;
}

.game-card[data-type="audio"] .play-audio-strip {
    width: 100%;
    height: 100%;
    background: var(--color-primary);
    color: white;
    border: none;
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 3.5rem;
    font-weight: 600;
    z-index: 10;
}

.game-card[data-type="audio"] .play-audio-strip i {
    font-size: 3.5rem;
}

.play-audio-strip {
    width: 100%;
    background: var(--color-primary);
    color: white;
    border: none;
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1rem;
    font-weight: 600;
    z-index: 10;
    border-top: 2px solid rgba(255, 255, 255, 0.2);
}

.play-audio-strip:hover {
    background: var(--color-primary-dark, #2563eb);
    transform: none;
}

.play-audio-strip:active {
    background: var(--color-primary-dark, #1e40af);
    transform: scale(0.98);
}

.play-audio-strip i {
    font-size: 1rem;
}

/* Ensure other card types still work with flex column */
.game-card[data-type="image"] .card-content,
.game-card[data-type="hebrew"] .card-content,
.game-card[data-type="arabic"] .card-content,
.game-card[data-type="word"] .card-content {
    flex-direction: row;
    padding: 0.5rem;
}

.game-card[data-type="image"] .card-image {
    width: 80%;
    height: 80%;
}
</style>
@endsection

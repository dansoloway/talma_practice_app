@extends('layouts.app')

@section('title', 'Vocabulary Matching Game')

@section('content')
<div class="matching-game-container">
    <div class="game-header">
        <a href="{{ route('lessons.show', $lesson->slug) }}" class="back-link">&larr; Back to Lesson</a>
        <h1 class="game-title">{{ $matching_game->title }}</h1>
    </div>

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
                        @if($card['type'] === 'image' && $card['content'])
                            <img src="{{ $card['content'] }}" alt="{{ $card['word'] }}" class="card-image">
                        @else
                            <div class="card-word">{{ $card['content'] }}</div>
                        @endif
                        @if($card['audio_path'])
                            <button class="play-audio-btn" data-audio="{{ $card['audio_path'] }}" title="Play audio">
                                🔊
                            </button>
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
    border-color: var(--color-success);
    background: var(--color-success-light, #d4edda);
    transform: scale(1.05);
}

.game-card.incorrect {
    border-color: var(--color-danger);
    background: var(--color-danger-light, #f8d7da);
    animation: shake 0.5s ease-in-out;
}

.game-card.matched {
    opacity: 0;
    transform: scale(0.8);
    transition: all 0.5s ease;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-3px); }
    20%, 40%, 60%, 80% { transform: translateX(3px); }
}

.card-content {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem;
}

.card-image {
    width: 80%;
    height: 80%;
    object-fit: cover;
    border-radius: 4px;
}

.card-word {
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--color-text);
    padding: 0.5rem;
}

.play-audio-btn {
    position: absolute;
    top: 0.25rem;
    right: 0.25rem;
    background: var(--color-primary);
    color: white;
    border: none;
    border-radius: 50%;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s;
    z-index: 10;
}

.play-audio-btn:hover {
    background: var(--color-primary-dark);
    transform: scale(1.1);
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
}
</style>

<script>
class MatchingGame {
    constructor() {
        this.cards = document.querySelectorAll('.game-card');
        this.flippedCards = [];
        this.matches = 0;
        this.startTime = Date.now();
        this.gameInterval = null;
        
        this.init();
    }
    
    init() {
        this.cards.forEach(card => {
            card.addEventListener('click', () => this.flipCard(card));
        });
        
        this.startTimer();
    }
    
    flipCard(card) {
        // Don't select if already matched
        if (card.classList.contains('matched')) {
            return;
        }
        
        // Don't select if already have 2 cards selected
        if (this.flippedCards.length >= 2) {
            return;
        }
        
        // Add selection highlight
        card.classList.add('selected');
        this.flippedCards.push(card);
        
        // Check for match when 2 cards are selected
        if (this.flippedCards.length === 2) {
            setTimeout(() => this.checkMatch(), 1000);
        }
    }
    
    checkMatch() {
        const [card1, card2] = this.flippedCards;
        const vocabId1 = card1.dataset.vocabId;
        const vocabId2 = card2.dataset.vocabId;
        
        if (vocabId1 === vocabId2) {
            // Match found! Mark as matched and hide
            card1.classList.remove('selected');
            card2.classList.remove('selected');
            card1.classList.add('matched');
            card2.classList.add('matched');
            this.matches++;
            this.updateStats();
            
            // Check if game is complete
            if (this.matches === this.cards.length / 2) {
                this.completeGame();
            }
        } else {
            // No match - show red highlight and shake
            card1.classList.add('incorrect');
            card2.classList.add('incorrect');
            
            // After animation, remove incorrect styling and selection
            setTimeout(() => {
                card1.classList.remove('incorrect', 'selected');
                card2.classList.remove('incorrect', 'selected');
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
        
        // Show completion screen
        const finalTime = document.getElementById('game-time').textContent;
        document.getElementById('final-time').textContent = finalTime;
        document.getElementById('final-matches').textContent = this.matches;
        document.getElementById('game-completion').style.display = 'flex';
    }
}

// Start the game when page loads
document.addEventListener('DOMContentLoaded', function() {
    new MatchingGame();
    
    // Handle audio playback
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('play-audio-btn')) {
            e.stopPropagation(); // Prevent card selection
            const audioPath = e.target.dataset.audio;
            if (audioPath) {
                const audio = new Audio(audioPath);
                audio.play().catch(err => console.log('Audio play failed:', err));
            }
        }
    });
});
</script>
@endsection

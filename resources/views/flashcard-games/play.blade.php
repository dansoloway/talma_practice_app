@extends('layouts.app')

@section('title', 'Flashcard Game: ' . $flashcardGame->title)

@section('content')
<div class="container">
    <div class="game-header">
        <a href="{{ route('lessons.show', $lesson->slug) }}" class="back-link">&larr; Back to Lesson</a>
        <h1 class="page-title">{{ $flashcardGame->title }}</h1>
        <p class="game-description">Practice vocabulary with interactive flashcards</p>
        
        @if(isset($gameData['available_modes']) && count($gameData['available_modes']) > 1)
            <div class="mode-selector">
                <label for="mode-select">Practice with:</label>
                <select id="mode-select" onchange="changeMode(this.value)">
                    @foreach($gameData['available_modes'] as $modeKey => $modeLabel)
                        <option value="{{ $modeKey }}" {{ $mode === $modeKey ? 'selected' : '' }}>
                            {{ $modeLabel }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>

    <div id="flashcard-app" class="flashcard-container">
        <!-- Game Selection Screen -->
        <div class="game-selection" id="game-selection">
            <h2>Choose Your Game Type</h2>
            <p>Select how you want to practice your vocabulary:</p>
            <div class="game-type-grid">
                @foreach($gameData['game_types'] as $type)
                    <button class="game-type-btn" data-type="{{ $type }}">
                        <div class="game-type-icon">
                            @if($type === 'image_to_word')
                                🖼️ → 📝
                            @elseif($type === 'image_to_audio')
                                🖼️ → 🔊
                            @elseif($type === 'audio_to_image')
                                🔊 → 🖼️
                            @elseif($type === 'audio_to_word')
                                🔊 → 📝
                            @endif
                        </div>
                        <div class="game-type-label">
                            {{ \App\Models\FlashcardGame::getGameTypes()[$type] }}
                        </div>
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Game Screen -->
        <div class="game-screen hidden" id="game-screen">
            <div class="game-progress">
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                <div class="progress-text">
                    <span id="current-card">1</span> of <span id="total-cards">{{ $gameData['cards_per_game'] }}</span>
                </div>
            </div>

            <div class="flashcard-container">
                <div class="flashcard" id="flashcard">
                    <!-- Content will be dynamically loaded -->
                </div>
            </div>

            <div class="game-controls">
                <button id="next-btn" class="btn btn-primary hidden">Next Card</button>
                <button id="restart-btn" class="btn btn-secondary">Restart Game</button>
            </div>
        </div>

        <!-- Game Complete Screen -->
        <div class="game-complete hidden" id="game-complete">
            <div class="completion-content">
                <h2>🎉 Game Complete!</h2>
                <p>Great job practicing your vocabulary!</p>
                <div class="completion-actions">
                    <button id="play-again-btn" class="btn btn-primary">Play Again</button>
                    <a href="{{ route('lessons.show', $lesson->slug) }}" class="btn btn-secondary">Back to Lesson</a>
                </div>
            </div>
        </div>
    </div>
</div>

<audio id="game-audio" preload="auto"></audio>

<script>
const gameData = @json($gameData);
const lessonSlug = '{{ $lesson->slug }}';

// Game state
let currentGameType = null;
let currentCardIndex = 0;
let currentMode = '{{ $mode }}';
let gameCards = [];

// DOM elements
const gameSelection = document.getElementById('game-selection');
const gameScreen = document.getElementById('game-screen');
const gameComplete = document.getElementById('game-complete');
const flashcard = document.getElementById('flashcard');
const progressFill = document.getElementById('progress-fill');
const currentCardSpan = document.getElementById('current-card');
const totalCardsSpan = document.getElementById('total-cards');
const nextBtn = document.getElementById('next-btn');
const restartBtn = document.getElementById('restart-btn');
const playAgainBtn = document.getElementById('play-again-btn');

// Mode change functionality
function changeMode(mode) {
    const url = new URL(window.location);
    url.searchParams.set('mode', mode);
    window.location.href = url.toString();
}

// Initialize game
document.addEventListener('DOMContentLoaded', function() {
    // Set up game type buttons
    document.querySelectorAll('.game-type-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            currentGameType = this.dataset.type;
            startGame();
        });
    });

    // Set up control buttons
    nextBtn.addEventListener('click', nextCard);
    restartBtn.addEventListener('click', restartGame);
    playAgainBtn.addEventListener('click', restartGame);
});

function startGame() {
    // Shuffle cards for this game
    gameCards = [...gameData.cards].sort(() => Math.random() - 0.5).slice(0, gameData.cards_per_game);
    currentCardIndex = 0;
    correctAnswers = 0;
    userAnswers = [];

    // Show game screen
    gameSelection.classList.add('hidden');
    gameScreen.classList.remove('hidden');
    gameComplete.classList.add('hidden');

    // Load first card
    loadCard();
}

function loadCard() {
    if (currentCardIndex >= gameCards.length) {
        endGame();
        return;
    }

    const card = gameCards[currentCardIndex];
    updateProgress();
    renderCard(card);
}

function renderCard(card) {
    const cardHtml = generateCardHTML(card);
    flashcard.innerHTML = cardHtml;
    
    // Set up event listeners for this card
    setupCardEvents(card);
    
    // Hide next button initially
    nextBtn.classList.add('hidden');
}

function getAnswerText(card) {
    switch(currentMode) {
        case 'hebrew':
            return card.hebrew_translation || card.english_word;
        case 'arabic':
            return card.arabic_translation || card.english_word;
        case 'image':
        default:
            return card.english_word;
    }
}

function generateCardHTML(card) {
    switch(currentGameType) {
        case 'image_to_word':
            const imageAnswer = getAnswerText(card);
            return `
                <div class="card-content">
                    <div class="card-image">
                        <img src="${card.image_path}" alt="${card.english_word}" />
                    </div>
                    <div class="card-prompt">What is this word?</div>
                    <div class="card-answer hidden" id="card-answer">
                        <div class="answer-content">
                            <span class="answer-text">${imageAnswer}</span>
                            ${card.audio_path ? `
                                <button class="play-answer-audio-btn" data-audio="${card.audio_path}" title="Listen to word">
                                    <i class="fas fa-volume-up"></i>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        case 'image_to_audio':
            return `
                <div class="card-content">
                    <div class="card-image">
                        <img src="${card.image_path}" alt="${card.english_word}" />
                    </div>
                    <div class="card-prompt">Choose the correct audio for this image:</div>
                    <div class="audio-options" id="audio-options">
                        <!-- Options will be generated by JavaScript -->
                    </div>
                </div>
            `;
        case 'audio_to_image':
            return `
                <div class="card-content">
                    <div class="card-audio">
                        <button class="play-audio-btn" data-audio="${card.audio_path}">
                            <i class="fas fa-play"></i> Play Word
                        </button>
                    </div>
                    <div class="card-prompt">Choose the correct image for this word:</div>
                    <div class="image-options" id="image-options">
                        <!-- Options will be generated by JavaScript -->
                    </div>
                </div>
            `;
        case 'audio_to_word':
            return `
                <div class="card-content">
                    <div class="card-audio">
                        <button class="play-audio-btn" data-audio="${card.audio_path}">
                            <i class="fas fa-play"></i> Play Word
                        </button>
                    </div>
                    <div class="card-prompt">Choose the correct ${currentMode === 'hebrew' ? 'Hebrew' : currentMode === 'arabic' ? 'Arabic' : 'word'}:</div>
                    <div class="word-options" id="word-options">
                        <!-- Options will be generated by JavaScript -->
                    </div>
                </div>
            `;
        default:
            return '<div class="error">Unknown game type</div>';
    }
}

function setupCardEvents(card) {
    // Audio playback
    document.querySelectorAll('.play-audio-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const audioPath = this.dataset.audio;
            if (audioPath) {
                playAudio(audioPath);
            }
        });
    });

    // Answer audio playback (for image_to_word mode)
    document.querySelectorAll('.play-answer-audio-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const audioPath = this.dataset.audio;
            if (audioPath) {
                playAudio(audioPath);
            }
        });
    });

    // Generate options based on game type
    generateOptions(card);
}

function generateOptions(correctCard) {
    let optionsContainer;
    let correctOptionId = correctCard.id;
    
    // Get all other cards for wrong options
    const wrongOptions = gameCards.filter(card => card.id !== correctCard.id);
    const shuffledWrong = wrongOptions.sort(() => Math.random() - 0.5).slice(0, 3); // Get 3 wrong options
    
    // Combine correct and wrong options
    const allOptions = [correctCard, ...shuffledWrong].sort(() => Math.random() - 0.5);
    
    switch(currentGameType) {
        case 'image_to_audio':
            optionsContainer = document.getElementById('audio-options');
            optionsContainer.innerHTML = '';
            allOptions.forEach(option => {
                const optionBtn = document.createElement('button');
                optionBtn.className = 'audio-option';
                optionBtn.dataset.optionId = option.id;
                optionBtn.dataset.correct = option.id === correctOptionId;
                optionBtn.innerHTML = `<i class="fas fa-play"></i> Play Audio`;
                optionBtn.addEventListener('click', function() {
                    selectOption(this, option.audio_path);
                });
                optionsContainer.appendChild(optionBtn);
            });
            break;
            
        case 'audio_to_image':
            optionsContainer = document.getElementById('image-options');
            optionsContainer.innerHTML = '';
            allOptions.forEach(option => {
                const optionBtn = document.createElement('button');
                optionBtn.className = 'image-option';
                optionBtn.dataset.optionId = option.id;
                optionBtn.dataset.correct = option.id === correctOptionId;
                if (option.image_path) {
                    optionBtn.innerHTML = `<img src="${option.image_path}" alt="${option.english_word}" />`;
                } else {
                    optionBtn.innerHTML = 'No Image';
                    optionBtn.disabled = true;
                }
                optionBtn.addEventListener('click', function() {
                    selectOption(this);
                });
                optionsContainer.appendChild(optionBtn);
            });
            break;
            
        case 'audio_to_word':
            optionsContainer = document.getElementById('word-options');
            optionsContainer.innerHTML = '';
            allOptions.forEach(option => {
                const optionBtn = document.createElement('button');
                optionBtn.className = 'word-option';
                optionBtn.dataset.optionId = option.id;
                optionBtn.dataset.correct = option.id === correctOptionId;
                optionBtn.textContent = getAnswerText(option);
                optionBtn.addEventListener('click', function() {
                    selectOption(this);
                });
                optionsContainer.appendChild(optionBtn);
            });
            break;
    }
}

function selectOption(optionElement, audioPath = null) {
    // Remove previous selections
    document.querySelectorAll('.audio-option, .image-option, .word-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    
    // Mark this option as selected
    optionElement.classList.add('selected');
    
    // Play audio if it's an audio option
    if (audioPath) {
        playAudio(audioPath);
    }
    
    // Automatically check the answer
    setTimeout(() => {
        checkAnswer(optionElement);
    }, 500); // Small delay to show selection
}

function checkAnswer(selectedOption) {
    const isCorrect = selectedOption.dataset.correct === 'true';
    
    if (isCorrect) {
        correctAnswers++;
        selectedOption.classList.add('correct');
        showFeedback('correct');
    } else {
        selectedOption.classList.add('incorrect');
        // Show correct answer
        document.querySelector(`[data-correct="true"]`).classList.add('correct-feedback');
        showFeedback('incorrect');
    }
    
    // Show next button
    nextBtn.classList.remove('hidden');
}

function showFeedback(type) {
    const feedback = document.createElement('div');
    feedback.className = `feedback feedback-${type}`;
    feedback.textContent = type === 'correct' ? '✓ Correct!' : '✗ Try again';
    flashcard.appendChild(feedback);
    
    setTimeout(() => {
        feedback.remove();
    }, 2000);
}

function nextCard() {
    currentCardIndex++;
    loadCard();
}

function endGame() {
    // Update stats
    correctCountSpan.textContent = correctAnswers;
    totalCountSpan.textContent = gameCards.length;
    
    // Show completion screen
    gameScreen.classList.add('hidden');
    gameComplete.classList.remove('hidden');
}

function restartGame() {
    // Reset to game selection
    gameSelection.classList.remove('hidden');
    gameScreen.classList.add('hidden');
    gameComplete.classList.add('hidden');
}

function updateProgress() {
    const progress = (currentCardIndex / gameCards.length) * 100;
    progressFill.style.width = progress + '%';
    currentCardSpan.textContent = currentCardIndex + 1;
}

function playAudio(audioPath) {
    const audio = document.getElementById('game-audio');
    audio.src = audioPath;
    audio.play();
}
</script>
@endsection

@push('styles')
<style>
.flashcard-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 2rem;
}

.mode-selector {
    margin-top: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    display: inline-block;
}

.mode-selector label {
    font-weight: 600;
    margin-right: 0.5rem;
    color: #495057;
}

.mode-selector select {
    padding: 0.5rem 1rem;
    border: 2px solid #dee2e6;
    border-radius: 6px;
    background: white;
    font-size: 1rem;
    cursor: pointer;
}

.mode-selector select:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.game-selection {
    text-align: center;
    padding: 2rem;
}

.game-type-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 2rem;
}

.game-type-btn {
    background: var(--color-white);
    border: 2px solid var(--color-border);
    border-radius: 8px;
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
}

.game-type-btn:hover {
    border-color: var(--color-primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.game-type-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.game-type-label {
    font-weight: 500;
    color: var(--color-text);
}

.game-progress {
    margin-bottom: 2rem;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: var(--color-border);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.progress-fill {
    height: 100%;
    background: var(--color-primary);
    transition: width 0.3s ease;
}

.progress-text {
    text-align: center;
    color: var(--color-text-light);
    font-size: 0.9rem;
}

.flashcard {
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.card-content {
    text-align: center;
    width: 100%;
}

.card-image img {
    max-width: 200px;
    max-height: 200px;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.card-prompt {
    font-size: 1.2rem;
    font-weight: 500;
    margin-bottom: 1.5rem;
    color: var(--color-text);
}

.card-answer {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--color-primary);
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    margin-top: 1rem;
}

.audio-options, .image-options, .word-options {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.audio-option, .image-option, .word-option {
    padding: 1rem 1.5rem;
    border: 2px solid var(--color-border);
    border-radius: 8px;
    background: var(--color-white);
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 120px;
}

.audio-option:hover, .image-option:hover, .word-option:hover {
    border-color: var(--color-primary);
    transform: translateY(-2px);
}

.audio-option.selected, .image-option.selected, .word-option.selected {
    border-color: var(--color-primary);
    background: #e3f2fd;
}

.audio-option.correct, .image-option.correct, .word-option.correct {
    border-color: var(--color-success);
    background: #d4edda;
    color: #155724;
}

.audio-option.incorrect, .image-option.incorrect, .word-option.incorrect {
    border-color: var(--color-danger);
    background: #f8d7da;
    color: #721c24;
}

.audio-option.correct-feedback, .image-option.correct-feedback, .word-option.correct-feedback {
    border-color: var(--color-success);
    background: #d4edda;
    color: #155724;
    animation: pulse 0.5s infinite alternate;
}

.image-option img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 4px;
}

/* Answer content styling */
.answer-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    justify-content: center;
}

.answer-text {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--color-text);
}

.play-answer-audio-btn {
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
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.play-answer-audio-btn:hover {
    background: var(--color-primary-dark);
    transform: scale(1.1);
}

.play-answer-audio-btn i {
    font-size: 1rem;
}

.game-controls {
    text-align: center;
}

.feedback {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    padding: 1rem 2rem;
    border-radius: 8px;
    font-weight: bold;
    font-size: 1.2rem;
    z-index: 10;
}

.feedback-correct {
    background: var(--color-success);
    color: white;
}

.feedback-incorrect {
    background: var(--color-danger);
    color: white;
}

.game-complete {
    text-align: center;
    padding: 3rem;
}

.completion-stats {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin: 2rem 0;
}

.stat {
    text-align: center;
}

.stat-value {
    display: block;
    font-size: 2rem;
    font-weight: bold;
    color: var(--color-primary);
}

.stat-label {
    color: var(--color-text-light);
    font-size: 0.9rem;
}

.completion-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
}

@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.02); opacity: 0.9; }
    100% { transform: scale(1); opacity: 1; }
}
</style>
@endpush
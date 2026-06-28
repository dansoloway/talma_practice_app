@extends('layouts.app')

@section('title', 'Flashcard Game: ' . $flashcardGame->title)

@section('content')
<style>
/* Hide footer on game pages for mobile */
@media (max-width: 768px) {
    .footer {
        display: none;
    }
    
    .game-header {
        padding: 0.5rem 0;
        margin-bottom: 1rem;
    }
    
    .game-header .page-title {
        font-size: 1.2rem;
        margin: 0.5rem 0;
    }
    
    .game-header .game-description {
        display: none;
    }
    
    .game-header .back-link {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }
    
    .container {
        padding: 0.5rem;
        max-width: 100%;
    }
    
    .flashcard-container {
        padding: 0.5rem;
        max-width: 100%;
    }
    
    .flashcard {
        padding: 1rem !important;
        min-height: 250px !important;
        margin-bottom: 0.75rem;
    }
    
    .card-image img {
        max-width: 180px !important;
        max-height: 180px !important;
    }
    
    .card-prompt {
        font-size: 1rem !important;
        margin-bottom: 1rem !important;
    }
    
    .audio-options, .image-options, .word-options {
        gap: 0.5rem !important;
    }
    
    .word-option, .audio-option {
        padding: 0.75rem 1rem !important;
        font-size: 1rem !important;
        min-width: 100px !important;
    }
    
    .image-option {
        padding: 0.5rem !important;
    }
    
    .image-option img {
        width: 100px !important;
        height: 100px !important;
    }
    
    .big-play-btn, .big-audio-btn {
        width: 70px !important;
        height: 70px !important;
    }
    
    .big-play-btn i, .big-audio-btn i {
        font-size: 1.75rem !important;
    }
    
    .card-type-selector-container {
        padding: 0.5rem !important;
        gap: 0.5rem !important;
        margin-bottom: 0.75rem !important;
    }
    
    .card-type-btn {
        padding: 0.5rem 0.75rem !important;
        min-width: 50px !important;
        height: 45px !important;
        font-size: 0.875rem !important;
    }
    
    .card-type-btn i {
        font-size: 0.875rem !important;
    }
    
    .game-progress {
        margin-bottom: 0.75rem !important;
    }
    
    .progress-text {
        font-size: 0.9rem !important;
    }
    
    .game-controls .btn {
        font-size: 1rem !important;
        padding: 0.75rem 1.5rem !important;
        min-width: 120px !important;
    }
    
    .audio-option-item {
        min-width: 150px !important;
        padding: 0.5rem 0.75rem !important;
        flex-direction: column;
        gap: 0.5rem !important;
    }
    
    .audio-play-preview-btn {
        width: 40px !important;
        height: 40px !important;
        font-size: 1rem !important;
    }
    
    .audio-select-btn {
        padding: 0.5rem 1rem !important;
        font-size: 1rem !important;
        width: 100%;
    }
    
    .mode-selector {
        padding: 0.5rem !important;
    }
    
    .mode-selector select {
        padding: 0.4rem 0.75rem !important;
        font-size: 0.9rem !important;
    }
    
    .mode-selector label {
        font-size: 0.9rem !important;
    }
}
</style>
<div class="container">
    <div class="game-header">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <a href="{{ route('lessons.show', $lesson->slug) }}" class="back-link">&larr; Back to Lesson</a>
            @include('partials.admin-edit-lesson', [
                'lesson' => $lesson,
                'activityEditUrl' => route('admin.lessons.flashcard-games.edit', [$lesson, $flashcardGame]),
                'activityEditLabel' => 'Edit Game',
            ])
        </div>
        <h1 class="page-title">{{ $flashcardGame->title }}</h1>
        <p class="game-description">Practice vocabulary with interactive flashcards</p>
    </div>

    <div id="flashcard-app" class="flashcard-container">
        <!-- Game Screen -->
        <div class="game-screen" id="game-screen">
            @if(isset($gameData['available_modes']) && count($gameData['available_modes']) > 1)
                <div class="mode-selector-container">
                    <div class="mode-selector" id="mode-selector">
                        <label for="mode-select">Practice with:</label>
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
            <div class="game-progress">
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                <div class="progress-text">
                    <span id="current-card">1</span> of <span id="total-cards">{{ $gameData['cards_per_game'] }}</span>
                </div>
            </div>

            <div class="flashcard-container">
                <div class="card-type-selector-container" id="card-type-selector-container">
                    <!-- Icon buttons will be populated by JavaScript -->
                </div>
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
                <p id="completion-message">Great job practicing your vocabulary!</p>
                <div class="completion-stats">
                    <div class="stat">
                        <span class="stat-value" id="completion-score">0 / 0</span>
                        <span class="stat-label">Correct</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value" id="completion-accuracy">0%</span>
                        <span class="stat-label">Accuracy</span>
                    </div>
                </div>
                <div class="completion-actions">
                    <button id="play-again-btn" class="btn btn-primary">Play Again</button>
                    @include('partials.guided-flow-nav', ['guidedFlow' => $guidedFlow ?? null, 'lesson' => $lesson])
                </div>
            </div>
        </div>
    </div>
</div>

<audio id="game-audio" preload="auto"></audio>

<script>
const flashcardActivityEndpoint = '{{ route('activity-events.store') }}';
const flashcardActivityPayload = {
    lesson_id: {{ $lesson->id }},
    activity_type: 'flashcard',
    activity_id: {{ $flashcardGame->id }},
};

function logFlashcardEvent(status, meta = {}) {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    fetch(flashcardActivityEndpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            ...(token ? { 'X-CSRF-TOKEN': token } : {}),
        },
        body: JSON.stringify({
            ...flashcardActivityPayload,
            status,
            meta,
        }),
    }).catch(() => {});
}

const gameData = @json($gameData);
const lessonSlug = '{{ $lesson->slug }}';

// Game state
let currentGameType = null;
let currentCardIndex = 0;
let currentMode = '{{ $mode }}';
let gameCards = [];
let correctAnswers = 0;
let userAnswers = [];
let gameStartTime = null;

// DOM elements
const gameScreen = document.getElementById('game-screen');
const gameComplete = document.getElementById('game-complete');
const flashcard = document.getElementById('flashcard');
const progressFill = document.getElementById('progress-fill');
const currentCardSpan = document.getElementById('current-card');
const totalCardsSpan = document.getElementById('total-cards');
const nextBtn = document.getElementById('next-btn');
const restartBtn = document.getElementById('restart-btn');
const playAgainBtn = document.getElementById('play-again-btn');
const completionMessage = document.getElementById('completion-message');
const completionScore = document.getElementById('completion-score');
const completionAccuracy = document.getElementById('completion-accuracy');

// Mode change functionality
function changeMode(mode) {
    // If we're already in a game, restart it with the new mode
    if (currentGameType) {
        currentMode = mode;
        // Reload the page with new mode but keep the current game type
        // We'll need to trigger the game start again after reload
        const url = new URL(window.location);
        url.searchParams.set('mode', mode);
        url.searchParams.set('gameType', currentGameType);
        window.location.href = url.toString();
    } else {
        // If not in a game yet, just reload the page with new mode
        const url = new URL(window.location);
        url.searchParams.set('mode', mode);
        window.location.href = url.toString();
    }
}

// Per-card game type change functionality
function changeCardType(gameType) {
    if (!gameData.game_types.includes(gameType)) {
        console.error('Invalid game type:', gameType);
        return;
    }
    
    currentGameType = gameType;
    
    // Re-render the current card with the new game type
    // This will also update the button active states via updateCardTypeSelector
    if (currentCardIndex < gameCards.length) {
        const card = gameCards[currentCardIndex];
        renderCard(card);
    }
}

function getAvailableGameTypesForCard(card) {
    const available = [];
    
    // Check what resources are available for this card
    const hasImage = card.image_path && card.image_path.trim() !== '';
    const hasAudio = card.audio_path && card.audio_path.trim() !== '';
    
    // Check if there are other cards with images (for audio_to_image mode)
    const hasOtherCardsWithImages = gameData.cards && gameData.cards.some(c => 
        c.id !== card.id && c.image_path && c.image_path.trim() !== ''
    );
    
    // Filter available game types based on card resources
    gameData.game_types.forEach(type => {
        if (type === 'image_to_word' && hasImage) {
            available.push(type);
        } else if (type === 'image_to_audio' && hasImage) {
            available.push(type);
        } else if (type === 'audio_to_image' && hasAudio && hasOtherCardsWithImages) {
            available.push(type);
        } else if (type === 'audio_to_word' && hasAudio) {
            available.push(type);
        }
    });
    
    // Fallback to all game types if none match (shouldn't happen, but safety check)
    return available.length > 0 ? available : gameData.game_types;
}

function updateCardTypeSelector(card) {
    const container = document.getElementById('card-type-selector-container');
    
    if (!container) return;
    
    const availableTypes = getAvailableGameTypesForCard(card);
    
    // Hide selector if only one type available
    if (availableTypes.length <= 1) {
        container.style.display = 'none';
        return;
    }
    
    container.style.display = 'flex';
    
    // Clear existing buttons
    container.innerHTML = '';
    
    // Icon mappings for each game type
    const typeIcons = {
        'image_to_word': '<i class="fas fa-image"></i><i class="fas fa-arrow-right"></i><i class="fas fa-font"></i>',
        'image_to_audio': '<i class="fas fa-image"></i><i class="fas fa-arrow-right"></i><i class="fas fa-volume-up"></i>',
        'audio_to_image': '<i class="fas fa-volume-up"></i><i class="fas fa-arrow-right"></i><i class="fas fa-image"></i>',
        'audio_to_word': '<i class="fas fa-volume-up"></i><i class="fas fa-arrow-right"></i><i class="fas fa-font"></i>'
    };
    
    const typeTitles = {
        'image_to_word': 'Image to Word',
        'image_to_audio': 'Image to Audio',
        'audio_to_image': 'Audio to Image',
        'audio_to_word': 'Audio to Word'
    };
    
    // Create icon buttons for each available type
    availableTypes.forEach(type => {
        const button = document.createElement('button');
        button.className = 'card-type-btn';
        button.type = 'button';
        button.innerHTML = typeIcons[type] || type;
        button.title = typeTitles[type] || type;
        button.dataset.gameType = type;
        
        // Mark as active if it's the current type
        if (type === currentGameType) {
            button.classList.add('active');
        }
        
        // Add click handler
        button.addEventListener('click', function() {
            changeCardType(type);
        });
        
        container.appendChild(button);
    });
}

// Initialize game
document.addEventListener('DOMContentLoaded', function() {
    // Set up control buttons
    nextBtn.addEventListener('click', nextCard);
    restartBtn.addEventListener('click', restartGame);
    playAgainBtn.addEventListener('click', restartGame);
    
    // Auto-select game type: prefer image_to_word, fallback to audio_to_word
    let defaultGameType = null;
    if (gameData.game_types.includes('image_to_word')) {
        defaultGameType = 'image_to_word';
    } else if (gameData.game_types.includes('audio_to_word')) {
        defaultGameType = 'audio_to_word';
    } else if (gameData.game_types.length > 0) {
        // Fallback to first available game type
        defaultGameType = gameData.game_types[0];
    }
    
    // Check if there's a gameType in the URL (from mode change during game)
    const urlParams = new URLSearchParams(window.location.search);
    const gameTypeFromUrl = urlParams.get('gameType');
    if (gameTypeFromUrl && gameData.game_types.includes(gameTypeFromUrl)) {
        currentGameType = gameTypeFromUrl;
    } else if (defaultGameType) {
        currentGameType = defaultGameType;
    } else {
        // No game types available - show error or redirect
        console.error('No game types available');
        return;
    }
    
    // Hide/show mode selector appropriately
    const modeSelector = document.querySelector('.mode-selector');
    if (modeSelector && currentGameType === 'audio_to_word') {
        modeSelector.style.display = 'block';
    } else if (modeSelector) {
        modeSelector.style.display = 'none';
    }
    
    // Auto-start the game
    startGame();
    
    // Clean up the URL if needed
    if (urlParams.has('gameType')) {
        urlParams.delete('gameType');
        window.history.replaceState({}, '', window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : ''));
    }
});

function startGame() {
    // Shuffle cards for this game
    gameCards = [...gameData.cards].sort(() => Math.random() - 0.5).slice(0, gameData.cards_per_game);
    currentCardIndex = 0;
    correctAnswers = 0;
    userAnswers = [];
    gameStartTime = Date.now();
    logFlashcardEvent('started', {
        mode: currentMode,
        game_type: currentGameType,
        cards_planned: gameCards.length,
    });

    // Show game screen (already visible, just ensure completion screen is hidden)
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
    // Update the card type selector for this card
    updateCardTypeSelector(card);
    
    // Ensure current game type is available for this card
    const availableTypes = getAvailableGameTypesForCard(card);
    if (!availableTypes.includes(currentGameType)) {
        // Fallback to first available type
        currentGameType = availableTypes[0] || gameData.game_types[0];
    }
    
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
            return `
                <div class="card-content">
                    <div class="card-image">
                        <img src="${card.image_path}" alt="${card.english_word}" />
                    </div>
                    <div class="card-prompt">Choose the correct word:</div>
                    <div class="word-options" id="word-options">
                        <!-- Options will be generated by JavaScript -->
                    </div>
                </div>
            `;
        case 'image_to_audio':
            return `
                <div class="card-content">
                    <div class="card-image">
                        <img src="${card.image_path}" alt="${card.english_word}" />
                    </div>
                    <div class="card-prompt">Play each audio and choose the correct one:</div>
                    <div class="audio-options" id="audio-options">
                        <!-- Options will be generated by JavaScript -->
                    </div>
                </div>
            `;
        case 'audio_to_image':
            return `
                <div class="card-content">
                    <div class="card-audio">
                        <button class="big-play-btn" data-audio="${card.audio_path}" title="Play Word">
                            <i class="fas fa-play"></i>
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
                        <button class="big-audio-btn" data-audio="${card.audio_path}" title="Play Word">
                            <i class="fas fa-volume-up"></i>
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
                playAudio(audioPath, this);
            }
        });
    });

    // Big play button (for audio_to_image mode)
    document.querySelectorAll('.big-play-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const audioPath = this.dataset.audio;
            if (audioPath) {
                playAudio(audioPath, this);
            }
        });
    });

    // Big audio button (for audio_to_word mode)
    document.querySelectorAll('.big-audio-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const audioPath = this.dataset.audio;
            if (audioPath) {
                playAudio(audioPath, this);
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
            
            allOptions.forEach((option) => {
                const audioItem = document.createElement('div');
                audioItem.className = 'audio-option-item';
                audioItem.dataset.optionId = option.id;
                audioItem.dataset.correct = option.id === correctOptionId;
                
                // Play button
                const playBtn = document.createElement('button');
                playBtn.className = 'audio-play-preview-btn';
                playBtn.innerHTML = '<i class="fas fa-volume-up"></i>';
                playBtn.title = 'Play Audio';
                playBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    playAudio(option.audio_path, this);
                });
                
                // Select button
                const selectBtn = document.createElement('button');
                selectBtn.className = 'audio-select-btn';
                selectBtn.innerHTML = 'Select';
                selectBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    selectOption(audioItem);
                });
                
                audioItem.appendChild(playBtn);
                audioItem.appendChild(selectBtn);
                optionsContainer.appendChild(audioItem);
            });
            break;
            
        case 'image_to_word':
            optionsContainer = document.getElementById('word-options');
            optionsContainer.innerHTML = '';
            
            allOptions.forEach((option) => {
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
            
        case 'audio_to_image':
            optionsContainer = document.getElementById('image-options');
            optionsContainer.innerHTML = '';
            
            allOptions.forEach((option) => {
                const optionBtn = document.createElement('button');
                optionBtn.className = 'image-option';
                optionBtn.dataset.optionId = option.id;
                optionBtn.dataset.correct = option.id === correctOptionId;
                
                if (option.image_path) {
                    optionBtn.innerHTML = `<img src="${option.image_path}" alt="${option.english_word}" />`;
                } else {
                    optionBtn.innerHTML = '<span>No Image</span>';
                    optionBtn.disabled = true;
                    optionBtn.classList.add('opacity-50', 'cursor-not-allowed');
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
            
            allOptions.forEach((option) => {
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
    document.querySelectorAll('.audio-option, .audio-option-item, .image-option, .word-option').forEach(opt => {
        opt.classList.remove('border-blue-500', 'bg-blue-50', 'selected');
    });
    
    // Mark this option as selected with Tailwind classes
    optionElement.classList.add('border-blue-500', 'bg-blue-50', 'selected');
    
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
        selectedOption.classList.add('border-green-500', 'bg-green-50');
        selectedOption.classList.remove('border-blue-500', 'bg-blue-50', 'border-gray-200');
        showFeedback('correct');
    } else {
        selectedOption.classList.add('border-red-500', 'bg-red-50');
        selectedOption.classList.remove('border-blue-500', 'bg-blue-50', 'border-gray-200');
        showFeedback('incorrect');
        
        // Highlight the correct answer
        document.querySelectorAll('.audio-option-item, .image-option, .word-option').forEach(opt => {
            if (opt.dataset.correct === 'true' && opt !== selectedOption) {
                opt.classList.add('border-green-500', 'bg-green-50');
                opt.classList.remove('border-gray-200');
            }
        });
    }

    userAnswers.push({
        cardId: gameCards[currentCardIndex]?.id,
        correct: isCorrect
    });
    
    // Show next button
    nextBtn.classList.remove('hidden');
}

function showFeedback(type) {
    const feedback = document.createElement('div');
    if (type === 'correct') {
        feedback.className = 'absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 px-6 py-4 rounded-xl font-bold text-xl z-10 bg-green-500 text-white shadow-lg';
    } else {
        feedback.className = 'absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 px-6 py-4 rounded-xl font-bold text-xl z-10 bg-red-500 text-white shadow-lg';
    }
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
    const total = gameCards.length;
    const accuracy = total > 0 ? Math.round((correctAnswers / total) * 100) : 0;
    const durationSeconds = gameStartTime ? Math.floor((Date.now() - gameStartTime) / 1000) : null;

    completionScore.textContent = `${correctAnswers} / ${total}`;
    completionAccuracy.textContent = `${accuracy}%`;

    if (accuracy === 100) {
        completionMessage.textContent = 'Perfect! You nailed every card.';
    } else if (accuracy >= 80) {
        completionMessage.textContent = 'Great work! A little more practice will make it perfect.';
    } else if (accuracy >= 50) {
        completionMessage.textContent = 'Nice effort! Keep practicing to master these words.';
    } else {
        completionMessage.textContent = 'Good start! Replay the game to boost your score.';
    }
    
    // Show completion screen
    gameScreen.classList.add('hidden');
    gameComplete.classList.remove('hidden');
    gameComplete.classList.add('celebrate');

    logFlashcardEvent('completed', {
        mode: currentMode,
        game_type: currentGameType,
        cards_played: total,
        correct_answers: correctAnswers,
        accuracy,
        duration_seconds: durationSeconds,
    });
}

function restartGame() {
    // Reset game state
    gameScreen.classList.remove('hidden');
    gameComplete.classList.add('hidden');
    gameComplete.classList.remove('celebrate');
    gameStartTime = null;
    currentCardIndex = 0;
    correctAnswers = 0;
    userAnswers = [];
    
    // Restart the game
    startGame();
}

function updateProgress() {
    const progress = (currentCardIndex / gameCards.length) * 100;
    progressFill.style.width = progress + '%';
    currentCardSpan.textContent = currentCardIndex + 1;
}

function playAudio(audioPath, button) {
    if (!audioPath) {
        return;
    }

    if (button) {
        TalmaAudio.toggle(audioPath, button);
    } else {
        TalmaAudio.play(audioPath);
    }
}

function autoResizeText(element) {
    // Wait for element to be rendered
    requestAnimationFrame(() => {
        // Reset to original size
        element.style.fontSize = '';
        element.style.whiteSpace = 'nowrap';
        element.style.wordBreak = '';
        element.style.lineHeight = '';
        
        // Check if text overflows
        if (element.scrollWidth > element.clientWidth) {
            // Calculate the scale factor needed
            const scale = element.clientWidth / element.scrollWidth;
            const currentFontSize = parseFloat(window.getComputedStyle(element).fontSize);
            const newFontSize = Math.max(currentFontSize * scale * 0.95, 0.6); // Minimum 0.6rem
            
            // Apply the new font size
            element.style.fontSize = newFontSize + 'rem';
            
            // Check again after font size change
            requestAnimationFrame(() => {
                // If still overflowing, allow wrapping for very long text
                if (element.scrollWidth > element.clientWidth) {
                    element.style.whiteSpace = 'normal';
                    element.style.wordBreak = 'break-word';
                    element.style.lineHeight = '1.2';
                }
            });
        }
    });
}
</script>
@endsection

@push('styles')
<style>
.game-header {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 2rem;
    align-items: flex-start;
}

.game-header .back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: white;
    background: var(--color-primary);
    text-decoration: none;
    font-weight: 600;
    padding: 0.625rem 1.25rem;
    border-radius: 8px;
    transition: all 0.2s ease;
    margin-bottom: 0;
    border: 2px solid var(--color-primary);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.game-header .back-link:hover {
    background: var(--color-primary-dark);
    border-color: var(--color-primary-dark);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    text-decoration: none;
}

.game-header .page-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
    line-height: 1.2;
}

.game-header .game-description {
    color: var(--color-text-muted);
    font-size: 1rem;
    margin: 0;
    line-height: 1.5;
}

.flashcard-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 1rem;
}

.mode-selector-container {
    display: flex;
    justify-content: center;
    margin-bottom: 1rem;
}

.mode-selector {
    padding: 0.75rem;
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

.card-type-selector-container {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    justify-content: center;
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 8px;
    flex-wrap: wrap;
}

.card-type-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    background: white;
    color: #495057;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 60px;
    height: 50px;
}

.card-type-btn:hover {
    border-color: var(--color-primary);
    background: #e3f2fd;
    color: var(--color-primary);
    transform: translateY(-2px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.card-type-btn.active {
    border-color: var(--color-primary);
    background: var(--color-primary);
    color: white;
    box-shadow: 0 2px 6px rgba(0, 123, 255, 0.3);
}

.card-type-btn.active:hover {
    background: var(--color-primary-dark);
    border-color: var(--color-primary-dark);
}

.card-type-btn i {
    font-size: 1rem;
}


.game-progress {
    margin-bottom: 1rem;
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
    font-size: 1.1rem;
    font-weight: 600;
}

.flashcard {
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 1rem;
    min-height: 350px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.card-content {
    text-align: center;
    width: 100%;
}

.card-image {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 1rem;
}

.card-image img {
    max-width: 280px;
    max-height: 280px;
    border-radius: 12px;
    display: block;
    margin: 0 auto;
    width: 100%;
    height: auto;
    object-fit: contain;
}

.card-prompt {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    color: var(--color-text);
}

.card-answer {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-primary);
    padding: 1.25rem;
    background: #f8f9fa;
    border-radius: 12px;
    margin-top: 1rem;
}

.audio-options, .image-options, .word-options {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.audio-option, .image-option, .word-option {
    padding: 1.25rem 2rem;
    border: 2px solid var(--color-border);
    border-radius: 12px;
    background: var(--color-white);
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 140px;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 1.5rem;
    font-weight: 600;
    line-height: 1.4;
    text-align: center;
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

/* Audio option item for image_to_audio mode */
.audio-option-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border: 2px solid var(--color-border);
    border-radius: 8px;
    background: var(--color-white);
    transition: all 0.2s ease;
    min-width: 200px;
}

.audio-option-item.selected {
    border-color: var(--color-primary);
    background: #e3f2fd;
}

.audio-option-item.correct {
    border-color: var(--color-success);
    background: #d4edda;
}

.audio-option-item.incorrect {
    border-color: var(--color-danger);
    background: #f8d7da;
}

.audio-play-preview-btn {
    background: var(--color-primary);
    color: white;
    border: none;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
    font-size: 1.25rem;
}

.audio-play-preview-btn:hover {
    background: var(--color-primary-dark);
    transform: scale(1.1);
}

.audio-select-btn {
    flex: 1;
    padding: 0.75rem 1.5rem;
    border: 2px solid var(--color-primary);
    border-radius: 8px;
    background: white;
    color: var(--color-primary);
    font-weight: 600;
    font-size: 1.25rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.audio-select-btn:hover {
    background: var(--color-primary);
    color: white;
}

.image-option img {
    width: 140px;
    height: 140px;
    object-fit: cover;
    border-radius: 8px;
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

.card-audio {
    text-align: center;
    margin: 1rem 0;
}

.big-play-btn {
    background: var(--color-primary);
    color: white;
    border: none;
    border-radius: 50%;
    width: 100px;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    margin: 0 auto;
}

.big-play-btn:hover {
    background: var(--color-primary-dark);
    transform: scale(1.1);
    box-shadow: 0 6px 16px rgba(0,0,0,0.3);
}

.big-play-btn i {
    font-size: 2.5rem;
}

.big-audio-btn {
    background: var(--color-primary);
    color: white;
    border: none;
    border-radius: 50%;
    width: 100px;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    margin: 0 auto;
}

.big-audio-btn:hover {
    background: var(--color-primary-dark);
    transform: scale(1.1);
    box-shadow: 0 6px 16px rgba(0,0,0,0.3);
}

.big-audio-btn i {
    font-size: 2.5rem;
}

.game-controls {
    text-align: center;
}

.game-controls .btn {
    font-size: 1.25rem;
    font-weight: 600;
    padding: 1rem 2rem;
    min-width: 160px;
}

.completion-actions .btn {
    font-size: 1.25rem;
    font-weight: 600;
    padding: 1rem 2rem;
    min-width: 160px;
}

.feedback {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    padding: 0.75rem 1.5rem;
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

.game-complete h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.game-complete #completion-message {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.game-complete.celebrate h2 {
    animation: bounce 1s ease-in-out 2;
}

.game-complete.celebrate .completion-stats {
    animation: fadeInUp 0.6s ease;
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
    font-size: 2.5rem;
    font-weight: bold;
    color: var(--color-primary);
}

.stat-label {
    color: var(--color-text-light);
    font-size: 1.1rem;
    font-weight: 600;
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

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
@endpush
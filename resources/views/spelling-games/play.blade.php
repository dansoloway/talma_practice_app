@extends('layouts.app')

@section('title', 'Spelling Practice - ' . $lesson->title)

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
    
    .game-title, .game-subtitle {
        font-size: 1.2rem;
        margin: 0.5rem 0;
    }
    
    .game-header .back-link {
        font-size: 0.9rem;
    }
}
</style>

<div class="spelling-game-container">
    @include('partials.student-game-locale-bar')
    <div class="game-header">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            @include('partials.student-lesson-back-link', ['lesson' => $lesson, 'org' => $org ?? null, 'linkClass' => 'back-link'])
            @include('partials.admin-edit-lesson', [
                'lesson' => $lesson,
                'activityEditUrl' => route('admin.lessons.spelling-games.edit', [$lesson, $spelling_game]),
                'activityEditLabel' => 'Edit Game',
            ])
        </div>
        <h1 class="game-title">{{ __('student-portal.games.spelling_practice') }}</h1>
        <p class="game-subtitle student-learning-ltr student-learning-ltr--text-start" dir="ltr" lang="en">{{ $lesson->title }}</p>
    </div>

    @if($vocabulary->count() > 0)
        <!-- Game Screen -->
        <div class="game-screen" id="game-screen">
            <div class="game-progress">
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                <div class="progress-text">
                    <span id="progress-display">{{ __('student-portal.games.word_of', ['current' => 1, 'total' => $vocabulary->count()]) }}</span>
                </div>
            </div>

            <div class="word-container" id="word-container">
                <!-- Word content will be loaded here by JavaScript -->
            </div>

            <div class="game-controls">
                <button id="next-btn" class="btn btn-primary hidden">{{ __('student-portal.games.next_word') }}</button>
                <button id="restart-btn" class="btn btn-secondary">{{ __('student-portal.games.restart') }}</button>
            </div>
        </div>

        <!-- Game Complete Screen -->
        <div class="game-complete hidden" id="game-complete">
            <div class="completion-content">
                <h2>🎉 {{ __('student-portal.games.game_complete') }}</h2>
                <p id="completion-message">{{ __('student-portal.games.great_job_practicing') }}</p>
                <div class="completion-stats">
                    <div class="stat">
                        <span class="stat-value" id="completion-score">0 / 0</span>
                        <span class="stat-label">{{ __('student-portal.games.correct_label') }}</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value" id="completion-accuracy">0%</span>
                        <span class="stat-label">{{ __('student-portal.games.accuracy') }}</span>
                    </div>
                </div>
                <div class="completion-actions">
                    <button id="play-again-btn" class="btn btn-primary">{{ __('student-portal.games.play_again') }}</button>
                    @include('partials.guided-flow-nav', ['guidedFlow' => $guidedFlow ?? null, 'lesson' => $lesson])
                </div>
            </div>
        </div>
    @else
        <div class="empty-state">
            <h3>{{ __('student-portal.games.no_words_available') }}</h3>
            <p>{{ __('student-portal.games.no_words_spelling') }}</p>
            @include('partials.student-lesson-back-link', ['lesson' => $lesson, 'org' => $org ?? null, 'linkClass' => 'btn btn-primary'])
        </div>
    @endif
</div>

<audio id="game-audio" preload="auto"></audio>

@include('partials.student-game-i18n')

<script>
const vocabulary = @json($vocabulary);
const difficulty = '{{ $spelling_game->difficulty ?? "medium" }}';
const lessonId = {{ $lesson->id }};
let currentWordIndex = 0;
let correctAnswers = 0;
let userAnswers = [];
let gameStartTime = null;
let currentWordAnswered = false;

// Activity event tracking
const activityEventEndpoint = '{{ route('activity-events.store') }}';
const activityEventPayload = {
    lesson_id: lessonId,
    activity_type: 'spelling',
    activity_id: {{ $spelling_game->id }},
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

// DOM elements
const gameScreen = document.getElementById('game-screen');
const gameComplete = document.getElementById('game-complete');
const wordContainer = document.getElementById('word-container');
const progressFill = document.getElementById('progress-fill');
const progressDisplay = document.getElementById('progress-display');
const nextBtn = document.getElementById('next-btn');
const restartBtn = document.getElementById('restart-btn');
const playAgainBtn = document.getElementById('play-again-btn');
const completionScore = document.getElementById('completion-score');
const completionAccuracy = document.getElementById('completion-accuracy');
const completionMessage = document.getElementById('completion-message');

// Initialize game
document.addEventListener('DOMContentLoaded', function() {
    if (vocabulary.length > 0) {
        gameStartTime = Date.now();
        logActivityEvent('started', {
            total_words: vocabulary.length,
            difficulty: difficulty,
        });
        loadWord(currentWordIndex);
    }

    // Set up control buttons
    nextBtn.addEventListener('click', nextWord);
    restartBtn.addEventListener('click', restartGame);
    if (playAgainBtn) {
        playAgainBtn.addEventListener('click', restartGame);
    }
});

function loadWord(index) {
    if (index >= vocabulary.length) {
        endGame();
        return;
    }

    const word = vocabulary[index];
    currentWordAnswered = false;
    updateProgress();

    // Generate hint based on difficulty
    let hint = '';
    const wordLength = word.english_word.length;
    
    if (difficulty === 'easy') {
        // Show first letter
        hint = word.english_word[0] + '_'.repeat(wordLength - 1);
    } else if (difficulty === 'medium') {
        // Show some letters (every other letter)
        hint = word.english_word.split('').map((char, i) => {
            return (i % 2 === 0) ? char : '_';
        }).join('');
    } else {
        // Hard: no hints
        hint = '_'.repeat(wordLength);
    }

    // Render word
    const wordHtml = `
        <div class="word-card student-learning-ltr" dir="ltr" lang="en">
            <div class="word-audio-section">
                <button type="button"
                        class="big-play-btn talma-audio-btn"
                        id="play-audio-btn"
                        data-audio-url="${word.word_audio_path || ''}"
                        data-talma-audio-manual
                        data-talma-audio-icon="volume-up"
                        aria-label="${gameT('click_to_listen')}">
                    <i class="fas fa-volume-up talma-audio-icon"></i>
                </button>
                <p class="audio-hint" id="play-audio-hint">${gameT('click_to_listen')}</p>
            </div>
            
            ${word.image_path ? `
            <div class="hint-control-section">
                <button class="show-image-btn" id="show-image-btn">
                    <i class="fas fa-image"></i> ${gameT('show_image')}
                </button>
            </div>
            <div class="word-image-section hidden" id="word-image-section">
                <img src="${word.image_path}" alt="${word.english_word}" class="word-image">
            </div>
            ` : ''}
            
            <div class="hint-control-section">
                <button class="show-hint-btn" id="show-hint-btn">
                    <i class="fas fa-lightbulb"></i> ${gameT('show_hint')}
                </button>
            </div>
            
            <div class="hint-section hidden" id="hint-section">
                <div class="hint-text" id="hint-text">${hint}</div>
            </div>
            
            <div class="spelling-input-section">
                <input type="text" 
                       id="spelling-input" 
                       class="spelling-input" 
                       autocomplete="off" 
                       autocorrect="off" 
                       autocapitalize="off" 
                       spellcheck="false"
                       placeholder="${gameT('type_the_word')}">
            </div>
            
            <div class="letter-feedback" id="letter-feedback"></div>
            
            <div class="answer-feedback hidden" id="answer-feedback">
                <div class="feedback-content" id="feedback-content"></div>
            </div>
        </div>
    `;

    wordContainer.innerHTML = wordHtml;

    // Set up event listeners
    setupWordEvents(word, hint);
}

function playWordAudio(audioPath, button) {
    if (!audioPath) {
        return;
    }

    if (typeof TalmaAudio !== 'undefined') {
        if (button) {
            TalmaAudio.toggle(audioPath, button);
        } else {
            TalmaAudio.play(audioPath);
        }
        return;
    }

    // Fallback if TalmaAudio is unavailable
    const audio = document.getElementById('game-audio');
    if (!audio) {
        return;
    }
    audio.src = audioPath;
    audio.playbackRate = 1;
    audio.play().catch((error) => {
        console.error('Error playing spelling audio:', error);
    });
}

function setupWordEvents(word, hint) {
    const input = document.getElementById('spelling-input');
    const letterFeedback = document.getElementById('letter-feedback');
    const answerFeedback = document.getElementById('answer-feedback');
    const feedbackContent = document.getElementById('feedback-content');
    const playBtn = document.getElementById('play-audio-btn');
    const playHint = document.getElementById('play-audio-hint');

    if (playBtn && word.word_audio_path) {
        playBtn.addEventListener('click', function (event) {
            event.preventDefault();
            playWordAudio(word.word_audio_path, this);
        });
        if (playHint) {
            playHint.style.cursor = 'pointer';
            playHint.addEventListener('click', function () {
                playWordAudio(word.word_audio_path, playBtn);
            });
        }
    } else if (playBtn && !word.word_audio_path) {
        playBtn.disabled = true;
        playBtn.classList.add('disabled');
        playBtn.title = gameT('no_audio');
    }

    // Show/Hide image button
    const showImageBtn = document.getElementById('show-image-btn');
    const imageSection = document.getElementById('word-image-section');
    if (showImageBtn && imageSection) {
        showImageBtn.addEventListener('click', function() {
            imageSection.classList.toggle('hidden');
            if (imageSection.classList.contains('hidden')) {
                showImageBtn.innerHTML = '<i class="fas fa-image"></i> ' + gameT('show_image');
            } else {
                showImageBtn.innerHTML = '<i class="fas fa-eye-slash"></i> ' + gameT('hide_image');
            }
        });
    }

    // Show/Hide hint button
    const showHintBtn = document.getElementById('show-hint-btn');
    const hintSection = document.getElementById('hint-section');
    if (showHintBtn && hintSection) {
        showHintBtn.addEventListener('click', function() {
            hintSection.classList.toggle('hidden');
            if (hintSection.classList.contains('hidden')) {
                showHintBtn.innerHTML = '<i class="fas fa-lightbulb"></i> ' + gameT('show_hint');
            } else {
                showHintBtn.innerHTML = '<i class="fas fa-eye-slash"></i> ' + gameT('hide_hint');
            }
        });
    }

    // Real-time letter feedback
    input.addEventListener('input', function() {
        if (currentWordAnswered) return;
        
        const userInput = this.value.toLowerCase();
        const correctWord = word.english_word.toLowerCase();
        const maxLength = Math.max(userInput.length, correctWord.length);
        
        // Update hint to show typed letters
        let displayHint = '';
        for (let i = 0; i < maxLength; i++) {
            if (i < userInput.length) {
                const userChar = userInput[i];
                const correctChar = correctWord[i];
                if (userChar === correctChar) {
                    displayHint += `<span class="letter-correct">${correctWord[i]}</span>`;
                } else if (i < correctWord.length) {
                    displayHint += `<span class="letter-incorrect">${userChar || '_'}</span>`;
                }
            } else if (i < correctWord.length) {
                displayHint += `<span class="letter-pending">_</span>`;
            }
        }
        
        letterFeedback.innerHTML = displayHint;
        
        // Check if word is complete and correct
        if (userInput.length === correctWord.length) {
            if (userInput === correctWord) {
                // Correct!
                currentWordAnswered = true;
                input.disabled = true;
                input.classList.add('correct');
                
                correctAnswers++;
                showAnswerFeedback('correct', word.english_word);
                nextBtn.classList.remove('hidden');
                
                userAnswers.push({
                    wordId: word.id,
                    userAnswer: userInput,
                    correct: true
                });
            } else {
                // Wrong - show correct spelling after a moment
                setTimeout(() => {
                    if (!currentWordAnswered) {
                        currentWordAnswered = true;
                        input.disabled = true;
                        input.classList.add('incorrect');
                        
                        showAnswerFeedback('incorrect', word.english_word, userInput);
                        nextBtn.classList.remove('hidden');
                        
                        userAnswers.push({
                            wordId: word.id,
                            userAnswer: userInput,
                            correct: false
                        });
                    }
                }, 1000);
            }
        }
    });

    // Focus input on load
    input.focus();
}

function showAnswerFeedback(type, correctWord, userAnswer = null) {
    const answerFeedback = document.getElementById('answer-feedback');
    const feedbackContent = document.getElementById('feedback-content');
    
    if (type === 'correct') {
        feedbackContent.innerHTML = `
            <div class="feedback-icon correct-icon">✓</div>
            <p class="feedback-text">${gameT('correct')}</p>
            <p class="correct-word">${correctWord}</p>
        `;
    } else {
        feedbackContent.innerHTML = `
            <div class="feedback-icon incorrect-icon">✗</div>
            <p class="feedback-text">${gameT('spelling_not_quite_answer')}</p>
            <p class="correct-word">${correctWord}</p>
            ${userAnswer ? `<p class="user-answer">${gameT('you_typed', { answer: userAnswer })}</p>` : ''}
        `;
    }
    
    answerFeedback.classList.remove('hidden');
}

function nextWord() {
    currentWordIndex++;
    loadWord(currentWordIndex);
    nextBtn.classList.add('hidden');
}

function endGame() {
    const total = vocabulary.length;
    const accuracy = total > 0 ? Math.round((correctAnswers / total) * 100) : 0;
    const durationSeconds = gameStartTime ? Math.floor((Date.now() - gameStartTime) / 1000) : null;

    completionScore.textContent = `${correctAnswers} / ${total}`;
    completionAccuracy.textContent = `${accuracy}%`;

    if (accuracy === 100) {
        completionMessage.textContent = gameT('perfect');
    } else if (accuracy >= 80) {
        completionMessage.textContent = gameT('good_job');
    } else if (accuracy >= 50) {
        completionMessage.textContent = gameT('keep_practicing');
    } else {
        completionMessage.textContent = gameT('great_job_practicing');
    }
    
    // Show completion screen
    gameScreen.classList.add('hidden');
    gameComplete.classList.remove('hidden');
    gameComplete.classList.add('celebrate');

    logActivityEvent('completed', {
        total_words: total,
        correct_answers: correctAnswers,
        accuracy,
        duration_seconds: durationSeconds,
    });
}

function restartGame() {
    // Reset game state
    currentWordIndex = 0;
    correctAnswers = 0;
    userAnswers = [];
    gameStartTime = Date.now();
    currentWordAnswered = false;

    // Reset display
    gameScreen.classList.remove('hidden');
    gameComplete.classList.add('hidden');
    gameComplete.classList.remove('celebrate');

    // Log new start event
    logActivityEvent('started', {
        total_words: vocabulary.length,
        difficulty: difficulty,
        is_restart: true,
    });

    // Load first word
    loadWord(currentWordIndex);
}

function updateProgress() {
    const progress = ((currentWordIndex + 1) / vocabulary.length) * 100;
    progressFill.style.width = progress + '%';
    if (progressDisplay) {
        progressDisplay.textContent = gameT('word_of', {
            current: currentWordIndex + 1,
            total: vocabulary.length,
        });
    }
}
</script>

@push('styles')
<style>
.spelling-game-container {
    max-width: 700px;
    margin: 0 auto;
    padding: 2rem;
}

.game-header {
    text-align: center;
    margin-bottom: 2rem;
}

.game-title {
    font-size: 2rem;
    color: var(--color-primary);
    margin: 1rem 0 0.5rem 0;
}

.game-subtitle {
    color: var(--color-text-muted);
    font-size: 1.1rem;
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

.word-container {
    margin-bottom: 2rem;
}

.word-card {
    background: white;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    position: relative;
}

.word-audio-section {
    margin-bottom: 1.5rem;
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
    margin: 0 auto 1rem auto;
    font-size: 2rem;
}

.big-play-btn:hover:not(:disabled) {
    background: var(--color-primary-dark);
    transform: scale(1.1);
    box-shadow: 0 6px 16px rgba(0,0,0,0.3);
}

.big-play-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
    opacity: 0.6;
}

.audio-hint {
    color: var(--color-text-muted);
    font-size: 0.9rem;
    margin: 0;
}

.word-image-section {
    margin: 1.5rem 0;
}

.word-image {
    max-width: 200px;
    max-height: 200px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.hint-control-section {
    margin: 1.5rem 0;
}

.show-hint-btn, .show-image-btn {
    background: transparent;
    color: var(--color-primary);
    border: 2px solid var(--color-primary);
    border-radius: 8px;
    padding: 0.5rem 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.show-hint-btn:hover, .show-image-btn:hover {
    background: var(--color-primary);
    color: white;
}

.hint-section {
    margin: 1.5rem 0;
}

.hint-text {
    font-size: 2rem;
    font-weight: bold;
    letter-spacing: 0.5rem;
    color: var(--color-text);
    font-family: 'Courier New', monospace;
}

.spelling-input-section {
    margin: 2rem 0;
}

.spelling-input {
    width: 100%;
    max-width: 400px;
    padding: 1rem 1.5rem;
    font-size: 1.5rem;
    text-align: center;
    border: 3px solid var(--color-border);
    border-radius: 12px;
    font-family: 'Courier New', monospace;
    letter-spacing: 0.2rem;
    transition: all 0.2s ease;
}

.spelling-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.spelling-input.correct {
    border-color: #28a745;
    background: #d4edda;
}

.spelling-input.incorrect {
    border-color: #dc3545;
    background: #f8d7da;
}

.spelling-input:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.letter-feedback {
    margin: 1rem 0;
    font-size: 1.8rem;
    font-weight: bold;
    letter-spacing: 0.3rem;
    font-family: 'Courier New', monospace;
    min-height: 2.5rem;
    line-height: 2.5rem;
}

.letter-correct {
    color: #28a745;
}

.letter-incorrect {
    color: #dc3545;
    text-decoration: underline;
}

.letter-pending {
    color: var(--color-text-muted);
}

.answer-feedback {
    margin-top: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid var(--color-info);
}

.feedback-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.correct-icon {
    color: #28a745;
}

.incorrect-icon {
    color: #dc3545;
}

.feedback-text {
    font-size: 1.1rem;
    margin: 0.5rem 0;
    color: var(--color-text);
}

.correct-word {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--color-primary);
    margin: 0.5rem 0;
    font-family: 'Courier New', monospace;
}

.user-answer {
    font-size: 1rem;
    color: var(--color-text-muted);
    margin: 0.5rem 0;
    font-family: 'Courier New', monospace;
}

.game-controls {
    text-align: center;
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.game-complete {
    text-align: center;
    padding: 3rem;
    background: white;
    border-radius: 12px;
    border: 2px solid var(--color-primary);
}

.game-complete.celebrate h2 {
    animation: bounce 1s ease-in-out 2;
}

.completion-stats {
    display: flex;
    justify-content: center;
    gap: 3rem;
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
    font-size: 0.9rem;
    margin-top: 0.5rem;
}

.completion-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
}

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: var(--shadow-md);
}

.empty-state h3 {
    color: var(--color-text);
    margin-bottom: 1rem;
}

.hidden {
    display: none !important;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

@media (max-width: 768px) {
    .spelling-game-container {
        padding: 1rem;
    }
    
    .hint-text {
        font-size: 1.5rem;
        letter-spacing: 0.3rem;
    }
    
    .spelling-input {
        font-size: 1.2rem;
    }
    
    .letter-feedback {
        font-size: 1.4rem;
    }
    
    .completion-stats {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .completion-actions {
        flex-direction: column;
    }
}
</style>
@endpush
@endsection


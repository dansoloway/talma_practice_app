@extends('layouts.app')

@section('title', 'True/False Game - ' . $lesson->title)

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

<div class="true-false-game-container">
    <div class="game-header">
        <a href="{{ route('lessons.show', $lesson->slug) }}" class="back-link">&larr; Back to Lesson</a>
        <h1 class="game-title">{{ $trueFalseGame->title }}</h1>
        <p class="game-subtitle">{{ $lesson->title }} • <span class="badge badge-{{ $trueFalseGame->game_version === 'easy' ? 'success' : ($trueFalseGame->game_version === 'medium' ? 'warning' : 'danger') }}">{{ ucfirst($trueFalseGame->game_version) }}</span></p>
    </div>

    @if($questions->count() > 0)
        <!-- Game Screen -->
        <div class="game-screen" id="game-screen">
            <div class="game-progress">
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                <div class="progress-text">
                    Question <span id="current-question">1</span> of <span id="total-questions">{{ $questions->count() }}</span>
                </div>
            </div>

            <div class="question-container" id="question-container">
                <!-- Question content will be loaded here by JavaScript -->
            </div>

            <div class="game-controls">
                <button id="next-btn" class="btn btn-primary hidden">Next Question</button>
                <button id="restart-btn" class="btn btn-secondary">Restart Game</button>
            </div>
        </div>

        <!-- Game Complete Screen -->
        <div class="game-complete hidden" id="game-complete">
            <div class="completion-content">
                <h2>🎉 Game Complete!</h2>
                <p id="completion-message">Great job practicing!</p>
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
                    <a href="{{ route('lessons.show', $lesson->slug) }}" class="btn btn-secondary">Back to Lesson</a>
                </div>
            </div>
        </div>
    @else
        <div class="empty-state">
            <h3>No Questions Available</h3>
            <p>This lesson doesn't have any True/False questions yet.</p>
            <a href="{{ route('lessons.show', $lesson->slug) }}" class="btn btn-primary">Back to Lesson</a>
        </div>
    @endif
</div>

<audio id="game-audio" preload="auto"></audio>

<script>
const questions = @json($questions);
const lessonId = {{ $lesson->id }};
let currentQuestionIndex = 0;
let correctAnswers = 0;
let userAnswers = [];
let gameStartTime = null;
let currentQuestionAnswered = false;

// Activity event tracking
const activityEventEndpoint = '{{ route('activity-events.store') }}';
const activityEventPayload = {
    lesson_id: lessonId,
    activity_type: 'true_false',
    activity_id: null,
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
const questionContainer = document.getElementById('question-container');
const progressFill = document.getElementById('progress-fill');
const currentQuestionSpan = document.getElementById('current-question');
const totalQuestionsSpan = document.getElementById('total-questions');
const nextBtn = document.getElementById('next-btn');
const restartBtn = document.getElementById('restart-btn');
const playAgainBtn = document.getElementById('play-again-btn');
const completionScore = document.getElementById('completion-score');
const completionAccuracy = document.getElementById('completion-accuracy');
const completionMessage = document.getElementById('completion-message');

// Initialize game
document.addEventListener('DOMContentLoaded', function() {
    if (questions.length > 0) {
        gameStartTime = Date.now();
        logActivityEvent('started', {
            total_questions: questions.length,
        });
        loadQuestion(currentQuestionIndex);
    }

    // Set up control buttons
    nextBtn.addEventListener('click', nextQuestion);
    restartBtn.addEventListener('click', restartGame);
    if (playAgainBtn) {
        playAgainBtn.addEventListener('click', restartGame);
    }
});

function loadQuestion(index) {
    if (index >= questions.length) {
        endGame();
        return;
    }

    const question = questions[index];
    currentQuestionAnswered = false;
    updateProgress();

    // Render question
    const questionHtml = `
        <div class="question-card">
            <div class="question-audio-section">
                <button class="big-play-btn" id="play-audio-btn" data-audio="${question.audio_path || ''}">
                    <i class="fas fa-volume-up"></i>
                </button>
                <p class="audio-hint">Click to listen</p>
            </div>
            
            <div class="show-text-section">
                <button class="show-text-btn" id="show-text-btn">
                    <i class="fas fa-eye"></i> Show Text
                </button>
            </div>
            
            <div class="question-text hidden" id="question-text">
                <p>${question.statement}</p>
            </div>
            
            <div class="answer-buttons" id="answer-buttons">
                <button class="answer-btn true-btn" data-answer="true">
                    <i class="fas fa-check-circle"></i> True
                </button>
                <button class="answer-btn false-btn" data-answer="false">
                    <i class="fas fa-times-circle"></i> False
                </button>
            </div>
            
            <div class="explanation hidden" id="explanation">
                <div class="explanation-content" id="explanation-content"></div>
            </div>
        </div>
    `;

    questionContainer.innerHTML = questionHtml;

    // Set up event listeners
    setupQuestionEvents(question);
}

function setupQuestionEvents(question) {
    // Audio playback
    const playBtn = document.getElementById('play-audio-btn');
    if (playBtn && question.audio_path) {
        playBtn.addEventListener('click', function() {
            playAudio(question.audio_path);
        });
    } else if (playBtn && !question.audio_path) {
        playBtn.disabled = true;
        playBtn.classList.add('disabled');
        playBtn.title = 'No audio available';
    }

    // Show text button
    const showTextBtn = document.getElementById('show-text-btn');
    const questionText = document.getElementById('question-text');
    if (showTextBtn) {
        showTextBtn.addEventListener('click', function() {
            questionText.classList.toggle('hidden');
            if (questionText.classList.contains('hidden')) {
                showTextBtn.innerHTML = '<i class="fas fa-eye"></i> Show Text';
            } else {
                showTextBtn.innerHTML = '<i class="fas fa-eye-slash"></i> Hide Text';
            }
        });
    }

    // Answer buttons
    const answerButtons = document.querySelectorAll('.answer-btn');
    answerButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            if (currentQuestionAnswered) return;
            
            const userAnswer = this.dataset.answer === 'true';
            const isCorrect = userAnswer === question.is_true;
            
            currentQuestionAnswered = true;
            
            // Disable answer buttons
            answerButtons.forEach(b => b.disabled = true);
            
            // Highlight selected answer
            this.classList.add('selected');
            
            // Show feedback
            if (isCorrect) {
                correctAnswers++;
                this.classList.add('correct');
                showFeedback('correct');
            } else {
                this.classList.add('incorrect');
                // Highlight correct answer
                const correctBtn = document.querySelector(`[data-answer="${question.is_true}"]`);
                if (correctBtn) {
                    correctBtn.classList.add('correct');
                }
                showFeedback('incorrect');
            }
            
            // Show explanation
            const explanation = document.getElementById('explanation');
            const explanationContent = document.getElementById('explanation-content');
            if (explanation && explanationContent) {
                explanationContent.innerHTML = `
                    <p><strong>${question.is_true ? 'True' : 'False'}</strong></p>
                    <p>${question.explanation}</p>
                `;
                explanation.classList.remove('hidden');
            }
            
            // Store answer
            userAnswers.push({
                questionId: question.id,
                userAnswer: userAnswer,
                correct: isCorrect
            });
            
            // Show next button
            nextBtn.classList.remove('hidden');
        });
    });
}

function showFeedback(type) {
    const feedback = document.createElement('div');
    feedback.className = `feedback feedback-${type}`;
    feedback.textContent = type === 'correct' ? '✓ Correct!' : '✗ Incorrect';
    questionContainer.appendChild(feedback);
    
    setTimeout(() => {
        feedback.remove();
    }, 2000);
}

function nextQuestion() {
    currentQuestionIndex++;
    loadQuestion(currentQuestionIndex);
    nextBtn.classList.add('hidden');
}

function endGame() {
    const total = questions.length;
    const accuracy = total > 0 ? Math.round((correctAnswers / total) * 100) : 0;
    const durationSeconds = gameStartTime ? Math.floor((Date.now() - gameStartTime) / 1000) : null;

    completionScore.textContent = `${correctAnswers} / ${total}`;
    completionAccuracy.textContent = `${accuracy}%`;

    if (accuracy === 100) {
        completionMessage.textContent = 'Perfect! You got every question right!';
    } else if (accuracy >= 80) {
        completionMessage.textContent = 'Great work! You\'re doing really well!';
    } else if (accuracy >= 50) {
        completionMessage.textContent = 'Nice effort! Keep practicing!';
    } else {
        completionMessage.textContent = 'Good start! Try again to improve your score!';
    }
    
    // Show completion screen
    gameScreen.classList.add('hidden');
    gameComplete.classList.remove('hidden');
    gameComplete.classList.add('celebrate');

    logActivityEvent('completed', {
        total_questions: total,
        correct_answers: correctAnswers,
        accuracy,
        duration_seconds: durationSeconds,
    });
}

function restartGame() {
    // Reset game state
    currentQuestionIndex = 0;
    correctAnswers = 0;
    userAnswers = [];
    gameStartTime = Date.now();
    currentQuestionAnswered = false;

    // Reset display
    gameScreen.classList.remove('hidden');
    gameComplete.classList.add('hidden');
    gameComplete.classList.remove('celebrate');

    // Log new start event
    logActivityEvent('started', {
        total_questions: questions.length,
        is_restart: true,
    });

    // Load first question
    loadQuestion(currentQuestionIndex);
}

function updateProgress() {
    const progress = ((currentQuestionIndex + 1) / questions.length) * 100;
    progressFill.style.width = progress + '%';
    currentQuestionSpan.textContent = currentQuestionIndex + 1;
}

function playAudio(audioPath) {
    if (!audioPath) return;
    
    const audio = document.getElementById('game-audio');
    const playBtn = document.getElementById('play-audio-btn');
    
    if (playBtn) {
        playBtn.disabled = true;
        playBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    }
    
    audio.src = audioPath;
    audio.play().then(() => {
        if (playBtn) {
            playBtn.disabled = false;
            playBtn.innerHTML = '<i class="fas fa-volume-up"></i>';
        }
    }).catch(error => {
        console.error('Error playing audio:', error);
        if (playBtn) {
            playBtn.disabled = false;
            playBtn.innerHTML = '<i class="fas fa-volume-up"></i>';
        }
    });
    
    audio.onended = function() {
        if (playBtn) {
            playBtn.disabled = false;
            playBtn.innerHTML = '<i class="fas fa-volume-up"></i>';
        }
    };
}
</script>

@push('styles')
<style>
.true-false-game-container {
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

.back-link {
    display: inline-block;
    color: var(--color-text-muted);
    text-decoration: none;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.back-link:hover {
    color: var(--color-primary);
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

.question-container {
    margin-bottom: 2rem;
}

.question-card {
    background: white;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    position: relative;
}

.question-audio-section {
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

.show-text-section {
    margin-bottom: 1.5rem;
}

.show-text-btn {
    background: transparent;
    color: var(--color-primary);
    border: 2px solid var(--color-primary);
    border-radius: 8px;
    padding: 0.5rem 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.show-text-btn:hover {
    background: var(--color-primary);
    color: white;
}

.question-text {
    margin: 1.5rem 0;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid var(--color-primary);
}

.question-text p {
    font-size: 1.3rem;
    font-weight: 500;
    color: var(--color-text);
    margin: 0;
    line-height: 1.6;
}

.answer-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin: 2rem 0;
}

.answer-btn {
    flex: 1;
    max-width: 200px;
    padding: 1.5rem 2rem;
    border: 3px solid var(--color-border);
    border-radius: 12px;
    background: white;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1.2rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.answer-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.answer-btn.true-btn {
    color: #28a745;
}

.answer-btn.true-btn:hover:not(:disabled) {
    border-color: #28a745;
    background: #d4edda;
}

.answer-btn.false-btn {
    color: #dc3545;
}

.answer-btn.false-btn:hover:not(:disabled) {
    border-color: #dc3545;
    background: #f8d7da;
}

.answer-btn.selected {
    transform: scale(1.05);
}

.answer-btn.correct {
    border-color: #28a745;
    background: #d4edda;
    color: #155724;
}

.answer-btn.incorrect {
    border-color: #dc3545;
    background: #f8d7da;
    color: #721c24;
}

.answer-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.explanation {
    margin-top: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid var(--color-info);
    text-align: left;
}

.explanation-content p {
    margin: 0.5rem 0;
    color: var(--color-text);
    line-height: 1.6;
}

.explanation-content strong {
    color: var(--color-primary);
    font-size: 1.1rem;
}

.feedback {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    padding: 1rem 2rem;
    border-radius: 8px;
    font-weight: bold;
    font-size: 1.5rem;
    z-index: 10;
    animation: fadeInOut 2s ease;
}

.feedback-correct {
    background: #28a745;
    color: white;
}

.feedback-incorrect {
    background: #dc3545;
    color: white;
}

@keyframes fadeInOut {
    0%, 100% { opacity: 0; transform: translate(-50%, -50%) scale(0.8); }
    50% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
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
    .true-false-game-container {
        padding: 1rem;
    }
    
    .answer-buttons {
        flex-direction: column;
    }
    
    .answer-btn {
        max-width: 100%;
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


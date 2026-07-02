@extends('layouts.app')

@section('title', 'Sentence Builder - ' . $lesson->title)

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

.sentence-builder-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 1rem;
}

.game-header {
    text-align: center;
    margin-bottom: 2rem;
}

.back-link {
    display: inline-block;
    margin-bottom: 1rem;
    color: var(--color-primary);
    text-decoration: none;
}

.game-title {
    font-size: 2rem;
    margin: 0.5rem 0;
}

.game-subtitle {
    color: #666;
    font-size: 1.1rem;
}

.game-progress {
    margin-bottom: 2rem;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.progress-fill {
    height: 100%;
    background: var(--color-primary);
    transition: width 0.3s ease;
    width: 0%;
}

.progress-text {
    text-align: center;
    font-size: 0.9rem;
    color: #666;
}

.question-container {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.question-number {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 1rem;
}

.explanation-text {
    font-size: 1rem;
    color: #666;
    margin-bottom: 1.5rem;
    font-style: italic;
}

.sentence-builder-area {
    margin: 2rem 0;
}

.sentence-slots {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: center;
    align-items: center;
    min-height: 80px;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.sentence-slot {
    min-width: 100px;
    min-height: 50px;
    border: 2px dashed #ccc;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.sentence-slot.drag-over {
    border-color: var(--color-primary);
    background: #e7f3ff;
}

.sentence-slot.filled {
    border-color: #28a745;
    background: #d4edda;
}

.sentence-slot.incorrect {
    border-color: #dc3545;
    background: #f8d7da;
}

.slot-word {
    font-weight: 600;
    font-size: 1.1rem;
    padding: 0.5rem 1rem;
}

.slot-remove {
    position: absolute;
    top: -8px;
    right: -8px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #dc3545;
    color: white;
    border: none;
    cursor: pointer;
    font-size: 1rem;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.word-options {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    justify-content: center;
    padding: 1rem;
}

.word-option {
    padding: 0.75rem 1.5rem;
    background: white;
    border: 2px solid #ddd;
    border-radius: 8px;
    cursor: move;
    font-weight: 500;
    font-size: 1rem;
    transition: all 0.2s ease;
    user-select: none;
}

.word-option:hover {
    border-color: var(--color-primary);
    transform: translateY(-2px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.word-option.dragging {
    opacity: 0.5;
}

.word-option.used {
    opacity: 0.3;
    cursor: not-allowed;
    background: #f0f0f0;
}

.game-controls {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
}

.btn {
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-primary {
    background: var(--color-primary);
    color: white;
}

.btn-primary:hover:not(:disabled) {
    opacity: 0.9;
    transform: translateY(-1px);
}

.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.hidden {
    display: none !important;
}

.feedback-message {
    text-align: center;
    padding: 1rem;
    border-radius: 8px;
    margin: 1rem 0;
    font-weight: 600;
}

.feedback-message.correct {
    background: #d4edda;
    color: #155724;
}

.feedback-message.incorrect {
    background: #f8d7da;
    color: #721c24;
}

.game-complete {
    text-align: center;
    padding: 3rem 2rem;
}

.completion-content h2 {
    font-size: 2rem;
    margin-bottom: 1rem;
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
    font-weight: 700;
    color: var(--color-primary);
}

.stat-label {
    display: block;
    color: #666;
    font-size: 0.9rem;
    margin-top: 0.5rem;
}

.completion-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
}
</style>

<div class="sentence-builder-container">
    @include('partials.student-game-locale-bar')
    <div class="game-header">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            @include('partials.student-lesson-back-link', ['lesson' => $lesson, 'org' => $org ?? null, 'linkClass' => 'back-link'])
            @include('partials.admin-edit-lesson', [
                'lesson' => $lesson,
                'activityEditUrl' => route('admin.lessons.sentence-builder-games.show', [$lesson, $game]),
                'activityEditLabel' => 'Edit Game',
            ])
        </div>
        <h1 class="game-title">{{ __('student-portal.games.sentence_builder') }}</h1>
        <p class="game-subtitle student-learning-ltr student-learning-ltr--text-start" dir="ltr" lang="en">{{ $lesson->title }}</p>
    </div>

    @if($questions->count() > 0)
        <!-- Game Screen -->
        <div class="game-screen" id="game-screen">
            <div class="game-progress">
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                <div class="progress-text">
                    <span id="progress-display">{{ __('student-portal.games.question_of', ['current' => 1, 'total' => $questions->count()]) }}</span>
                </div>
            </div>

            <div class="question-container" id="question-container">
                <!-- Question content will be loaded here by JavaScript -->
            </div>

            <div class="game-controls">
                <button id="check-btn" class="btn btn-primary" disabled>{{ __('student-portal.games.check_answer') }}</button>
                <button id="next-btn" class="btn btn-primary hidden">{{ __('student-portal.games.next_question') }}</button>
                <button id="restart-btn" class="btn btn-secondary">{{ __('student-portal.games.restart') }}</button>
            </div>
        </div>

        <!-- Game Complete Screen -->
        <div class="game-complete hidden" id="game-complete">
            <div class="completion-content">
                <h2>🎉 {{ __('student-portal.games.game_complete') }}</h2>
                <p id="completion-message">{{ __('student-portal.games.sentence_builder_complete') }}</p>
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
                    @include('partials.student-lesson-back-link', ['lesson' => $lesson, 'org' => $org ?? null, 'linkClass' => 'btn btn-secondary'])
                </div>
            </div>
        </div>
    @else
        <div class="empty-state">
            <h3>{{ __('student-portal.games.no_questions') }}</h3>
            <p>{{ __('student-portal.games.no_questions_sentence_builder') }}</p>
            @include('partials.student-lesson-back-link', ['lesson' => $lesson, 'org' => $org ?? null, 'linkClass' => 'btn btn-primary'])
        </div>
    @endif
</div>

@include('partials.student-game-i18n')

<script>
const questions = @json($questionsData ?? []);
const lessonId = {{ $lesson->id }};
const gameId = {{ $game->id }};
let currentQuestionIndex = 0;
let correctAnswers = 0;
let userAnswers = [];
let gameStartTime = null;
let currentAnswer = [];
let isAnswerChecked = false;

// Activity event tracking
const activityEventEndpoint = '{{ route('activity-events.store') }}';
const activityEventPayload = {
    lesson_id: lessonId,
    activity_type: 'sentence_builder',
    activity_id: gameId,
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
const progressDisplay = document.getElementById('progress-display');
const checkBtn = document.getElementById('check-btn');
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

    checkBtn.addEventListener('click', checkAnswer);
    nextBtn.addEventListener('click', nextQuestion);
    restartBtn.addEventListener('click', restartGame);
    playAgainBtn.addEventListener('click', restartGame);
});

function loadQuestion(index) {
    if (index >= questions.length) {
        showCompletionScreen();
        return;
    }

    const question = questions[index];
    currentAnswer = [];
    isAnswerChecked = false;
    
    // Update progress
    const progress = ((index + 1) / questions.length) * 100;
    progressFill.style.width = progress + '%';
    if (progressDisplay) {
        progressDisplay.textContent = gameT('question_of', {
            current: index + 1,
            total: questions.length,
        });
    }
    
    // Render question
    renderQuestion(question);
    
    // Reset buttons
    checkBtn.classList.remove('hidden');
    checkBtn.disabled = true;
    nextBtn.classList.add('hidden');
}

function renderQuestion(question) {
    const correctSentence = question.correct_sentence;
    const wordOptions = [...question.word_options].sort(() => Math.random() - 0.5); // Shuffle
    
    questionContainer.innerHTML = `
        <div class="question-number">${gameT('question_of', { current: currentQuestionIndex + 1, total: questions.length })}</div>
        <div class="explanation-text">${question.explanation}</div>
        
        <div class="sentence-builder-area student-learning-ltr" dir="ltr" lang="en">
            <div class="sentence-slots" id="sentence-slots">
                ${correctSentence.map((_, i) => `
                    <div class="sentence-slot" data-slot-index="${i}">
                        <span class="slot-placeholder">${gameT('drop_word_here')}</span>
                    </div>
                `).join('')}
            </div>
            
            <div class="word-options" id="word-options">
                ${wordOptions.map((word, i) => `
                    <div class="word-option" draggable="true" data-word="${word}" data-word-index="${i}">
                        ${word}
                    </div>
                `).join('')}
            </div>
        </div>
        
        <div id="feedback-message" class="feedback-message hidden"></div>
    `;
    
    setupDragAndDrop();
    updateCheckButton();
}

function setupDragAndDrop() {
    const wordOptions = document.querySelectorAll('.word-option');
    const sentenceSlots = document.querySelectorAll('.sentence-slot');
    
    // Setup draggable words
    wordOptions.forEach(option => {
        option.addEventListener('dragstart', handleDragStart);
        option.addEventListener('dragend', handleDragEnd);
        option.addEventListener('click', handleWordClick);
    });
    
    // Setup drop zones
    sentenceSlots.forEach(slot => {
        slot.addEventListener('dragover', handleDragOver);
        slot.addEventListener('drop', handleDrop);
        slot.addEventListener('dragenter', handleDragEnter);
        slot.addEventListener('dragleave', handleDragLeave);
    });
}

let draggedWord = null;

function handleDragStart(e) {
    if (isAnswerChecked) return;
    draggedWord = e.target;
    e.target.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', e.target.dataset.word);
}

function handleDragEnd(e) {
    e.target.classList.remove('dragging');
    draggedWord = null;
}

function handleDragOver(e) {
    if (isAnswerChecked) return;
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
}

function handleDragEnter(e) {
    if (isAnswerChecked) return;
    e.preventDefault();
    e.target.closest('.sentence-slot').classList.add('drag-over');
}

function handleDragLeave(e) {
    e.target.closest('.sentence-slot')?.classList.remove('drag-over');
}

function handleDrop(e) {
    if (isAnswerChecked) return;
    e.preventDefault();
    const slot = e.target.closest('.sentence-slot');
    slot.classList.remove('drag-over');
    
    const word = e.dataTransfer.getData('text/plain');
    const slotIndex = parseInt(slot.dataset.slotIndex);
    
    // Remove word from options if it's already used
    const wordOption = document.querySelector(`[data-word="${word}"]`);
    if (wordOption && !wordOption.classList.contains('used')) {
        wordOption.classList.add('used');
    }
    
    // Fill slot
    fillSlot(slot, slotIndex, word);
    updateCheckButton();
}

function handleWordClick(e) {
    if (isAnswerChecked) return;
    const word = e.target.dataset.word;
    const wordOption = e.target;
    
    // Find first empty slot
    const emptySlot = document.querySelector('.sentence-slot:not(.filled)');
    if (!emptySlot) return;
    
    const slotIndex = parseInt(emptySlot.dataset.slotIndex);
    
    // Mark word as used
    if (!wordOption.classList.contains('used')) {
        wordOption.classList.add('used');
    }
    
    // Fill slot
    fillSlot(emptySlot, slotIndex, word);
    updateCheckButton();
}

function fillSlot(slot, slotIndex, word) {
    // Remove placeholder
    slot.innerHTML = '';
    
    // Add word
    const wordSpan = document.createElement('span');
    wordSpan.className = 'slot-word';
    wordSpan.textContent = word;
    slot.appendChild(wordSpan);
    
    // Add remove button
    const removeBtn = document.createElement('button');
    removeBtn.className = 'slot-remove';
    removeBtn.textContent = '×';
    removeBtn.onclick = () => removeWordFromSlot(slot, slotIndex, word);
    slot.appendChild(removeBtn);
    
    slot.classList.add('filled');
    currentAnswer[slotIndex] = word;
}

function removeWordFromSlot(slot, slotIndex, word) {
    if (isAnswerChecked) return;
    
    // Remove from answer
    currentAnswer[slotIndex] = null;
    
    // Clear slot
    slot.innerHTML = '<span class="slot-placeholder">' + gameT('drop_word_here') + '</span>';
    slot.classList.remove('filled', 'incorrect', 'correct');
    
    // Return word to options
    const wordOption = document.querySelector(`[data-word="${word}"]`);
    if (wordOption) {
        wordOption.classList.remove('used');
    }
    
    updateCheckButton();
}

function updateCheckButton() {
    const filledSlots = currentAnswer.filter(w => w !== null && w !== undefined).length;
    const totalSlots = questions[currentQuestionIndex].correct_sentence.length;
    checkBtn.disabled = filledSlots !== totalSlots || isAnswerChecked;
}

function checkAnswer() {
    if (isAnswerChecked) return;
    
    const question = questions[currentQuestionIndex];
    const correctSentence = question.correct_sentence;
    const userSentence = currentAnswer.filter(w => w !== null && w !== undefined);
    
    const isCorrect = JSON.stringify(userSentence) === JSON.stringify(correctSentence);
    
    // Show feedback
    const feedbackMessage = document.getElementById('feedback-message');
    feedbackMessage.classList.remove('hidden', 'correct', 'incorrect');
    
    if (isCorrect) {
        feedbackMessage.classList.add('correct');
        feedbackMessage.textContent = '✓ ' + gameT('correct_great_job');
        correctAnswers++;
        
        // Mark slots as correct
        document.querySelectorAll('.sentence-slot').forEach(slot => {
            slot.classList.add('correct');
        });
        
        logActivityEvent('answered', {
            question_index: currentQuestionIndex,
            correct: true,
        });
    } else {
        feedbackMessage.classList.add('incorrect');
        feedbackMessage.textContent = '✗ ' + gameT('not_quite');
        
        // Mark incorrect slots
        userSentence.forEach((word, index) => {
            if (word !== correctSentence[index]) {
                const slot = document.querySelector(`[data-slot-index="${index}"]`);
                if (slot) {
                    slot.classList.add('incorrect');
                }
            }
        });
        
        logActivityEvent('answered', {
            question_index: currentQuestionIndex,
            correct: false,
        });
    }
    
    isAnswerChecked = true;
    checkBtn.classList.add('hidden');
    nextBtn.classList.remove('hidden');
    
    userAnswers.push({
        question: currentQuestionIndex,
        answer: userSentence,
        correct: isCorrect,
    });
}

function nextQuestion() {
    currentQuestionIndex++;
    loadQuestion(currentQuestionIndex);
}

function restartGame() {
    currentQuestionIndex = 0;
    correctAnswers = 0;
    userAnswers = [];
    gameStartTime = Date.now();
    gameScreen.classList.remove('hidden');
    gameComplete.classList.add('hidden');
    logActivityEvent('restarted');
    loadQuestion(0);
}

function showCompletionScreen() {
    gameScreen.classList.add('hidden');
    gameComplete.classList.remove('hidden');
    
    const accuracy = questions.length > 0 ? Math.round((correctAnswers / questions.length) * 100) : 0;
    completionScore.textContent = `${correctAnswers} / ${questions.length}`;
    completionAccuracy.textContent = `${accuracy}%`;
    
    if (accuracy === 100) {
        completionMessage.textContent = gameT('completion_all_correct');
    } else if (accuracy >= 80) {
        completionMessage.textContent = gameT('good_job');
    } else if (accuracy >= 60) {
        completionMessage.textContent = gameT('keep_practicing');
    } else {
        completionMessage.textContent = gameT('great_job_practicing');
    }
    
    const duration = Math.round((Date.now() - gameStartTime) / 1000);
    logActivityEvent('completed', {
        correct_answers: correctAnswers,
        total_questions: questions.length,
        accuracy: accuracy,
        duration_seconds: duration,
    });
}
</script>
@endsection


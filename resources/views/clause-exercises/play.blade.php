@extends('layouts.app')

@section('title', 'Clause Exercise - ' . $lesson->title)

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
<div class="clause-exercise-container">
    <div class="game-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <a href="{{ route('lessons.show', $lesson->slug) }}" class="back-link">&larr; Back to Lesson</a>
            @include('partials.admin-edit-lesson', [
                'lesson' => $lesson,
                'activityEditUrl' => route('admin.lessons.clause-exercises.edit', [$lesson, $clauseExercise]),
                'activityEditLabel' => 'Edit Exercise',
            ])
        </div>
        <h1 class="game-title">{{ $clauseExercise->title }}</h1>
        <p class="game-subtitle">{{ $lesson->title }}</p>
    </div>

    <div class="exercise-content">
        <div class="paragraph-container" id="paragraph-container">
            <!-- Paragraph with blanks will be rendered here -->
        </div>

        @if($vocabulary->count() > 0)
        <div class="vocabulary-bank" id="vocabulary-bank">
            <h3>Vocabulary Reference</h3>
            <p style="font-size: 0.875rem; color: var(--color-text-muted); margin-bottom: 1rem;">
                Listen to the vocabulary words used in this exercise:
            </p>
            <div class="vocabulary-grid" id="vocabulary-grid">
                <!-- Vocabulary words will be rendered here -->
            </div>
        </div>
        @endif

        <div class="exercise-controls">
            <button id="check-btn" class="btn btn-primary" disabled>Check Answers</button>
            <button id="reset-btn" class="btn btn-secondary">Reset</button>
        </div>

        <div class="exercise-results" id="exercise-results" style="display: none;">
            <div class="results-content">
                <h2 id="results-title"></h2>
                <div class="score-display" id="score-display"></div>
                <div class="results-actions">
                    <button id="try-again-btn" class="btn btn-primary">Try Again</button>
                    @include('partials.guided-flow-nav', ['guidedFlow' => $guidedFlow ?? null, 'lesson' => $lesson])
                </div>
            </div>
        </div>
    </div>
</div>

<script>
@php
    // Pre-encode to avoid Blade parsing issues with {{ in paragraph_text
    $paragraphTextJson = json_encode($clauseExercise->paragraph_text ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $blanksJson = json_encode($clauseExercise->blanks ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $correctAnswersJson = json_encode($clauseExercise->correct_answers ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $blankPositionsJson = json_encode($clauseExercise->blank_positions ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $blankMetadataJson = json_encode($clauseExercise->blank_metadata ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
@endphp
const exerciseData = {
    paragraph_text: {!! $paragraphTextJson !!},
    blanks: {!! $blanksJson !!},
    // Backward compatibility
    correct_answers: {!! $correctAnswersJson !!},
    blank_positions: {!! $blankPositionsJson !!},
    blank_metadata: {!! $blankMetadataJson !!},
};

@php
    $vocabArray = $vocabulary->map(function($vocab) {
        return [
            'id' => $vocab->id,
            'word' => $vocab->english_word,
            'audio' => $vocab->word_audio_url ?? null,
        ];
    })->values()->toArray();
@endphp
const vocabulary = @json($vocabArray);

let userAnswers = {};
let checked = false;
let activityStartTime = null;

// Activity event tracking
const activityEventEndpoint = '{{ route('activity-events.store') }}';
const activityEventPayload = {
    lesson_id: {{ $lesson->id }},
    activity_type: 'clause_exercise',
    activity_id: {{ $clauseExercise->id }},
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

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    activityStartTime = Date.now();
    logActivityEvent('started', {
        exercise_title: '{{ $clauseExercise->title }}',
    });
    
    renderParagraph();
    @if($vocabulary->count() > 0)
    renderVocabularyBank();
    @endif
    setupEventListeners();
});

// Render paragraph with blanks
function renderParagraph() {
    const container = document.getElementById('paragraph-container');
    let paragraph = exerciseData.paragraph_text || '';
    const blanks = exerciseData.blanks || {}; // New format: object keyed by blank_id
    
    // Extract all blank_id tokens - only matches blank_\d+ format
    // Use RegExp constructor to avoid Blade parsing issues
    const tokenRegex = new RegExp('\\{\\{' + '(blank_\\d+)' + '\\}\\}', 'g');
    const tokens = [];
    let match;
    while ((match = tokenRegex.exec(paragraph)) !== null) {
        tokens.push(match[1]); // blank_1, blank_2, etc.
    }
    
    if (tokens.length === 0) {
        // Fallback: try old {} format for backward compatibility
        const oldPlaceholders = paragraph.match(/\{\}/g);
        if (oldPlaceholders && oldPlaceholders.length > 0) {
            console.warn('Using legacy {} placeholder format. Please regenerate exercise.');
            // Use old rendering logic as fallback
            return renderParagraphLegacy();
        }
        container.innerHTML = `<div class="alert alert-error">
            <p><strong>Error:</strong> This exercise is missing blank placeholders.</p>
        </div>`;
        return;
    }
    
    // Replace each token with dropdown
    tokens.forEach(token => {
        const blank = blanks[token];
        
        if (!blank) {
            console.warn(`Missing blank data for token: ${token}`);
            // Use regex to match the token pattern, avoiding Blade parsing issues
            const tokenPattern = new RegExp('\\{\\{' + token + '\\}\\}', 'g');
            paragraph = paragraph.replace(
                tokenPattern,
                '<select class="blank-select" disabled data-blank-id="' + escapeHtml(token) + '"><option>Blank unavailable</option></select>'
            );
            return;
        }
        
        // Build options
        let options = [];
        
        // CRITICAL: Always get the correct answer first
        const correctText = blank.correct?.text || '';
        
        if (!correctText) {
            console.error(`CRITICAL ERROR: Blank ${token} has no correct answer!`, blank);
            // Use regex to match the token pattern, avoiding Blade parsing issues
            const tokenPattern = new RegExp('\\{\\{' + token + '\\}\\}', 'g');
            paragraph = paragraph.replace(
                tokenPattern,
                '<select class="blank-select" disabled data-blank-id="' + escapeHtml(token) + '" style="border: 2px solid red;"><option>ERROR: Missing correct answer</option></select>'
            );
            return;
        }
        
        // ALWAYS add correct answer first (most important!)
        options.push({value: correctText, label: correctText, isCorrect: true});
        
        // Add distractors
        (blank.distractors || []).forEach(dist => {
            const distText = dist.text || '';
            if (distText && distText !== correctText) {
                options.push({value: distText, label: distText, isCorrect: false});
            }
        });
        
        // CRITICAL VALIDATION: Ensure correct answer is in options
        const hasCorrectAnswer = options.some(opt => opt.isCorrect && opt.value === correctText);
        if (!hasCorrectAnswer) {
            console.error(`CRITICAL ERROR: Correct answer "${correctText}" not found in options for blank ${token}!`, options);
            // Force add correct answer if missing
            options.unshift({value: correctText, label: correctText, isCorrect: true});
        }
        
        // Ensure we have at least 4 options (1 correct + 3 distractors)
        if (options.length < 4) {
            console.warn(`Blank ${token} has only ${options.length} options (expected 4). Correct answer: "${correctText}"`, blank);
        }
        
        // Ensure we have at least the correct answer
        if (options.length === 0) {
            console.error(`CRITICAL: No options found for blank: ${token}`, blank);
            // Use regex to match the token pattern, avoiding Blade parsing issues
            const tokenPattern = new RegExp('\\{\\{' + token + '\\}\\}', 'g');
            paragraph = paragraph.replace(
                tokenPattern,
                '<select class="blank-select" disabled data-blank-id="' + escapeHtml(token) + '" style="border: 2px solid red;"><option>ERROR: No options available</option></select>'
            );
            return;
        }
        
        // Shuffle options
        const shuffled = options.sort(() => Math.random() - 0.5);
        
        const optionsHtml = shuffled.map(opt => 
            `<option value="${escapeHtml(opt.value)}">${escapeHtml(opt.label)}</option>`
        ).join('');
        
        const selectHtml = `<select class="blank-select" data-blank-id="${escapeHtml(token)}" data-blank-type="${escapeHtml(blank.type)}" id="${escapeHtml(token)}">
            <option value="">_____</option>
            ${optionsHtml}
        </select>`;
        
        // Use regex to match the token pattern, avoiding Blade parsing issues
        const tokenPattern = new RegExp('\\{\\{' + token + '\\}\\}', 'g');
        paragraph = paragraph.replace(tokenPattern, selectHtml);
    });
    
    container.innerHTML = `<div class="paragraph-text">${paragraph}</div>`;
    setupBlankSelects();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Legacy rendering for old {} format (backward compatibility)
function renderParagraphLegacy() {
    const container = document.getElementById('paragraph-container');
    let paragraph = exerciseData.paragraph_text || '';
    const blankMetadata = exerciseData.blank_metadata || {};
    const correctAnswers = exerciseData.correct_answers || {};
    
    let blankIndex = 0;
    const blankIds = Object.keys(blankMetadata);
    
    paragraph = paragraph.replace(/\{\}/g, () => {
        if (blankIndex >= blankIds.length) {
            return '<select class="blank-select" disabled><option>Blank unavailable</option></select>';
        }
        
        const blankId = blankIds[blankIndex];
        const metadata = blankMetadata[blankId];
        blankIndex++;
        
        if (!metadata) {
            return '<select class="blank-select" disabled><option>Blank unavailable</option></select>';
        }
        
        let options = [];
        
        if (metadata.type === 'vocab') {
            const correctVocabId = correctAnswers[blankId];
            const distractorIds = metadata.distractors || [];
            
            const correctVocab = vocabulary.find(v => v.id == correctVocabId);
            if (correctVocab) {
                options.push({value: String(correctVocabId), label: correctVocab.word, isCorrect: true});
            }
            
            distractorIds.forEach(distractorId => {
                const distractorVocab = vocabulary.find(v => v.id == distractorId);
                if (distractorVocab) {
                    options.push({value: String(distractorId), label: distractorVocab.word, isCorrect: false});
                }
            });
        } else if (metadata.type === 'grammar') {
            const correctWord = String(correctAnswers[blankId]);
            const distractors = metadata.distractors || [];
            
            options.push({value: correctWord, label: correctWord, isCorrect: true});
            distractors.forEach(distractor => {
                options.push({value: String(distractor), label: String(distractor), isCorrect: false});
            });
        }
        
        if (options.length === 0) {
            return '<select class="blank-select" disabled><option>Blank unavailable</option></select>';
        }
        
        const shuffled = options.sort(() => Math.random() - 0.5);
        const optionsHtml = shuffled.map(opt => 
            `<option value="${escapeHtml(opt.value)}">${escapeHtml(opt.label)}</option>`
        ).join('');
        
        return `<select class="blank-select" data-blank-id="${escapeHtml(blankId)}" data-blank-type="${escapeHtml(metadata.type)}" id="${escapeHtml(blankId)}">
            <option value="">_____</option>
            ${optionsHtml}
        </select>`;
    });
    
    container.innerHTML = `<div class="paragraph-text">${paragraph}</div>`;
    setupBlankSelects();
}

// Setup blank select event listeners
function setupBlankSelects() {
    const blanks = document.querySelectorAll('.blank-select');
    blanks.forEach(blank => {
        blank.addEventListener('change', function() {
            const blankId = this.dataset.blankId;
            const blankType = this.dataset.blankType;
            const selectedValue = this.value;
            
            if (selectedValue) {
                // Store the selected value (vocab ID or grammar word string)
                userAnswers[blankId] = selectedValue;
                this.classList.add('filled');
            } else {
                delete userAnswers[blankId];
                this.classList.remove('filled');
            }
            
            updateCheckButton();
        });
    });
}

// Render vocabulary bank (for reference/audio)
function renderVocabularyBank() {
    const grid = document.getElementById('vocabulary-grid');
    const shuffled = [...vocabulary].sort(() => Math.random() - 0.5);
    
    grid.innerHTML = shuffled.map(vocab => `
        <div class="vocab-card">
            <span class="vocab-word">${vocab.word}</span>
            ${vocab.audio ? `<button type="button" class="vocab-audio-btn talma-audio-btn" data-audio-url="${vocab.audio}" title="Listen">
                <i class="fas fa-play talma-audio-icon"></i>
            </button>` : ''}
        </div>
    `).join('');
}


function updateCheckButton() {
    const blanks = exerciseData.blanks || {};
    const correctAnswers = exerciseData.correct_answers || {}; // Fallback
    const totalBlanks = Object.keys(blanks).length > 0 ? Object.keys(blanks).length : Object.keys(correctAnswers).length;
    const filledBlanks = document.querySelectorAll('.blank-select:not([value=""])').length;
    const checkBtn = document.getElementById('check-btn');
    
    if (!checkBtn) return;
    
    if (totalBlanks === 0) {
        checkBtn.disabled = true;
        return;
    }
    
    if (filledBlanks === totalBlanks && !checked) {
        checkBtn.disabled = false;
    } else {
        checkBtn.disabled = true;
    }
}

function setupEventListeners() {
    document.getElementById('check-btn').addEventListener('click', checkAnswers);
    document.getElementById('reset-btn').addEventListener('click', resetExercise);
    document.getElementById('try-again-btn').addEventListener('click', resetExercise);
}

function checkAnswers() {
    checked = true;
    const blanks = exerciseData.blanks || {};
    const correctAnswers = exerciseData.correct_answers || {}; // Fallback for old format
    const blankMetadata = exerciseData.blank_metadata || {}; // Fallback for old format
    
    // Use new format if available, otherwise fall back to old
    const useNewFormat = Object.keys(blanks).length > 0;
    const totalBlanks = useNewFormat ? Object.keys(blanks).length : Object.keys(correctAnswers).length;
    
    if (totalBlanks === 0) {
        console.error('No blanks found in exercise data');
        alert('Error: This exercise has no blanks to check.');
        checked = false;
        return;
    }
    
    let correctCount = 0; // Initialize counter
    
    // Check each blank
    const blankIds = useNewFormat ? Object.keys(blanks) : Object.keys(correctAnswers);
    blankIds.forEach(blankId => {
        const blankSelect = document.querySelector(`[data-blank-id="${blankId}"]`);
        if (!blankSelect) {
            console.error(`Blank select not found for blankId: ${blankId}`);
            return;
        }
        
        const blankType = blankSelect.dataset.blankType;
        const userAnswer = blankSelect.value;
        
        let correctAnswer;
        if (useNewFormat) {
            const blank = blanks[blankId];
            correctAnswer = blank?.correct?.text || '';
        } else {
            // Old format
            if (blankType === 'vocab') {
                const correctVocabId = correctAnswers[blankId];
                const correctVocab = vocabulary.find(v => v.id == correctVocabId);
                correctAnswer = correctVocab ? String(correctVocabId) : '';
            } else {
                correctAnswer = String(correctAnswers[blankId] || '');
            }
        }
        
        if (!correctAnswer) {
            console.error(`Correct answer not found for blankId: ${blankId}`);
            blankSelect.classList.add('incorrect');
            blankSelect.disabled = true;
            return;
        }
        
        // Compare answers based on type
        let isCorrect = false;
        if (useNewFormat) {
            // New format: always compare text (case-insensitive)
            isCorrect = userAnswer.toLowerCase().trim() === String(correctAnswer).toLowerCase().trim();
        } else {
            // Old format
            if (blankType === 'vocab') {
                // For vocab blanks, compare IDs (both should be numbers)
                isCorrect = parseInt(userAnswer) === parseInt(correctAnswer);
            } else if (blankType === 'grammar') {
                // For grammar blanks, compare word strings (case-insensitive)
                isCorrect = userAnswer.toLowerCase().trim() === String(correctAnswer).toLowerCase().trim();
            } else {
                // Fallback: try both methods
                isCorrect = (userAnswer == correctAnswer) || 
                           (userAnswer.toLowerCase().trim() === String(correctAnswer).toLowerCase().trim());
            }
        }
        
        if (isCorrect) {
            correctCount++;
            blankSelect.classList.add('correct');
        } else {
            blankSelect.classList.add('incorrect');
            // Set correct answer - ensure value matches option values
            const correctValue = String(correctAnswer);
            // Check if the value exists in the select options
            const optionExists = Array.from(blankSelect.options).some(opt => opt.value === correctValue);
            if (optionExists) {
                blankSelect.value = correctValue;
            } else {
                // If option doesn't exist, try to find it by comparing values
                // This handles cases where the value might be stored differently
                const matchingOption = Array.from(blankSelect.options).find(opt => {
                    if (blankType === 'vocab') {
                        return parseInt(opt.value) === parseInt(correctAnswer);
                    } else {
                        return opt.value.toLowerCase().trim() === String(correctAnswer).toLowerCase().trim();
                    }
                });
                if (matchingOption) {
                    blankSelect.value = matchingOption.value;
                } else {
                    console.error(`Could not find matching option for correct answer: ${correctAnswer} in blank ${blankId}`);
                }
            }
            blankSelect.classList.add('show-correct');
        }
        blankSelect.disabled = true;
    });
    
    // Disable vocabulary cards (visual feedback)
    document.querySelectorAll('.vocab-card').forEach(card => {
        card.style.opacity = '0.5';
    });
    
    // Show results
    const percentage = Math.round((correctCount / totalBlanks) * 100);
    const durationSeconds = Math.round((Date.now() - activityStartTime) / 1000);
    
    logActivityEvent('completed', {
        score: correctCount,
        total: totalBlanks,
        percentage: percentage,
        duration_seconds: durationSeconds,
    });
    
    document.getElementById('results-title').textContent = 
        percentage === 100 ? 'Perfect! 🎉' : 
        percentage >= 70 ? 'Good Job! 👍' : 
        'Keep Practicing! 💪';
    
    document.getElementById('score-display').innerHTML = `
        <div class="score-value">${correctCount}/${totalBlanks}</div>
        <div class="score-percentage">${percentage}%</div>
    `;
    
    document.getElementById('exercise-results').style.display = 'block';
    document.getElementById('check-btn').disabled = true;
}

function resetExercise() {
    userAnswers = {};
    checked = false;
    activityStartTime = Date.now();
    
    logActivityEvent('started', {
        exercise_title: '{{ $clauseExercise->title }}',
        is_restart: true,
    });
    
    renderParagraph();
    @if($vocabulary->count() > 0)
    renderVocabularyBank();
    @endif
    document.getElementById('exercise-results').style.display = 'none';
    document.getElementById('check-btn').disabled = true;
    
    // Reset all selects
    document.querySelectorAll('.blank-select').forEach(select => {
        select.value = '';
        select.disabled = false;
        select.classList.remove('filled', 'correct', 'incorrect', 'show-correct');
    });
    
    // Re-enable vocabulary cards
    document.querySelectorAll('.vocab-card').forEach(card => {
        card.style.opacity = '1';
    });
}

</script>

<style>
.clause-exercise-container {
    max-width: 900px;
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

.exercise-content {
    background: white;
    border-radius: var(--radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-md);
}

.paragraph-container {
    margin-bottom: 2rem;
}

.paragraph-text {
    font-size: 1.25rem;
    line-height: 2;
    padding: 1.5rem;
    background: var(--color-gray-50);
    border-radius: var(--radius-md);
    border-left: 4px solid var(--color-primary);
    min-height: 150px;
}

.blank-select {
    display: inline-block;
    min-width: 140px;
    padding: 0.5rem 1rem;
    margin: 0 0.25rem;
    border: 2px solid var(--color-border);
    border-radius: var(--radius-sm);
    background: white;
    color: var(--color-text);
    font-weight: 500;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    padding-right: 2.5rem;
}

.blank-select:hover:not(:disabled) {
    border-color: var(--color-primary);
    background-color: var(--color-primary-bg);
}

.blank-select:focus:not(:disabled) {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(0, 36, 167, 0.1);
}

.blank-select.filled {
    border-color: var(--color-primary);
    background-color: var(--color-primary-bg);
}

.blank-select.correct {
    border-color: var(--color-success);
    background-color: var(--color-success-bg);
    color: var(--color-success-dark);
}

.blank-select.incorrect {
    border-color: var(--color-danger);
    background-color: var(--color-danger-bg);
    color: var(--color-danger-dark);
}

.blank-select.show-correct {
    border-color: var(--color-info);
    background-color: var(--color-info-bg);
}

.blank-select:disabled {
    cursor: not-allowed;
    opacity: 0.8;
}

.vocabulary-bank {
    margin-bottom: 2rem;
}

.vocabulary-bank h3 {
    margin-bottom: 1rem;
    color: var(--color-text);
}

.vocabulary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
}

.vocab-card {
    background: white;
    border: 2px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.2s;
}

.vocab-card:hover {
    border-color: var(--color-primary);
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.vocab-word {
    font-weight: 600;
    color: var(--color-text);
}

.vocab-audio-btn {
    background: var(--color-info);
    color: white;
    border: none;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.vocab-audio-btn:hover {
    background: var(--color-info-dark);
    transform: scale(1.1);
}

.exercise-controls {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 2rem;
}

.exercise-results {
    text-align: center;
    background: var(--color-gray-50);
    border-radius: var(--radius-lg);
    padding: 2rem;
    margin-top: 2rem;
}

.results-content h2 {
    color: var(--color-success);
    margin-bottom: 1rem;
}

.score-display {
    margin: 1.5rem 0;
}

.score-value {
    font-size: 3rem;
    font-weight: 700;
    color: var(--color-primary);
    margin-bottom: 0.5rem;
}

.score-percentage {
    font-size: 1.5rem;
    color: var(--color-text-muted);
}

.results-actions {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-top: 2rem;
}

@media (max-width: 768px) {
    .clause-exercise-container {
        padding: 1rem;
    }
    
    .paragraph-text {
        font-size: 1rem;
        padding: 1rem;
    }
    
    .blank-select {
        min-width: 100px;
        padding: 0.4rem 0.8rem;
        font-size: 0.9rem;
        padding-right: 2rem;
    }
    
    .vocabulary-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }
    
    .results-actions {
        flex-direction: column;
    }
}
</style>
@endsection

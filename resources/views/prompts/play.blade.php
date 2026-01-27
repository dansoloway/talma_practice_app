@extends('layouts.app')

@section('title', 'Sentence Completion - ' . $lesson->title)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-6 md:py-8">
    <div class="container mx-auto px-4 max-w-4xl">
        <!-- Game Header -->
        <div class="mb-6">
            <a href="{{ route('lessons.show', $lesson->slug) }}" 
               class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium mb-4 transition-colors duration-200 group">
                <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform duration-200"></i>
                <span>Back to Lesson</span>
            </a>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 text-center">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">Sentence Completion</h1>
                <p class="text-gray-600 font-medium">{{ $lesson->title }}</p>
            </div>
        </div>

        @if($lesson->prompts->count() > 0)
            <!-- Prompt Container -->
            <div id="prompt-container" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 md:p-8 mb-6">
                <!-- Prompt content will be loaded here by JavaScript -->
            </div>

            <!-- Game Controls -->
            <div class="flex justify-center gap-4 mb-6">
                <button id="prev-btn" 
                        class="px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-300 active:scale-95 transition-all duration-200 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed" 
                        disabled>
                    Previous
                </button>
                <button id="next-btn" 
                        class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed" 
                        disabled>
                    Next
                </button>
                <button id="finish-btn" 
                        class="px-6 py-3 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md hidden">
                    Finish
                </button>
            </div>

            <!-- Game Results -->
            <div id="game-results" class="hidden bg-white rounded-2xl shadow-lg border border-gray-200 p-8 md:p-12 text-center">
                <div class="mb-8">
                    <div class="text-6xl mb-4">🎉</div>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-3">Great Job!</h2>
                    <p class="text-lg text-gray-600 mb-8">You completed all the sentence completion questions!</p>
                    <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-xl p-6 border-2 border-blue-200 inline-block">
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Final Score</h3>
                        <div class="text-4xl font-bold text-blue-600 mb-2" id="score-display">0/0</div>
                        <p class="text-lg text-gray-600" id="score-percentage">0%</p>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <button id="restart-btn" 
                            class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">
                        Try Again
                    </button>
                    <a href="{{ route('lessons.show', $lesson->slug) }}" 
                       class="px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-300 active:scale-95 transition-all duration-200 shadow-sm">
                        Back to Lesson
                    </a>
                </div>
            </div>
        @else
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center">
                <div class="text-6xl mb-4">📝</div>
                <h3 class="text-2xl font-bold text-gray-800 mb-3">No Questions Available</h3>
                <p class="text-gray-600 mb-6">This lesson doesn't have any sentence completion questions yet.</p>
                <a href="{{ route('lessons.show', $lesson->slug) }}" 
                   class="inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">
                    Back to Lesson
                </a>
            </div>
        @endif
    </div>
</div>

<audio id="prompt-audio" preload="auto"></audio>
<audio id="option-audio" preload="auto"></audio>

<script>
const lessonData = @json($lesson);
const prompts = lessonData.prompts;
let currentPromptIndex = 0;
let score = 0;
let totalQuestions = prompts.length;
let answeredQuestions = 0;
let activityStartTime = null;

// Activity event tracking
const activityEventEndpoint = '{{ route('activity-events.store') }}';
const activityEventPayload = {
    lesson_id: {{ $lesson->id }},
    activity_type: 'prompts',
    activity_id: null, // Prompts activity doesn't have a single ID
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

// Initialize the game
document.addEventListener('DOMContentLoaded', function() {
    if (prompts.length > 0) {
        activityStartTime = Date.now();
        logActivityEvent('started', {
            total_prompts: prompts.length,
        });
        loadPrompt(currentPromptIndex);
        setupDragAndDrop();
    }
});

// Create sentence with drop zone
function createSentenceWithDropZone(template) {
    return template.replace('{}', '<span class="inline-block min-w-[100px] px-4 py-2 border-2 border-dashed border-blue-500 rounded-lg bg-blue-50 text-blue-600 font-semibold text-center transition-all duration-300 cursor-pointer" id="drop-zone">_____</span>');
}

// Setup drag and drop functionality
function setupDragAndDrop() {
    // Add event listeners after DOM is updated
    setTimeout(() => {
        const draggables = document.querySelectorAll('.draggable');
        const dropZone = document.getElementById('drop-zone');
        
        if (!dropZone) return;
        
        // Setup draggable items
        draggables.forEach(draggable => {
            draggable.addEventListener('dragstart', handleDragStart);
            draggable.addEventListener('dragend', handleDragEnd);
            // Also allow click-to-select for accessibility and touch devices
            draggable.addEventListener('click', function() {
                const optionLabel = this.dataset.optionLabel;
                const optionIndex = parseInt(this.dataset.optionIndex);
                if (!dropZone || dropZone.classList.contains('filled')) return;
                dropZone.textContent = optionLabel;
                dropZone.classList.add('filled', 'border-green-500', 'bg-green-50', 'text-green-700', 'border-solid');
                dropZone.classList.remove('border-dashed', 'border-blue-500', 'bg-blue-50', 'text-blue-600');
                this.style.opacity = '0.3';
                this.draggable = false;
                handleWordSelection(optionIndex, optionLabel);
            });
        });
        
        // Setup drop zone
        dropZone.addEventListener('dragover', handleDragOver);
        dropZone.addEventListener('dragenter', handleDragEnter);
        dropZone.addEventListener('dragleave', handleDragLeave);
        dropZone.addEventListener('drop', handleDrop);
    }, 100);
}

// Drag and drop event handlers
let draggedElement = null;

function handleDragStart(e) {
    draggedElement = this;
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.outerHTML);
}

function handleDragEnd(e) {
    this.classList.remove('dragging');
    draggedElement = null;
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
}

function handleDragEnter(e) {
    e.preventDefault();
    this.classList.add('border-green-500', 'bg-green-50', 'text-green-600', 'scale-105');
    this.classList.remove('border-blue-500', 'bg-blue-50', 'text-blue-600');
}

function handleDragLeave(e) {
    this.classList.remove('border-green-500', 'bg-green-50', 'text-green-600', 'scale-105');
    if (!this.classList.contains('filled')) {
        this.classList.add('border-blue-500', 'bg-blue-50', 'text-blue-600');
    }
}

function handleDrop(e) {
    e.preventDefault();
    this.classList.remove('drag-over');
    
    if (draggedElement) {
        const optionLabel = draggedElement.dataset.optionLabel;
        const optionIndex = parseInt(draggedElement.dataset.optionIndex);
        
        // Place the word in the drop zone
        this.textContent = optionLabel;
        this.classList.add('filled', 'border-green-500', 'bg-green-50', 'text-green-700', 'border-solid');
        this.classList.remove('border-dashed', 'border-blue-500', 'bg-blue-50', 'text-blue-600', 'border-green-500', 'bg-green-50', 'text-green-600', 'scale-105');
        
        // Hide the dragged option
        draggedElement.style.opacity = '0.3';
        draggedElement.draggable = false;
        
        // Handle the word selection
        handleWordSelection(optionIndex, optionLabel);
    }
}


// Load a specific prompt
function loadPrompt(index) {
    if (index < 0 || index >= prompts.length) return;
    
    const prompt = prompts[index];
    const container = document.getElementById('prompt-container');
    
    // Create the prompt HTML
    const promptHtml = `
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-6">
                <h3 class="text-xl md:text-2xl font-bold text-gray-800 flex-1">${prompt.prompt_text}</h3>
                ${prompt.prompt_audio_path ? `
                    <button class="w-12 h-12 rounded-full bg-blue-600 text-white hover:bg-blue-700 active:scale-95 transition-all duration-200 flex items-center justify-center shadow-sm" 
                            onclick="playPromptAudio('${prompt.prompt_audio_path}')" 
                            title="Listen to question">
                        <i class="fas fa-volume-up"></i>
                    </button>
                ` : ''}
            </div>
            <div class="bg-gray-50 rounded-xl p-6 border-l-4 border-blue-500 mb-6">
                <p id="sentence-display" class="text-lg md:text-xl text-gray-800 leading-relaxed m-0">${createSentenceWithDropZone(prompt.template)}</p>
            </div>
        </div>
        
        <div class="mb-6">
            <h4 class="text-lg font-semibold text-gray-800 mb-4">Choose the correct word:</h4>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                ${prompt.options.map((option, optionIndex) => `
                    <div class="option-card bg-white rounded-xl border-2 border-gray-200 p-4 cursor-pointer hover:border-blue-400 hover:shadow-md hover:-translate-y-1 transition-all duration-200 draggable" 
                         data-option-id="${option.id}" 
                         data-option-index="${optionIndex}"
                         data-option-label="${option.label}"
                         draggable="true">
                        <div class="flex items-center justify-between gap-3">
                            <span class="option-text text-lg font-semibold text-gray-800">${option.label}</span>
                            ${option.word_audio_path ? `
                                <button class="w-10 h-10 rounded-full bg-green-600 text-white hover:bg-green-700 active:scale-95 transition-all duration-200 flex items-center justify-center shadow-sm flex-shrink-0" 
                                        onclick="playOptionAudio(event, '${option.word_audio_path}')" 
                                        title="Listen to word">
                                    <i class="fas fa-volume-up text-sm"></i>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
        
        <div id="sentence-result" class="mb-6">
            <div id="completed-sentence" class="text-lg md:text-xl font-semibold text-gray-800"></div>
        </div>
        
        <div id="audio-controls" class="hidden bg-gray-50 rounded-xl p-6 border border-gray-200">
            <div>
                <h4 class="text-lg font-semibold text-gray-800 mb-4">Listen & Practice</h4>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-xl p-6 border border-gray-200">
                        <h5 class="font-semibold text-gray-700 mb-3">Example</h5>
                        <button class="w-full px-4 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 active:scale-95 transition-all duration-200 shadow-sm" 
                                id="play-model-btn" 
                                onclick="playModelAudio()">
                            <i class="fas fa-play mr-2"></i> Play Example
                        </button>
                        <div id="model-status" class="mt-3 text-sm text-gray-600"></div>
                    </div>
                    
                    <div class="bg-white rounded-xl p-6 border border-gray-200">
                        <h5 class="font-semibold text-gray-700 mb-3">You</h5>
                        <div class="flex gap-3">
                            <button class="flex-1 px-4 py-3 bg-red-600 text-white font-semibold rounded-xl hover:bg-red-700 active:scale-95 transition-all duration-200 shadow-sm" 
                                    id="record-btn" 
                                    onclick="toggleRecording()">
                                <i class="fas fa-microphone mr-2"></i> Record
                            </button>
                            <button class="flex-1 px-4 py-3 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 active:scale-95 transition-all duration-200 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed" 
                                    id="play-recording-btn" 
                                    onclick="playRecording()" 
                                    disabled>
                                <i class="fas fa-play mr-2"></i> Play
                            </button>
                        </div>
                        <div id="recording-status" class="mt-3 text-sm text-gray-600"></div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.innerHTML = promptHtml;
    
    // Setup drag and drop for this prompt
    setupDragAndDrop();
    
    // Reset recording state for new prompt
    resetRecordingState();
    
    // Update navigation buttons
    document.getElementById('prev-btn').disabled = index === 0;
    document.getElementById('next-btn').disabled = true; // Disabled until answer is selected
    
    // Show finish button on last question
    if (index === prompts.length - 1) {
        document.getElementById('next-btn').classList.add('hidden');
        document.getElementById('finish-btn').classList.remove('hidden');
        document.getElementById('finish-btn').disabled = true;
    } else {
        document.getElementById('next-btn').classList.remove('hidden');
        document.getElementById('finish-btn').classList.add('hidden');
    }
}

// Handle word selection after drag and drop
function handleWordSelection(optionIndex, selectedWord) {
    const prompt = prompts[currentPromptIndex];
    const options = document.querySelectorAll('.option-card');
    const audioControls = document.getElementById('audio-controls');
    const completedSentence = document.getElementById('completed-sentence');
    
    // Mark the selected option
    const selectedOption = options[optionIndex];
    selectedOption.classList.add('border-blue-500', 'bg-blue-50');
    
    // Check if the answer is correct using 1-based index
    const isCorrect = checkAnswer(optionIndex + 1, prompt.correct_answer);
    
    // Determine which option to use for sentence and audio display
    // If there's a correct answer and we got it wrong, use the correct option
    let displayOptionData = prompt.options[optionIndex];
    let displayWord = selectedWord;
    
    if (isCorrect === false && typeof prompt.correct_answer === 'number') {
        const correctIdx = prompt.correct_answer - 1;
        displayOptionData = prompt.options[correctIdx];
        displayWord = displayOptionData.label;
    }
    
    // Show the completed sentence
    const fullSentence = prompt.template.replace('{}', displayWord);
    completedSentence.textContent = fullSentence;
    
    // Store the pre-generated sentence audio path
    window.currentSentenceAudioPath = displayOptionData.sentence_audio_path;
    
    // Update score if this is the first time answering this question
    if (!prompt.answered) {
        if (isCorrect === true) {
            score++;
        }
        answeredQuestions++;
        prompt.answered = true;
    }
    
    // Show feedback and highlight selection
    showAnswerFeedback(isCorrect, selectedOption);

    // Highlight the correct option if answered incorrectly
    if (isCorrect === false && typeof prompt.correct_answer === 'number') {
        const correctIdx = prompt.correct_answer - 1;
        if (options[correctIdx]) {
            options[correctIdx].classList.add('border-green-500', 'bg-green-50');
            options[correctIdx].classList.remove('border-gray-200', 'border-blue-500', 'bg-blue-50');
        }
        selectedOption.classList.add('border-red-500', 'bg-red-50');
        selectedOption.classList.remove('border-gray-200', 'border-blue-500', 'bg-blue-50');
    } else if (isCorrect === true) {
        selectedOption.classList.add('border-green-500', 'bg-green-50');
        selectedOption.classList.remove('border-gray-200', 'border-blue-500', 'bg-blue-50');
    }
    
    // Update progress display
    updateProgressDisplay();
    
    // Debug logging
    console.log('Selected option:', displayOptionData);
    console.log('Sentence audio path:', displayOptionData.sentence_audio_path);
    console.log('Next button should be enabled');
    
    // Reset recording state when new word is selected
    resetRecordingState();
    
        // Show audio controls
    if (audioControls) {
        audioControls.classList.remove('hidden');
    }
    
    // Enable next/finish button
    if (currentPromptIndex === prompts.length - 1) {
        document.getElementById('finish-btn').disabled = false;
        console.log('Finish button enabled');
    } else {
        document.getElementById('next-btn').disabled = false;
        console.log('Next button enabled, current index:', currentPromptIndex, 'total prompts:', prompts.length);
    }
}

// Check if the selected answer is correct
function checkAnswer(selectedOptionNumber, correctAnswer) {
    if (correctAnswer === null || correctAnswer === undefined) {
        return null; // No correct answer defined
    }
    return selectedOptionNumber === correctAnswer;
}

// Show feedback for the answer
function showAnswerFeedback(isCorrect, selectedOption) {
    // Remove any existing feedback classes (already handled in handleWordSelection)
    
    // Add appropriate feedback message
    if (isCorrect === true) {
        showFeedbackMessage('Correct! 🎉', 'success');
    } else if (isCorrect === false) {
        showFeedbackMessage('Try again! 💪', 'error');
    }
    // If isCorrect is null, no feedback is shown
}

// Show feedback message
function showFeedbackMessage(message, type) {
    // Remove existing feedback message
    const existingFeedback = document.getElementById('answer-feedback');
    if (existingFeedback) {
        existingFeedback.remove();
    }
    
    // Create new feedback message with Tailwind classes
    const feedback = document.createElement('div');
    feedback.id = 'answer-feedback';
    if (type === 'success') {
        feedback.className = 'mt-4 p-4 rounded-xl bg-green-50 border-l-4 border-green-500 text-green-700 font-semibold text-center';
    } else {
        feedback.className = 'mt-4 p-4 rounded-xl bg-red-50 border-l-4 border-red-500 text-red-700 font-semibold text-center';
    }
    feedback.textContent = message;
    
    // Insert after the completed sentence
    const completedSentence = document.getElementById('completed-sentence');
    completedSentence.parentNode.insertBefore(feedback, completedSentence.nextSibling);
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        if (feedback.parentNode) {
            feedback.remove();
        }
    }, 3000);
}

// Update progress display
function updateProgressDisplay() {
    const progressText = document.getElementById('progress-display');
    if (progressText) {
        progressText.textContent = `Score: ${score}/${answeredQuestions} (${Math.round((score/answeredQuestions)*100)}%)`;
    }
}

// Navigation functions using event delegation
document.addEventListener('click', function(e) {
    if (e.target && e.target.id === 'prev-btn') {
        if (currentPromptIndex > 0) {
            currentPromptIndex--;
            loadPrompt(currentPromptIndex);
        }
    } else if (e.target && e.target.id === 'next-btn') {
        console.log('Next button clicked! Current index:', currentPromptIndex, 'Total prompts:', prompts.length);
        if (currentPromptIndex < prompts.length - 1) {
            currentPromptIndex++;
            console.log('Moving to next prompt, new index:', currentPromptIndex);
            loadPrompt(currentPromptIndex);
        } else {
            console.log('Already at last prompt');
        }
    } else if (e.target && e.target.id === 'finish-btn') {
        finishGame();
    }
});

document.getElementById('restart-btn').addEventListener('click', function() {
    restartGame();
});


// Audio recording variables
let mediaRecorder;
let recordedChunks = [];
let isRecording = false;
let recordedAudioBlob = null;

// Reset recording state when new word is selected
function resetRecordingState() {
    // Clear the recorded audio blob
    if (recordedAudioBlob) {
        recordedAudioBlob = null;
        console.log('Cleared previous recording');
    }
    
    // Disable and reset the play recording button
    const playRecordingBtn = document.getElementById('play-recording-btn');
    const recordingStatus = document.getElementById('recording-status');
    
    if (playRecordingBtn) {
        playRecordingBtn.disabled = true;
        playRecordingBtn.innerHTML = '<i class="fas fa-play"></i> Play';
    }
    
    if (recordingStatus) {
        recordingStatus.textContent = '';
    }
    
    // Reset recording button if it was in a recording state
    const recordBtn = document.getElementById('record-btn');
    if (recordBtn && isRecording) {
        recordBtn.innerHTML = '<i class="fas fa-microphone"></i> Record';
        recordBtn.classList.remove('recording');
        if (mediaRecorder && mediaRecorder.state === 'recording') {
            mediaRecorder.stop();
        }
        isRecording = false;
    }
}

// Initialize audio recording
async function initializeRecording() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        
        mediaRecorder.ondataavailable = function(event) {
            if (event.data.size > 0) {
                recordedChunks.push(event.data);
            }
        };
        
        mediaRecorder.onstop = function() {
            recordedAudioBlob = new Blob(recordedChunks, { type: 'audio/wav' });
            recordedChunks = [];
            
            // Enable play button
            document.getElementById('play-recording-btn').disabled = false;
            document.getElementById('recording-status').textContent = 'Recording saved!';
        };
        
        return true;
    } catch (error) {
        console.error('Error accessing microphone:', error);
        document.getElementById('recording-status').textContent = 'Microphone access denied';
        return false;
    }
}

// Toggle recording
async function toggleRecording() {
    const recordBtn = document.getElementById('record-btn');
    const statusDiv = document.getElementById('recording-status');
    
    if (!isRecording) {
        // Start recording
        if (!mediaRecorder) {
            const initialized = await initializeRecording();
            if (!initialized) return;
        }
        
        recordedChunks = [];
        mediaRecorder.start();
        isRecording = true;
        
        recordBtn.innerHTML = '<i class="fas fa-stop"></i> Stop';
        recordBtn.classList.add('recording');
        statusDiv.textContent = 'Recording...';
        
        // Disable play button while recording
        document.getElementById('play-recording-btn').disabled = true;
    } else {
        // Stop recording
        mediaRecorder.stop();
        isRecording = false;
        
        recordBtn.innerHTML = '<i class="fas fa-microphone"></i> Record';
        recordBtn.classList.remove('recording');
        statusDiv.textContent = 'Processing...';
    }
}

// Play model audio (pre-generated sentence audio)
function playModelAudio() {
    const statusDiv = document.getElementById('model-status');
    const playBtn = document.getElementById('play-model-btn');
    
    if (!window.currentSentenceAudioPath) {
        statusDiv.textContent = 'No audio available - audio may still be generating';
        console.log('No sentence audio path available');
        return;
    }
    
    const audio = new Audio(window.currentSentenceAudioPath);
    
    playBtn.disabled = true;
    playBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Playing...';
    statusDiv.textContent = 'Playing model audio...';
    
    audio.play();
    
    audio.onended = function() {
        playBtn.disabled = false;
        playBtn.innerHTML = '<i class="fas fa-play"></i> Play Example';
        statusDiv.textContent = '';
    };
    
    audio.onerror = function() {
        playBtn.disabled = false;
        playBtn.innerHTML = '<i class="fas fa-play"></i> Play Example';
        statusDiv.textContent = 'Error playing audio';
    };
}

// Play recorded audio
function playRecording() {
    if (!recordedAudioBlob) {
        document.getElementById('recording-status').textContent = 'No recording available';
        return;
    }
    
    const audioUrl = URL.createObjectURL(recordedAudioBlob);
    const audio = new Audio(audioUrl);
    
    const playBtn = document.getElementById('play-recording-btn');
    const statusDiv = document.getElementById('recording-status');
    
    playBtn.disabled = true;
    playBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Playing...';
    statusDiv.textContent = 'Playing your recording...';
    
    audio.play();
    
    audio.onended = function() {
        playBtn.disabled = false;
        playBtn.innerHTML = '<i class="fas fa-play"></i> Play';
        statusDiv.textContent = '';
        URL.revokeObjectURL(audioUrl);
    };
    
    audio.onerror = function() {
        playBtn.disabled = false;
        playBtn.innerHTML = '<i class="fas fa-play"></i> Play';
        statusDiv.textContent = 'Error playing recording';
        URL.revokeObjectURL(audioUrl);
    };
}

// Finish the game
function finishGame() {
    // Calculate final score
    const percentage = Math.round((score / totalQuestions) * 100);
    
    // Calculate duration
    const durationSeconds = activityStartTime ? Math.round((Date.now() - activityStartTime) / 1000) : 0;
    
    // Log completion event
    logActivityEvent('completed', {
        total_prompts: totalQuestions,
        answered_prompts: answeredQuestions,
        score: score,
        percentage: percentage,
        duration_seconds: durationSeconds,
    });
    
    // Update score display
    document.getElementById('score-display').textContent = `${score}/${totalQuestions}`;
    document.getElementById('score-percentage').textContent = `${percentage}%`;
    
    // Show results
    document.getElementById('prompt-container').classList.add('hidden');
    document.querySelector('.game-controls')?.classList.add('hidden');
    document.getElementById('game-results').classList.remove('hidden');
}

// Restart the game
function restartGame() {
    currentPromptIndex = 0;
    score = 0;
    answeredQuestions = 0;
    
    // Reset all prompts as unanswered
    prompts.forEach(prompt => {
        prompt.answered = false;
    });
    
    // Reset activity start time and log new start event
    activityStartTime = Date.now();
    logActivityEvent('started', {
        total_prompts: prompts.length,
        is_restart: true,
    });
    
    // Reset display
    document.getElementById('prompt-container').classList.remove('hidden');
    document.querySelector('.game-controls')?.classList.remove('hidden');
    document.getElementById('game-results').classList.add('hidden');
    
    // Reset progress display
    updateProgressDisplay();
    
    // Load first prompt
    loadPrompt(currentPromptIndex);
}

// Audio functions
function playPromptAudio(audioPath) {
    const audio = document.getElementById('prompt-audio');
    audio.src = audioPath;
    audio.play().catch(error => {
        console.error('Error playing prompt audio:', error);
    });
}

function playOptionAudio(event, audioPath) {
    event.stopPropagation(); // Prevent option selection
    const audio = document.getElementById('option-audio');
    audio.src = audioPath;
    audio.play().catch(error => {
        console.error('Error playing option audio:', error);
    });
}
</script>

<style>
.prompts-game-container {
    max-width: 800px;
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

.game-stats {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-bottom: 2rem;
    padding: 1rem;
    background: var(--color-gray-50);
    border-radius: var(--radius-md);
}

.stat {
    text-align: center;
}

.stat-label {
    display: block;
    font-size: 0.875rem;
    color: var(--color-text-muted);
    margin-bottom: 0.25rem;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--color-primary);
}

.stat-separator {
    margin: 0 0.25rem;
}

.prompt-container {
    background: white;
    border-radius: var(--radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-md);
    margin-bottom: 2rem;
}

.prompt-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.prompt-header h3 {
    margin: 0;
    color: var(--color-text);
    flex: 1;
}

.audio-btn {
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
}

.audio-btn:hover {
    background: var(--color-primary-dark);
    transform: scale(1.05);
}

.prompt-sentence {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--color-gray-50);
    border-radius: var(--radius-md);
    border-left: 4px solid var(--color-primary);
}

.prompt-sentence p {
    font-size: 1.25rem;
    margin: 0;
    line-height: 1.6;
}

.drop-zone {
    display: inline-block;
    min-width: 100px;
    padding: 0.5rem 1rem;
    border: 2px dashed var(--color-primary);
    border-radius: var(--radius-sm);
    background: var(--color-primary-bg);
    color: var(--color-primary);
    font-weight: 500;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
}

.drop-zone.drag-over {
    border-color: var(--color-success);
    background: var(--color-success-bg);
    color: var(--color-success);
    transform: scale(1.05);
}

.drop-zone.filled {
    border-color: var(--color-success);
    background: var(--color-success-bg);
    color: var(--color-success-dark);
    border-style: solid;
}

.prompt-options h4 {
    margin-bottom: 1rem;
    color: var(--color-text);
}

.options-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.option-card {
    background: white;
    border: 2px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.option-card:hover {
    border-color: var(--color-primary);
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.option-card.selected {
    border-color: var(--color-primary);
    background: var(--color-primary-bg);
}

.option-card.correct {
    border-color: var(--color-success);
    background: var(--color-success-bg);
}

.option-card.incorrect {
    border-color: var(--color-danger);
    background: var(--color-danger-bg);
}

.option-card.dragging {
    opacity: 0.5;
    transform: rotate(5deg);
}

.option-card[draggable="true"] {
    cursor: grab;
}

.option-card[draggable="true"]:active {
    cursor: grabbing;
}

.option-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.option-text {
    font-size: 1.1rem;
    font-weight: 500;
}

.option-audio-btn {
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
    transition: all 0.2s ease;
}

.option-audio-btn:hover {
    background: var(--color-info-dark);
}

.feedback {
    margin-top: 2rem;
    padding: 1.5rem;
    border-radius: var(--radius-md);
    background: var(--color-gray-50);
}

.feedback-message {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.feedback-message.correct {
    color: var(--color-success);
}

.feedback-message.incorrect {
    color: var(--color-danger);
}

/* Option card feedback styles */
.option-card.correct {
    background: #d4edda;
    border-color: #28a745;
    box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.25);
    transform: scale(1.02);
}

.option-card.incorrect {
    background: #f8d7da;
    border-color: #dc3545;
    box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.25);
    transform: scale(1.02);
}

.option-card.correct::after {
    content: "✓";
    position: absolute;
    top: 8px;
    right: 8px;
    color: #28a745;
    font-weight: bold;
    font-size: 1.2rem;
}

.option-card.incorrect::after {
    content: "✗";
    position: absolute;
    top: 8px;
    right: 8px;
    color: #dc3545;
    font-weight: bold;
    font-size: 1.2rem;
}

/* Progress display styles */
.progress-display {
    background: var(--color-primary);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: var(--radius-md);
    font-weight: 500;
    text-align: center;
    margin-top: 1rem;
}

/* Final score styles */
.final-score {
    background: var(--color-gray-50);
    border-radius: var(--radius-lg);
    padding: 2rem;
    margin: 2rem 0;
    text-align: center;
    border: 2px solid var(--color-primary);
}

.final-score h3 {
    color: var(--color-primary);
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
}

.final-score p {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--color-text);
    margin: 0;
}

.sentence-result {
    margin-top: 1.5rem;
    padding: 1rem;
    background: var(--color-gray-50);
    border-radius: var(--radius-md);
    border-left: 4px solid var(--color-success);
}

.completed-sentence {
    font-size: 1.25rem;
    font-weight: 500;
    color: var(--color-text);
    text-align: center;
}

.audio-controls {
    margin-top: 2rem;
    background: white;
    border-radius: var(--radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--color-border);
}

.audio-section h4 {
    margin: 0 0 1.5rem 0;
    color: var(--color-text);
    text-align: center;
}

.audio-split {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.audio-panel {
    background: var(--color-gray-50);
    border-radius: var(--radius-md);
    padding: 1.5rem;
    text-align: center;
}

.audio-panel h5 {
    margin: 0 0 1rem 0;
    color: var(--color-text);
    font-size: 1.1rem;
}

.audio-play-btn, .audio-record-btn {
    background: var(--color-primary);
    color: white;
    border: none;
    border-radius: var(--radius-md);
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    justify-content: center;
    margin: 0 auto 1rem auto;
    min-width: 120px;
}

.audio-play-btn:hover, .audio-record-btn:hover {
    background: var(--color-primary-dark);
    transform: translateY(-1px);
}

.audio-play-btn:disabled, .audio-record-btn:disabled {
    background: var(--color-gray-400);
    cursor: not-allowed;
    transform: none;
}

.audio-record-btn.recording {
    background: var(--color-danger);
    animation: pulse 1.5s infinite;
}

.audio-record-btn.recording:hover {
    background: var(--color-danger-dark);
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.recording-controls {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.audio-status {
    font-size: 0.875rem;
    color: var(--color-text-muted);
    min-height: 1.2rem;
    font-style: italic;
}

.model-audio {
    border-left: 4px solid var(--color-info);
}

.recording-audio {
    border-left: 4px solid var(--color-warning);
}

.game-controls {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 2rem;
}

.game-results {
    text-align: center;
    background: white;
    border-radius: var(--radius-lg);
    padding: 3rem 2rem;
    box-shadow: var(--shadow-md);
}

.results-header h2 {
    color: var(--color-success);
    margin-bottom: 0.5rem;
}

.results-stats {
    display: flex;
    justify-content: center;
    gap: 3rem;
    margin: 2rem 0;
}

.result-stat {
    text-align: center;
}

.result-number {
    display: block;
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--color-primary);
    margin-bottom: 0.5rem;
}

.result-label {
    font-size: 0.875rem;
    color: var(--color-text-muted);
    text-transform: uppercase;
    font-weight: 500;
}

.results-actions {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-top: 2rem;
}

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    background: white;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
}

.empty-state h3 {
    color: var(--color-text);
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .prompts-game-container {
        padding: 1rem;
    }
    
    .game-stats {
        flex-direction: column;
        gap: 1rem;
    }
    
    .options-grid {
        grid-template-columns: 1fr;
    }
    
    .audio-split {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .audio-controls {
        padding: 1rem;
    }
    
    .results-stats {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .results-actions {
        flex-direction: column;
    }
}
</style>
@endsection

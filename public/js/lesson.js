// Lesson Runner JavaScript - Part-Based Structure
let currentPartIndex = 0;
let currentPromptIndex = 0;
let currentPart = null;
let currentPrompt = null;
let currentOption = null;
let mediaRecorder = null;
let recordedChunks = [];
let recordedBlob = null;

const maxRecordingSeconds = 20;

// DOM Elements
const partInstructions = document.getElementById('part-instructions');
const promptStep = document.getElementById('prompt-step');
const modelStep = document.getElementById('model-step');
const completionStep = document.getElementById('completion-step');
const lessonComplete = document.getElementById('lesson-complete');
const progressFill = document.getElementById('progress-fill');

const partTitle = document.getElementById('part-title');
const partDescription = document.getElementById('part-description');
const startPartBtn = document.getElementById('start-part-btn');
const nextPartBtn = document.getElementById('next-part-btn');

const promptText = document.getElementById('prompt-text');
const optionGrid = document.getElementById('option-grid');
const promptAudioBtn = document.getElementById('prompt-audio-btn');
const promptAudio = document.getElementById('prompt-audio');

const modelText = document.getElementById('model-text');
const playBtn = document.getElementById('play-btn');
const modelAudio = document.getElementById('model-audio');

const recordBtn = document.getElementById('record-btn');
const stopBtn = document.getElementById('stop-btn');
const playbackBtn = document.getElementById('playback-btn');
const playbackAudio = document.getElementById('playback-audio');
const recordingStatus = document.getElementById('recording-status');
const nextBtn = document.getElementById('next-btn');

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    if (lessonData && lessonData.parts && lessonData.parts.length > 0) {
        loadPart(0);
    } else {
        alert('No parts available for this lesson.');
    }
});

// Load a part
function loadPart(partIndex) {
    if (partIndex >= lessonData.parts.length) {
        showLessonComplete();
        return;
    }

    currentPartIndex = partIndex;
    currentPart = lessonData.parts[partIndex];
    currentPromptIndex = 0;

    // Show part instructions
    showPartInstructions();
}

// Show part instructions screen
function showPartInstructions() {
    partTitle.textContent = currentPart.title;
    partDescription.textContent = currentPart.description || 'No description provided.';
    
    // Hide all other screens
    hideAllScreens();
    partInstructions.classList.remove('hidden');
    
    // Update progress
    updateProgress();
}

// Start the current part
function startPart() {
    if (currentPart.prompts && currentPart.prompts.length > 0) {
        loadPrompt(0);
    } else {
        alert('No prompts available for this part.');
    }
}

// Load a prompt within the current part
function loadPrompt(promptIndex) {
    if (promptIndex >= currentPart.prompts.length) {
        showPartComplete();
        return;
    }

    currentPromptIndex = promptIndex;
    currentPrompt = currentPart.prompts[promptIndex];

    // Show prompt step
    hideAllScreens();
    promptStep.classList.remove('hidden');
    
    renderPrompt();
    updateProgress();
}

// Render the current prompt
function renderPrompt() {
    promptText.textContent = currentPrompt.prompt_text;
    
    // Set up prompt audio button
    if (currentPrompt.prompt_audio_path) {
        promptAudio.src = currentPrompt.prompt_audio_path;
        promptAudioBtn.classList.remove('hidden');
    } else {
        promptAudioBtn.classList.add('hidden');
    }
    
    // Play prompt audio button
    promptAudioBtn.addEventListener('click', () => {
        promptAudio.currentTime = 0;
        promptAudio.play();
    });

    // Set up drag and drop sentence
    setupDragAndDropSentence();
    
    // Render draggable options
    optionGrid.innerHTML = '';
    currentPrompt.options.forEach(option => {
        const optionCard = document.createElement('div');
        optionCard.className = 'option-card';
        optionCard.dataset.optionId = option.id;
        optionCard.draggable = true;
        
        let contentHTML = '<div class="option-card-content">';
        
        // Display image or text based on option_type
        if (option.option_type === 'text' && option.option_text) {
            contentHTML += `<div class="option-card-text">${option.option_text}</div>`;
        } else if (option.image_path) {
            contentHTML += `<img src="${option.image_path}" alt="${option.label}" />`;
        }
        
        contentHTML += `<div class="option-card-label">${option.label}</div>`;
        
        // Add word audio button if available
        if (option.word_audio_path) {
            contentHTML += `
                <div class="option-card-audio">
                    <button onclick="playWordAudio(event, '${option.word_audio_path}')" title="Listen to word">
                        ▶
                    </button>
                </div>
            `;
        }
        
        contentHTML += '</div>';
        optionCard.innerHTML = contentHTML;
        
        // Set up drag events
        setupDragEvents(optionCard, option);
        optionGrid.appendChild(optionCard);
    });
}

// Set up drag and drop sentence
function setupDragAndDropSentence() {
    const sentenceText = document.getElementById('sentence-text');
    const dropZone = document.getElementById('drop-zone');
    const checkBtn = document.getElementById('check-answer-btn');
    const clearBtn = document.getElementById('clear-answer-btn');
    
    // Split the template into parts
    const template = currentPrompt.template;
    const parts = template.split('{{answer}}');
    
    // Display the sentence with the drop zone in the middle
    sentenceText.innerHTML = parts[0];
    dropZone.innerHTML = '<span class="drop-placeholder">Drop your answer here</span>';
    
    // Clear any previous state
    dropZone.classList.remove('has-answer', 'incorrect', 'correct');
    checkBtn.classList.add('hidden');
    clearBtn.classList.add('hidden');
    
    // Set up drop zone events
    dropZone.addEventListener('dragover', handleDragOver);
    dropZone.addEventListener('drop', handleDrop);
    dropZone.addEventListener('dragenter', handleDragEnter);
    dropZone.addEventListener('dragleave', handleDragLeave);
    
    // Set up control buttons
    checkBtn.addEventListener('click', checkAnswer);
    clearBtn.addEventListener('click', clearAnswer);
}

// Set up drag events for option cards
function setupDragEvents(card, option) {
    card.addEventListener('dragstart', (e) => {
        e.dataTransfer.setData('text/plain', JSON.stringify({
            id: option.id,
            label: option.label,
            option: option
        }));
        card.classList.add('dragging');
    });
    
    card.addEventListener('dragend', (e) => {
        card.classList.remove('dragging');
    });
}

// Handle drag over
function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
}

// Handle drag enter
function handleDragEnter(e) {
    e.preventDefault();
    e.target.closest('.drop-zone').classList.add('drag-over');
}

// Handle drag leave
function handleDragLeave(e) {
    e.target.closest('.drop-zone').classList.remove('drag-over');
}

// Handle drop
function handleDrop(e) {
    e.preventDefault();
    const dropZone = e.target.closest('.drop-zone');
    dropZone.classList.remove('drag-over');
    
    const data = JSON.parse(e.dataTransfer.getData('text/plain'));
    
    // Mark the dragged card as used
    const draggedCard = document.querySelector(`[data-option-id="${data.id}"]`);
    if (draggedCard) {
        draggedCard.classList.add('used');
    }
    
    // Display the dropped answer
    dropZone.innerHTML = `
        <div class="dropped-answer" data-option-id="${data.id}">
            ${data.label}
            <button onclick="removeAnswer()" class="btn-remove">×</button>
        </div>
    `;
    
    dropZone.classList.add('has-answer');
    
    // Show control buttons
    document.getElementById('check-answer-btn').classList.remove('hidden');
    document.getElementById('clear-answer-btn').classList.remove('hidden');
}

// Check answer
function checkAnswer() {
    const dropZone = document.getElementById('drop-zone');
    const droppedAnswer = dropZone.querySelector('.dropped-answer');
    
    if (!droppedAnswer) return;
    
    const optionId = parseInt(droppedAnswer.dataset.optionId);
    const correctOption = currentPrompt.options.find(opt => opt.id === optionId);
    
    if (correctOption) {
        // Correct answer
        droppedAnswer.classList.add('correct');
        dropZone.classList.add('correct');
        
        // Show model step after a delay
        setTimeout(() => {
            showModelStep(correctOption);
        }, 1000);
    } else {
        // Incorrect answer
        droppedAnswer.classList.add('incorrect');
        dropZone.classList.add('incorrect');
        
        // Reset after animation
        setTimeout(() => {
            dropZone.classList.remove('incorrect');
            droppedAnswer.classList.remove('incorrect');
        }, 1000);
    }
}

// Clear answer
function clearAnswer() {
    const dropZone = document.getElementById('drop-zone');
    const droppedAnswer = dropZone.querySelector('.dropped-answer');
    
    if (droppedAnswer) {
        const optionId = droppedAnswer.dataset.optionId;
        const card = document.querySelector(`[data-option-id="${optionId}"]`);
        if (card) {
            card.classList.remove('used');
        }
    }
    
    dropZone.innerHTML = '<span class="drop-placeholder">Drop your answer here</span>';
    dropZone.classList.remove('has-answer', 'correct', 'incorrect');
    
    document.getElementById('check-answer-btn').classList.add('hidden');
    document.getElementById('clear-answer-btn').classList.add('hidden');
}

// Remove answer (called from the × button)
function removeAnswer() {
    clearAnswer();
}

// Show model step
function showModelStep(option) {
    currentOption = option;
    
    // Hide prompt step and show model step
    hideAllScreens();
    document.getElementById('model-step').classList.remove('hidden');
    
    // Generate and display model sentence
    const modelSentence = currentPrompt.template.replace('{{answer}}', option.label);
    document.getElementById('model-text').textContent = modelSentence;
    
    // Set up model audio
    if (currentPrompt.assets && currentPrompt.assets.length > 0) {
        const asset = currentPrompt.assets.find(a => a.option_id === option.id);
        if (asset && asset.audio_path) {
            modelAudio.src = asset.audio_path;
            playBtn.classList.remove('hidden');
        } else {
            playBtn.classList.add('hidden');
        }
    } else {
        playBtn.classList.add('hidden');
    }
    
    // Set up play button
    playBtn.addEventListener('click', () => {
        modelAudio.currentTime = 0;
        modelAudio.play();
    });
}

// Legacy selectOption function - now handled by drag and drop
// This is kept for backward compatibility but not used in drag-and-drop mode

// Play word audio
let wordAudio = new Audio();

function playWordAudio(event, audioPath) {
    event.stopPropagation(); // Prevent card selection
    wordAudio.src = audioPath;
    wordAudio.play();
}

// Start recording
function startRecording() {
    navigator.mediaDevices.getUserMedia({ audio: true })
        .then(stream => {
            mediaRecorder = new MediaRecorder(stream);
            recordedChunks = [];
            
            mediaRecorder.ondataavailable = event => {
                if (event.data.size > 0) {
                    recordedChunks.push(event.data);
                }
            };
            
            mediaRecorder.onstop = () => {
                recordedBlob = new Blob(recordedChunks, { type: 'audio/webm' });
                const audioUrl = URL.createObjectURL(recordedBlob);
                playbackAudio.src = audioUrl;
                playbackBtn.classList.remove('hidden');
                nextBtn.classList.remove('hidden');
            };
            
            mediaRecorder.start();
            recordBtn.classList.add('hidden');
            stopBtn.classList.remove('hidden');
            recordingStatus.textContent = 'Recording...';
            
            // Auto-stop after max time
            setTimeout(() => {
                if (mediaRecorder && mediaRecorder.state === 'recording') {
                    stopRecording();
                }
            }, maxRecordingSeconds * 1000);
        })
        .catch(error => {
            console.error('Error accessing microphone:', error);
            alert('Microphone access denied. Please allow microphone access to record.');
        });
}

// Stop recording
function stopRecording() {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach(track => track.stop());
        
        recordBtn.classList.remove('hidden');
        stopBtn.classList.add('hidden');
        recordingStatus.textContent = 'Recording stopped.';
    }
}

// Playback recording
function playRecording() {
    if (playbackAudio.src) {
        playbackAudio.currentTime = 0;
        playbackAudio.play();
    }
}

// Submit response
function submitResponse() {
    if (!currentPrompt || !currentOption) return;
    
    const formData = new FormData();
    formData.append('prompt_id', currentPrompt.id);
    formData.append('option_id', currentOption.id);
    formData.append('_token', csrfToken);
    
    if (recordedBlob) {
        formData.append('audio', recordedBlob, 'recording.webm');
    }
    
    fetch('/responses', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Response submitted:', data);
        loadPrompt(currentPromptIndex + 1);
    })
    .catch(error => {
        console.error('Error submitting response:', error);
        loadPrompt(currentPromptIndex + 1);
    });
}

// Show part complete
function showPartComplete() {
    hideAllScreens();
    completionStep.classList.remove('hidden');
    
    // Show next part button if there are more parts
    if (currentPartIndex < lessonData.parts.length - 1) {
        nextPartBtn.classList.remove('hidden');
    } else {
        nextPartBtn.classList.add('hidden');
    }
}

// Show lesson complete
function showLessonComplete() {
    hideAllScreens();
    lessonComplete.classList.remove('hidden');
}

// Next part
function nextPart() {
    loadPart(currentPartIndex + 1);
}

// Update progress
function updateProgress() {
    const totalParts = lessonData.parts.length;
    const currentPartProgress = currentPart ? (currentPromptIndex / currentPart.prompts.length) : 0;
    const overallProgress = (currentPartIndex + currentPartProgress) / totalParts;
    progressFill.style.width = `${overallProgress * 100}%`;
}

// Hide all screens
function hideAllScreens() {
    partInstructions.classList.add('hidden');
    promptStep.classList.add('hidden');
    modelStep.classList.add('hidden');
    completionStep.classList.add('hidden');
    lessonComplete.classList.add('hidden');
}

// Event listeners
startPartBtn.addEventListener('click', startPart);
nextPartBtn.addEventListener('click', nextPart);
recordBtn.addEventListener('click', startRecording);
stopBtn.addEventListener('click', stopRecording);
playbackBtn.addEventListener('click', playRecording);
nextBtn.addEventListener('click', submitResponse);
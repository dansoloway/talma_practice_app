@extends('layouts.admin')

@section('title', 'Preview CSV Import - ' . $lesson->title)

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.lessons.prompts.import', $lesson) }}" class="back-link">&larr; Back to Import</a>
            <h1 class="page-title">Preview CSV Import</h1>
            <p class="page-subtitle">{{ $lesson->title }}</p>
        </div>
    </div>

    @if(!empty($validationErrors))
        <div class="card error-card">
            <div class="card-header">
                <h2 class="card-title text-danger">Import Errors</h2>
            </div>
            <div class="card-body">
                <p>The following errors were found in your CSV. Please fix them and try again:</p>
                <ul class="error-list">
                    @foreach($validationErrors as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <div class="form-actions">
                    <a href="{{ route('admin.lessons.prompts.import', $lesson) }}" class="btn btn-secondary">Fix CSV and Try Again</a>
                </div>
            </div>
        </div>
    @endif

    @if(!empty($previewData))
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Import Preview</h2>
                <div class="import-summary">
                    <span class="badge badge-primary">{{ count($previewData) }} prompts</span>
                    <span class="badge badge-secondary">{{ array_sum(array_map(function($item) { return count($item['options']); }, $previewData)) }} options</span>
                    <span class="badge badge-info">Mode: {{ ucfirst($importMode) }}</span>
                </div>
            </div>
            <div class="card-body">
                <p>Review the prompts below. When you confirm, we'll import them and automatically generate TTS audio for all options.</p>
                
                <div class="preview-list">
                    @foreach($previewData as $item)
                        <div class="preview-item">
                            <div class="preview-header">
                                <h4>{{ $item['prompt_text'] }}</h4>
                                <span class="row-number">Row {{ $item['row_number'] }}</span>
                            </div>
                            
                            <div class="preview-template">
                                <strong>Template:</strong> {{ $item['template'] }}
                            </div>
                            
                            <div class="preview-options">
                                <strong>Options & Generated Sentences:</strong>
                                <div class="options-grid">
                                    @foreach($item['options'] as $index => $option)
                                        <div class="option-preview">
                                            <div class="option-word">{{ $option }}</div>
                                            <div class="option-sentence">{{ $item['generated_sentences'][$index] }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if(empty($validationErrors))
                    <div class="preview-actions">
                        <form id="import-form">
                            @csrf
                            <input type="hidden" name="import_mode" value="{{ $importMode }}">
                            <button type="submit" class="btn btn-success btn-large" id="import-btn">
                                <span class="btn-content">
                                    <i class="fas fa-check"></i> Confirm Import & Generate TTS
                                </span>
                                <span class="btn-loading" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i> Importing...
                                </span>
                            </button>
                            <a href="{{ route('admin.lessons.prompts.import', $lesson) }}" class="btn btn-secondary" id="edit-btn">
                                <i class="fas fa-edit"></i> Edit CSV
                            </a>
                        </form>
                        
                        <!-- Progress Display -->
                        <div class="progress-container" id="progress-container" style="display: none;">
                            <h3>Generating TTS Audio</h3>
                            <div class="progress-section">
                                <h4>Word Audio</h4>
                                <div class="progress-bar">
                                    <div class="progress-fill" id="word-progress"></div>
                                </div>
                                <div class="progress-text" id="word-status">Waiting...</div>
                            </div>
                            <div class="progress-section">
                                <h4>Sentence Audio</h4>
                                <div class="progress-bar">
                                    <div class="progress-fill" id="sentence-progress"></div>
                                </div>
                                <div class="progress-text" id="sentence-status">Waiting...</div>
                            </div>
                            <div class="progress-actions" id="progress-actions" style="display: none;">
                                <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn btn-primary">Continue to Lesson</a>
                            </div>
                        </div>
                        
                        <div class="import-note">
                            <p><strong>What happens next:</strong></p>
                            <ul>
                                <li>{{ count($previewData) }} prompts will be created</li>
                                <li>{{ array_sum(array_map(function($item) { return count($item['options']); }, $previewData)) }} options will be created</li>
                                <li>TTS audio will be generated automatically for all options</li>
                                @if($importMode === 'replace')
                                    <li><strong>Warning:</strong> All existing prompts will be deleted first</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
.error-card {
    border-left: 4px solid var(--color-danger);
    margin-bottom: 2rem;
}

.import-summary {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-primary { background: var(--color-primary); color: white; }
.badge-secondary { background: var(--color-secondary); color: white; }
.badge-info { background: var(--color-info); color: white; }

.preview-list {
    margin: 2rem 0;
}

.preview-item {
    border: 1px solid var(--color-border);
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    background: var(--color-background);
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.preview-header h4 {
    margin: 0;
    color: var(--color-primary);
    font-size: 1.125rem;
}

.row-number {
    background: var(--color-gray-100);
    color: var(--color-text-muted);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
}

.preview-template {
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: var(--color-gray-50);
    border-radius: 4px;
    font-family: monospace;
}

.preview-options strong {
    display: block;
    margin-bottom: 0.75rem;
}

.options-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
}

.option-preview {
    padding: 0.75rem;
    border: 1px solid var(--color-border-light);
    border-radius: 4px;
    background: white;
}

.option-word {
    font-weight: 600;
    color: var(--color-primary);
    margin-bottom: 0.25rem;
}

.option-sentence {
    color: var(--color-text-muted);
    font-style: italic;
    font-size: 0.875rem;
}

.preview-actions {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--color-border);
}

.preview-actions form {
    display: flex;
    gap: 1rem;
    align-items: center;
    margin-bottom: 1.5rem;
}

.import-note {
    background: var(--color-info-bg);
    border: 1px solid var(--color-info-light);
    padding: 1rem;
    border-radius: 4px;
}

.import-note p {
    margin: 0 0 0.5rem 0;
    font-weight: 600;
}

.import-note ul {
    margin: 0;
}

.import-note li {
    margin-bottom: 0.25rem;
}

/* Progress Container */
.progress-container {
    background: white;
    border-radius: var(--radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-md);
    margin-top: 2rem;
}

.progress-container h3 {
    text-align: center;
    color: var(--color-primary);
    margin-bottom: 2rem;
}

.progress-section {
    margin-bottom: 2rem;
}

.progress-section h4 {
    margin-bottom: 0.5rem;
    color: var(--color-text);
    font-size: 1.1rem;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background: var(--color-gray-200);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--color-success), var(--color-success-light));
    width: 0%;
    transition: width 0.3s ease;
    border-radius: 10px;
}

.progress-text {
    font-size: 0.9rem;
    color: var(--color-text-muted);
    text-align: center;
}

.progress-actions {
    text-align: center;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--color-border);
}

@media (max-width: 768px) {
    .preview-header {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .options-grid {
        grid-template-columns: 1fr;
    }
    
    .preview-actions form {
        flex-direction: column;
        align-items: stretch;
    }
}

/* Loading Button States */
.btn-loading {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
}

.btn .fa-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Loading overlay for the entire preview */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    color: white;
    font-size: 1.25rem;
    font-weight: 600;
}

.loading-content {
    text-align: center;
    background: rgba(0, 0, 0, 0.8);
    padding: 2rem;
    border-radius: 8px;
}

.loading-content .fa-spinner {
    font-size: 2rem;
    margin-bottom: 1rem;
}
</style>
@endpush

@push('scripts')
<script>
document.getElementById('import-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Get the button and form elements
    const importBtn = document.getElementById('import-btn');
    const editBtn = document.getElementById('edit-btn');
    const btnContent = importBtn.querySelector('.btn-content');
    const btnLoading = importBtn.querySelector('.btn-loading');
    const progressContainer = document.getElementById('progress-container');
    
    // Show loading state
    btnContent.style.display = 'none';
    btnLoading.style.display = 'flex';
    
    // Disable buttons
    importBtn.disabled = true;
    editBtn.style.opacity = '0.5';
    editBtn.style.pointerEvents = 'none';
    
    // Start the import process
    const formData = new FormData(this);
    
    fetch('{{ route('admin.lessons.prompts.confirm-import', $lesson) }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide form and show progress
            document.querySelector('.preview-actions form').style.display = 'none';
            document.querySelector('.import-note').style.display = 'none';
            progressContainer.style.display = 'block';
            
            // Start TTS generation
            startTtsGeneration({{ $lesson->id }});
        } else {
            alert('Error: ' + (data.message || 'Import failed'));
            resetForm();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error importing prompts');
        resetForm();
    });
});

function resetForm() {
    const importBtn = document.getElementById('import-btn');
    const editBtn = document.getElementById('edit-btn');
    const btnContent = importBtn.querySelector('.btn-content');
    const btnLoading = importBtn.querySelector('.btn-loading');
    
    btnContent.style.display = 'flex';
    btnLoading.style.display = 'none';
    importBtn.disabled = false;
    editBtn.style.opacity = '1';
    editBtn.style.pointerEvents = 'auto';
}

function startTtsGeneration(lessonId) {
    let wordOffset = 0;
    let sentenceOffset = 0;
    
    // Start generating word TTS
    generateWordTts(lessonId, wordOffset);
}

function generateWordTts(lessonId, offset) {
    const wordStatus = document.getElementById('word-status');
    const wordProgress = document.getElementById('word-progress');
    
    wordStatus.textContent = 'Generating word audio...';
    
    fetch(`/admin/lessons/${lessonId}/prompts/generate-word-tts`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            batch_size: 5,
            offset: offset
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update progress
            const totalProcessed = offset + data.processed;
            const totalWords = totalProcessed + data.remaining;
            const percentage = totalWords > 0 ? (totalProcessed / totalWords) * 100 : 100;
            
            wordProgress.style.width = percentage + '%';
            wordStatus.textContent = `Generated ${totalProcessed} of ${totalWords} word audio files`;
            
            if (!data.completed && data.remaining > 0) {
                // Continue with next batch
                setTimeout(() => generateWordTts(lessonId, offset + data.processed), 1000);
            } else {
                // Word TTS complete, start sentence TTS
                wordStatus.textContent = 'Word audio complete!';
                generateSentenceTts(lessonId, 0);
            }
        } else {
            wordStatus.textContent = 'Error generating word audio';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        wordStatus.textContent = 'Error generating word audio';
    });
}

function generateSentenceTts(lessonId, offset) {
    const sentenceStatus = document.getElementById('sentence-status');
    const sentenceProgress = document.getElementById('sentence-progress');
    
    sentenceStatus.textContent = 'Generating sentence audio...';
    
    fetch(`/admin/lessons/${lessonId}/prompts/generate-sentence-tts`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            batch_size: 3,
            offset: offset
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update progress
            const totalProcessed = offset + data.processed;
            const totalSentences = totalProcessed + data.remaining;
            const percentage = totalSentences > 0 ? (totalProcessed / totalSentences) * 100 : 100;
            
            sentenceProgress.style.width = percentage + '%';
            sentenceStatus.textContent = `Generated ${totalProcessed} of ${totalSentences} sentence audio files`;
            
            if (!data.completed && data.remaining > 0) {
                // Continue with next batch
                setTimeout(() => generateSentenceTts(lessonId, offset + data.processed), 1000);
            } else {
                // All TTS complete
                sentenceStatus.textContent = 'Sentence audio complete!';
                document.getElementById('progress-actions').style.display = 'block';
            }
        } else {
            sentenceStatus.textContent = 'Error generating sentence audio';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        sentenceStatus.textContent = 'Error generating sentence audio';
    });
}
</script>
@endpush

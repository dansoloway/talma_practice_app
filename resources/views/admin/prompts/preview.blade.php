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

    @if(!empty($warningMessages))
        <div class="card" style="border-left:4px solid var(--color-warning); margin-bottom: 2rem;">
            <div class="card-header">
                <h2 class="card-title" style="color: var(--color-warning-dark);">Import Warnings</h2>
            </div>
            <div class="card-body">
                <p>The following rows will be skipped:</p>
                <ul class="error-list">
                    @foreach($warningMessages as $warn)
                        <li>{{ $warn }}</li>
                    @endforeach
                </ul>
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
                                @if(isset($item['correct_answer']) && $item['correct_answer'])
                                    <div class="correct-answer">
                                        <strong>Correct:</strong>
                                        Option {{ $item['correct_answer'] }}
                                        @php
                                            $correctIndex = $item['correct_answer'] - 1;
                                        @endphp
                                        @if(isset($item['options'][$correctIndex]))
                                            — "{{ $item['options'][$correctIndex] }}"
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if(empty($validationErrors))
                    <div class="preview-actions">
                        <form id="import-form" action="{{ route('admin.lessons.prompts.confirm-import', $lesson) }}" method="POST">
                            @csrf
                            <input type="hidden" name="import_mode" value="{{ $importMode }}">
                            <input type="hidden" id="generate_tts" name="generate_tts" value="0">
                            <button type="button" class="btn btn-success btn-large" id="import-and-tts-btn">
                                Confirm Import + Start TTS
                            </button>
                            <a href="{{ route('admin.lessons.prompts.import', $lesson) }}" class="btn btn-secondary" id="edit-btn">
                                <i class="fas fa-edit"></i> Edit CSV
                            </a>
                        </form>
                        
                        <!-- Progress Container -->
                        <div id="tts-progress-container" style="display: none; margin-top: 2rem;">
                            <div class="progress-section">
                                <h4>Word TTS Generation</h4>
                                <div id="word-tts-progress-text" style="margin-bottom: 0.5rem; color: #666;">Initializing...</div>
                                <div class="progress-bar">
                                    <div id="word-tts-progress-fill" class="progress-fill"></div>
                                </div>
                                <div class="progress-text">
                                    <span id="word-tts-processed">0</span> processed, <span id="word-tts-remaining">0</span> remaining
                                </div>
                            </div>
                            
                            <div class="progress-section">
                                <h4>Sentence TTS Generation</h4>
                                <div id="sentence-tts-progress-text" style="margin-bottom: 0.5rem; color: #666;">Waiting for word TTS...</div>
                                <div class="progress-bar">
                                    <div id="sentence-tts-progress-fill" class="progress-fill"></div>
                                </div>
                                <div class="progress-text">
                                    <span id="sentence-tts-processed">0</span> processed, <span id="sentence-tts-remaining">0</span> remaining
                                </div>
                            </div>
                        </div>
                        
                        <div class="import-note">
                            <p><strong>What happens next:</strong></p>
                            <ul>
                                <li>{{ count($previewData) }} prompts will be created</li>
                                <li>{{ array_sum(array_map(function($item) { return count($item['options']); }, $previewData)) }} options will be created</li>
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
document.addEventListener('DOMContentLoaded', function() {
    var importAndTtsBtn = document.getElementById('import-and-tts-btn');
    var importForm = document.getElementById('import-form');
    var generateTtsInput = document.getElementById('generate_tts');
    var progressContainer = document.getElementById('tts-progress-container');
    
    if (importAndTtsBtn && importForm && generateTtsInput) {
        importAndTtsBtn.addEventListener('click', async function() {
            generateTtsInput.value = '1';
            
            // Disable button
            importAndTtsBtn.disabled = true;
            importAndTtsBtn.textContent = '⏳ Importing...';
            
            // Show progress container
            if (progressContainer) {
                progressContainer.style.display = 'block';
            }
            
            try {
                // Create FormData
                const formData = new FormData(importForm);
                
                // Make AJAX request to import
                const response = await fetch(importForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                
                const data = await response.json();
                
                if (data.success && data.start_tts) {
                    // Import successful, now generate TTS
                    await generateTtsWithProgress(data.lesson_id, data.options_needing_word_tts, data.options_needing_sentence_tts);
                } else if (data.success) {
                    // Import successful but no TTS requested
                    setTimeout(function() {
                        window.location.href = data.redirect_url;
                    }, 1000);
                } else {
                    alert('Import failed: ' + (data.message || 'Unknown error'));
                    importAndTtsBtn.disabled = false;
                    importAndTtsBtn.textContent = 'Confirm Import + Start TTS';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred: ' + error.message);
                importAndTtsBtn.disabled = false;
                importAndTtsBtn.textContent = 'Confirm Import + Start TTS';
            }
        });
    }
    
    async function generateTtsWithProgress(lessonId, wordTtsCount, sentenceTtsCount) {
        const lessonIdParam = lessonId;
        
        // Update word TTS progress
        document.getElementById('word-tts-remaining').textContent = wordTtsCount;
        document.getElementById('word-tts-progress-text').textContent = 'Generating word TTS...';
        
        // Generate word TTS
        if (wordTtsCount > 0) {
            let wordProcessed = 0;
            let wordRemaining = wordTtsCount;
            
            while (wordRemaining > 0) {
                try {
                    const response = await fetch(`/admin/lessons/${lessonIdParam}/prompts/generate-word-tts`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ reset: wordProcessed === 0 })
                    });
                    
                    const data = await response.json();
                    
                    if (data.completed) {
                        wordProcessed = wordTtsCount;
                        wordRemaining = 0;
                    } else {
                        wordProcessed += data.processed || 0;
                        wordRemaining = data.remaining || 0;
                    }
                    
                    // Update progress
                    const wordProgress = (wordProcessed / wordTtsCount) * 100;
                    document.getElementById('word-tts-progress-fill').style.width = wordProgress + '%';
                    document.getElementById('word-tts-processed').textContent = wordProcessed;
                    document.getElementById('word-tts-remaining').textContent = wordRemaining;
                    
                    if (wordRemaining > 0) {
                        await new Promise(resolve => setTimeout(resolve, 500));
                    }
                } catch (error) {
                    console.error('Word TTS generation error:', error);
                    break;
                }
            }
        }
        
        // Update sentence TTS progress
        document.getElementById('sentence-tts-remaining').textContent = sentenceTtsCount;
        document.getElementById('sentence-tts-progress-text').textContent = 'Generating sentence TTS...';
        
        // Generate sentence TTS
        if (sentenceTtsCount > 0) {
            let sentenceProcessed = 0;
            let sentenceRemaining = sentenceTtsCount;
            
            while (sentenceRemaining > 0) {
                try {
                    const response = await fetch(`/admin/lessons/${lessonIdParam}/prompts/generate-sentence-tts`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ reset: sentenceProcessed === 0 })
                    });
                    
                    const data = await response.json();
                    
                    if (data.completed) {
                        sentenceProcessed = sentenceTtsCount;
                        sentenceRemaining = 0;
                    } else {
                        sentenceProcessed += data.processed || 0;
                        sentenceRemaining = data.remaining || 0;
                    }
                    
                    // Update progress
                    const sentenceProgress = (sentenceProcessed / sentenceTtsCount) * 100;
                    document.getElementById('sentence-tts-progress-fill').style.width = sentenceProgress + '%';
                    document.getElementById('sentence-tts-processed').textContent = sentenceProcessed;
                    document.getElementById('sentence-tts-remaining').textContent = sentenceRemaining;
                    
                    if (sentenceRemaining > 0) {
                        await new Promise(resolve => setTimeout(resolve, 500));
                    }
                } catch (error) {
                    console.error('Sentence TTS generation error:', error);
                    break;
                }
            }
        }
        
        // All done - redirect
        document.getElementById('word-tts-progress-text').textContent = 'Word TTS complete!';
        document.getElementById('sentence-tts-progress-text').textContent = 'Sentence TTS complete!';
        
        setTimeout(function() {
            window.location.href = '/admin/lessons/' + lessonIdParam + '/manage';
        }, 2000);
    }
});
</script>
@endpush

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
    if (importAndTtsBtn && importForm && generateTtsInput) {
        importAndTtsBtn.addEventListener('click', function() {
            generateTtsInput.value = '1';
            importForm.submit();
        });
    }
});
</script>
@endpush

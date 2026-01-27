@extends('layouts.admin')

@section('title', 'Create Prompt')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Create Prompt for {{ $lesson->title }}</h1>
        <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn">Cancel</a>
    </div>

    <!-- CSV Import Option -->
    <div class="card import-option">
        <div class="card-body">
            <div class="import-content">
                <div class="import-text">
                    <h3>Import Multiple Prompts</h3>
                    <p>Need to create multiple prompts at once? Upload a CSV file with all your prompts and we'll automatically generate TTS audio for the options.</p>
                </div>
                <div class="import-action">
                    <a href="{{ route('admin.lessons.prompts.import', $lesson) }}" class="btn btn-success">
                        <i class="fas fa-upload"></i> Import from CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Single Prompt Form -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Create Single Prompt</h2>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.lessons.prompts.store', $lesson) }}" method="POST" class="form">
        @csrf

        <div class="form-group">
            <label for="prompt_text">Prompt Text *</label>
            <input type="text" id="prompt_text" name="prompt_text" value="{{ old('prompt_text') }}" required class="form-control">
            <small>The question students will see (e.g., "What is your favorite color?")</small>
        </div>

        <div class="form-group">
            <label for="template">Template *</label>
            <input type="text" id="template" name="template" value="{{ old('template') }}" required class="form-control">
            <small>Use <code>{}</code> as placeholder for the answer (e.g., "My favorite color is {}.")</small>
        </div>

        <div class="form-group">
            <label for="tts_voice">TTS Voice</label>
            <input type="text" id="tts_voice" name="tts_voice" value="{{ old('tts_voice', 'default') }}" class="form-control">
            <small>ElevenLabs voice ID to use for this prompt</small>
        </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Prompt</button>
                <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn">Cancel</a>
            </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.import-option {
    margin-bottom: 2rem;
    border-left: 4px solid var(--color-success);
}

.import-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
}

.import-text h3 {
    margin: 0 0 0.5rem 0;
    color: var(--color-success);
    font-size: 1.25rem;
}

.import-text p {
    margin: 0;
    color: var(--color-text-muted);
    line-height: 1.5;
}

.import-action {
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .import-content {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
}
</style>
@endpush


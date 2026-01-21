@extends('layouts.admin')

@section('title', 'Create Clause Exercise')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Create Clause Exercise for: {{ $lesson->title }}</h1>
        <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn">Back to Lesson</a>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Generate Clause Exercise with AI</h2>
        </div>
        <div class="card-body">
            <p style="margin-bottom: 1.5rem;">
                AI will generate a paragraph exercise using vocabulary from this lesson and concepts from associated grammar sets.
                Students will fill in the blanks with the correct vocabulary words.
            </p>

            @if($lesson->vocabulary->count() === 0)
                <div class="alert alert-error">
                    <strong>No vocabulary available!</strong> This lesson needs vocabulary items before creating clause exercises.
                    <a href="{{ route('admin.lessons.vocabulary.index', $lesson) }}">Add vocabulary</a>
                </div>
            @elseif($grammarSets->count() === 0)
                <div class="alert alert-warning">
                    <strong>No grammar sets associated!</strong> For best results, associate a grammar set with this lesson first.
                    <a href="{{ route('admin.lessons.manage', $lesson) }}">Go back to associate a grammar set</a>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-error" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <strong>Error:</strong> {{ session('error') }}
                    </div>
                    <div style="display: flex; gap: 0.5rem; flex-shrink: 0;">
                        <button type="button" class="btn btn-primary btn-sm" onclick="retryWithModel('gpt-4o-mini')">
                            🔄 Retry
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="retryWithModel('gpt-4o')" title="Use gpt-4o (more expensive but higher quality)">
                            💎 Retry with Pricier Model
                        </button>
                    </div>
                </div>
            @endif

            <form action="{{ route('admin.lessons.clause-exercises.store', $lesson) }}" method="POST" class="form" id="main-form">
                @csrf

                <div class="form-group">
                    <label for="title">Exercise Title *</label>
                    <input type="text" id="title" name="title" 
                           value="{{ old('title', 'Fill in the Blanks: ' . ($grammarSets->first()?->title ?? 'Grammar Practice')) }}" 
                           required class="form-control">
                    <small>Give this exercise a descriptive title</small>
                </div>

                <div class="form-group">
                    <label for="topic">Topic (Optional)</label>
                    <input type="text" id="topic" name="topic" 
                           value="{{ old('topic') }}" 
                           class="form-control"
                           placeholder="e.g., 'Daily Routines', 'Weather', 'School Life'">
                    <small>If provided, the paragraph will be organized around this topic. Leave blank for general paragraph.</small>
                </div>

                <div class="form-group">
                    <label for="grammar_set_id">Grammar Set (Optional)</label>
                    <select id="grammar_set_id" name="grammar_set_id" class="form-control">
                        <option value="">Use all associated grammar sets</option>
                        @foreach($grammarSets as $set)
                            <option value="{{ $set->id }}" {{ old('grammar_set_id') == $set->id ? 'selected' : '' }}>
                                {{ $set->title }} ({{ $set->concepts_count }} concepts)
                            </option>
                        @endforeach
                    </select>
                    <small>Select a specific grammar set to focus on, or leave blank to use all associated sets</small>
                </div>


                <div class="form-group">
                    <div style="background: var(--color-gray-50); padding: 1rem; border-radius: var(--radius-md);">
                        <strong>Available Vocabulary:</strong> {{ $lesson->vocabulary->count() }} words
                        <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--color-text-muted);">
                            {{ $lesson->vocabulary->take(10)->pluck('english_word')->join(', ') }}{{ $lesson->vocabulary->count() > 10 ? '...' : '' }}
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>AI Model</label>
                    <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                        <label class="checkbox-label" style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="radio" name="model" value="gpt-4o-mini" {{ old('model', 'gpt-4o-mini') === 'gpt-4o-mini' ? 'checked' : '' }}>
                            <span>gpt-4o-mini (Default - Cost Effective)</span>
                        </label>
                        <label class="checkbox-label" style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="radio" name="model" value="gpt-4o" {{ old('model') === 'gpt-4o' ? 'checked' : '' }}>
                            <span>gpt-4o (Higher Quality - More Expensive)</span>
                        </label>
                    </div>
                    <small>Choose which OpenAI model to use for generation. gpt-4o is more expensive but produces higher quality results.</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="generate-btn">
                        <span id="btn-text">Generate Exercise with AI</span>
                        <span id="btn-spinner" style="display: none;">⏳ Generating...</span>
                    </button>
                    <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('main-form').addEventListener('submit', function(e) {
    const btn = document.getElementById('generate-btn');
    const btnText = document.getElementById('btn-text');
    const btnSpinner = document.getElementById('btn-spinner');
    
    btn.disabled = true;
    btnText.style.display = 'none';
    btnSpinner.style.display = 'inline';
});

function retryWithModel(model) {
    const form = document.getElementById('main-form');
    const modelInput = form.querySelector('input[name="model"][value="' + model + '"]');
    if (modelInput) {
        modelInput.checked = true;
    }
    
    const btn = form.querySelector('button[type="submit"]');
    const btnText = btn.querySelector('#btn-text');
    const btnSpinner = btn.querySelector('#btn-spinner');
    
    btn.disabled = true;
    if (btnText) btnText.style.display = 'none';
    if (btnSpinner) {
        btnSpinner.style.display = 'inline';
        btnSpinner.textContent = model === 'gpt-4o' ? '⏳ Generating with gpt-4o...' : '⏳ Generating...';
    }
    
    form.submit();
}
</script>
@endsection

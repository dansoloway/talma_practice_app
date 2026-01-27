@extends('layouts.admin')

@section('title', 'Create True/False Question')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.lessons.true-false-games.show', [$lesson, $trueFalseGame]) }}" class="back-link">&larr; Back to Game</a>
            <h1 class="page-title">Create True/False Question</h1>
            <p class="page-subtitle">{{ $trueFalseGame->title }} • {{ $lesson->title }}</p>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($vocabulary->isEmpty())
        <div class="alert alert-warning">
            <strong>No vocabulary available!</strong> This lesson needs vocabulary items before creating True/False questions.
            <a href="{{ route('admin.lessons.vocabulary.index', $lesson) }}">Add vocabulary</a>
        </div>
    @endif

    <form action="{{ route('admin.lessons.true-false-games.questions.store', [$lesson, $trueFalseGame]) }}" method="POST" class="form">
        @csrf

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Question Details</h2>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Difficulty Level</label>
                    <div class="form-control" style="background: #f8f9fa; padding: 0.75rem;">
                        <strong>{{ ucfirst($trueFalseGame->game_version) }}</strong>
                        <small class="form-text" style="margin-top: 0.25rem; display: block;">
                            @if($trueFalseGame->game_version === 'easy')
                                Direct meaning, no negation, no tricks
                            @elseif($trueFalseGame->game_version === 'medium')
                                One reasoning lever (usage context, simple contrast, or inference)
                            @else
                                Near-miss meanings, partial correctness, subtle details
                            @endif
                        </small>
                    </div>
                    <small class="form-text">Difficulty is set by the game. <a href="{{ route('admin.lessons.true-false-games.edit', [$lesson, $trueFalseGame]) }}">Change game difficulty</a></small>
                </div>

                <div class="form-group">
                    <label for="statement">Statement <span class="required">*</span></label>
                    <textarea id="statement" name="statement" rows="3" required class="form-control" 
                              placeholder="e.g., An oasis is a place with water in a desert.">{{ old('statement') }}</textarea>
                    <small class="form-text">The statement students will hear/read. Must test vocabulary understanding. Must NOT contain "?", "means", "a kind of", "a type of".</small>
                    @error('statement')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Correct Answer <span class="required">*</span></label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="is_true" value="1" {{ old('is_true', '1') == '1' ? 'checked' : '' }} required>
                            <span class="radio-custom"></span>
                            <span class="radio-text">True</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="is_true" value="0" {{ old('is_true') == '0' ? 'checked' : '' }} required>
                            <span class="radio-custom"></span>
                            <span class="radio-text">False</span>
                        </label>
                    </div>
                    <small class="form-text">TRUE means the statement correctly reflects vocabulary meaning/usage. FALSE means it incorrectly reflects vocabulary meaning/usage.</small>
                    @error('is_true')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="explanation">Explanation <span class="required">*</span></label>
                    <textarea id="explanation" name="explanation" rows="3" required class="form-control" 
                              placeholder="e.g., Yes! An oasis has water, which is why travelers can rest there.">{{ old('explanation') }}</textarea>
                    <small class="form-text">This explanation will be shown to students after they answer. Keep it friendly and educational (1-2 sentences).</small>
                    @error('explanation')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="vocabulary_ids">Vocabulary Words Being Tested <span class="required">*</span></label>
                    @if($vocabulary->isNotEmpty())
                        <div class="vocabulary-selection" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 1rem;">
                            @foreach($vocabulary as $vocab)
                                <label class="checkbox-label" style="display: block; padding: 0.5rem; margin-bottom: 0.25rem;">
                                    <input type="checkbox" name="vocabulary_ids[]" value="{{ $vocab->id }}" 
                                           {{ in_array($vocab->id, old('vocabulary_ids', $vocabulary->pluck('id')->toArray())) ? 'checked' : '' }}>
                                    <span class="checkmark"></span>
                                    <strong>{{ $vocab->english_word }}</strong>
                                    @if($vocab->hebrew_translation)
                                        <span class="text-muted">({{ $vocab->hebrew_translation }})</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                        <small class="form-text">Select at least one vocabulary word that this question tests.</small>
                    @else
                        <div class="alert alert-warning">No vocabulary available. <a href="{{ route('admin.lessons.vocabulary.index', $lesson) }}">Add vocabulary first</a>.</div>
                    @endif
                    @error('vocabulary_ids')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <input type="text" id="category" name="category" class="form-control" 
                           value="{{ old('category') }}" 
                           placeholder="e.g., oasis, vocabulary, meaning">
                    <small class="form-text">Optional: Use the vocabulary word being tested or a simple reasoning label.</small>
                    @error('category')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Settings</h2>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_approved" value="1" {{ old('is_approved', true) ? 'checked' : '' }}>
                        <span class="checkmark"></span>
                        Approved (ready for students)
                    </label>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <span class="checkmark"></span>
                        Active
                    </label>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Question</button>
            <a href="{{ route('admin.lessons.true-false-games.show', [$lesson, $trueFalseGame]) }}" class="btn">Cancel</a>
        </div>
    </form>
</div>

<style>
.required {
    color: #dc3545;
}

.radio-group {
    display: flex;
    gap: 2rem;
    margin-top: 0.5rem;
}

.radio-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-weight: normal;
}

.radio-label input[type="radio"] {
    margin-right: 0.5rem;
}

.card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.card-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #ddd;
    background: #f8f9fa;
}

.card-title {
    margin: 0;
    font-size: 1.25rem;
}

.card-body {
    padding: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
}

.form-control:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.form-text {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #666;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-weight: normal;
}

.checkbox-label input[type="checkbox"] {
    margin-right: 0.5rem;
}

.vocabulary-selection {
    background: #f8f9fa;
}

.vocabulary-selection label:hover {
    background: #e9ecef;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}
</style>
@endsection

@extends('layouts.admin')

@section('title', 'Edit True/False Question')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.lessons.true-false-questions.index', $lesson) }}" class="back-link">&larr; Back to Questions</a>
            <h1 class="page-title">Edit True/False Question</h1>
            <p class="page-subtitle">{{ $lesson->title }}</p>
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

    <form action="{{ route('admin.lessons.true-false-questions.update', [$lesson, $trueFalseQuestion]) }}" method="POST" class="form">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Question Details</h2>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="statement">Statement <span class="required">*</span></label>
                    <textarea id="statement" name="statement" rows="3" required class="form-control">{{ old('statement', $trueFalseQuestion->statement) }}</textarea>
                    <small class="form-text">The statement students will hear/read. Should be a complete sentence.</small>
                    @error('statement')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Correct Answer <span class="required">*</span></label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="is_true" value="1" {{ old('is_true', $trueFalseQuestion->is_true) == '1' ? 'checked' : '' }} required>
                            <span class="radio-custom"></span>
                            <span class="radio-text">True</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="is_true" value="0" {{ old('is_true', $trueFalseQuestion->is_true) == '0' ? 'checked' : '' }} required>
                            <span class="radio-custom"></span>
                            <span class="radio-text">False</span>
                        </label>
                    </div>
                    @error('is_true')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="explanation">Explanation <span class="required">*</span></label>
                    <textarea id="explanation" name="explanation" rows="3" required class="form-control">{{ old('explanation', $trueFalseQuestion->explanation) }}</textarea>
                    <small class="form-text">This explanation will be shown to students after they answer. Keep it friendly and educational (1-2 sentences).</small>
                    @error('explanation')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="grammar_set_id">Grammar Set (Optional)</label>
                    <select id="grammar_set_id" name="grammar_set_id" class="form-control">
                        <option value="">None</option>
                        @foreach($grammarSets as $set)
                            <option value="{{ $set->id }}" {{ old('grammar_set_id', $trueFalseQuestion->grammar_set_id) == $set->id ? 'selected' : '' }}>
                                {{ $set->title }} ({{ $set->grammarConcepts->count() }} concepts)
                            </option>
                        @endforeach
                    </select>
                    <small class="form-text">Optional: Link this question to a grammar set to test specific grammar concepts.</small>
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" class="form-control">
                        <option value="">None</option>
                        <option value="science_facts" {{ old('category', $trueFalseQuestion->category) == 'science_facts' ? 'selected' : '' }}>Science Facts</option>
                        <option value="procedures" {{ old('category', $trueFalseQuestion->category) == 'procedures' ? 'selected' : '' }}>Procedures</option>
                        <option value="vocabulary" {{ old('category', $trueFalseQuestion->category) == 'vocabulary' ? 'selected' : '' }}>Vocabulary</option>
                        <option value="process" {{ old('category', $trueFalseQuestion->category) == 'process' ? 'selected' : '' }}>Process</option>
                        <option value="misconception" {{ old('category', $trueFalseQuestion->category) == 'misconception' ? 'selected' : '' }}>Misconception</option>
                    </select>
                    <small class="form-text">Optional: Categorize the question type.</small>
                </div>

                @if($trueFalseQuestion->audio_path)
                    <div class="form-group">
                        <label>Audio</label>
                        <div class="audio-preview">
                            <audio controls>
                                <source src="{{ asset($trueFalseQuestion->audio_path) }}" type="audio/mpeg">
                            </audio>
                        </div>
                        <small class="form-text">Audio file: {{ $trueFalseQuestion->audio_path }}</small>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Settings</h2>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_approved" value="1" {{ old('is_approved', $trueFalseQuestion->is_approved) ? 'checked' : '' }}>
                        <span class="checkmark"></span>
                        Approved (ready for students)
                    </label>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $trueFalseQuestion->is_active) ? 'checked' : '' }}>
                        <span class="checkmark"></span>
                        Active
                    </label>
                </div>

                <div class="form-group">
                    <label for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $trueFalseQuestion->sort_order) }}" min="0" class="form-control">
                    <small class="form-text">Lower numbers appear first in the game.</small>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Question</button>
            <a href="{{ route('admin.lessons.true-false-questions.index', $lesson) }}" class="btn">Cancel</a>
            <form action="{{ route('admin.lessons.true-false-questions.destroy', [$lesson, $trueFalseQuestion]) }}" method="POST" class="inline-form">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this question?')">Delete</button>
            </form>
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

.audio-preview {
    margin-top: 0.5rem;
}

.audio-preview audio {
    width: 100%;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    align-items: center;
}

.inline-form {
    display: inline-block;
}
</style>
@endsection


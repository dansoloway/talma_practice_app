@extends('layouts.admin')

@section('title', 'Create True/False Question')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.lessons.true-false-questions.index', $lesson) }}" class="back-link">&larr; Back to Questions</a>
            <h1 class="page-title">Create True/False Question</h1>
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

    <form action="{{ route('admin.lessons.true-false-questions.store', $lesson) }}" method="POST" class="form">
        @csrf

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Question Details</h2>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="statement">Statement <span class="required">*</span></label>
                    <textarea id="statement" name="statement" rows="3" required class="form-control" 
                              placeholder="e.g., Ice melts faster in warm water than cold water">{{ old('statement') }}</textarea>
                    <small class="form-text">The statement students will hear/read. Should be a complete sentence.</small>
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
                    @error('is_true')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="explanation">Explanation <span class="required">*</span></label>
                    <textarea id="explanation" name="explanation" rows="3" required class="form-control" 
                              placeholder="e.g., Yes! Warm water has more energy, so ice melts faster in it.">{{ old('explanation') }}</textarea>
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
                            <option value="{{ $set->id }}" {{ old('grammar_set_id') == $set->id ? 'selected' : '' }}>
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
                        <option value="science_facts" {{ old('category') == 'science_facts' ? 'selected' : '' }}>Science Facts</option>
                        <option value="procedures" {{ old('category') == 'procedures' ? 'selected' : '' }}>Procedures</option>
                        <option value="vocabulary" {{ old('category') == 'vocabulary' ? 'selected' : '' }}>Vocabulary</option>
                        <option value="process" {{ old('category') == 'process' ? 'selected' : '' }}>Process</option>
                        <option value="misconception" {{ old('category') == 'misconception' ? 'selected' : '' }}>Misconception</option>
                    </select>
                    <small class="form-text">Optional: Categorize the question type.</small>
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

                <div class="form-group">
                    <label for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $lesson->trueFalseQuestions()->max('sort_order') + 1) }}" min="0" class="form-control">
                    <small class="form-text">Lower numbers appear first in the game.</small>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Question</button>
            <a href="{{ route('admin.lessons.true-false-questions.index', $lesson) }}" class="btn">Cancel</a>
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

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}
</style>
@endsection


@extends('layouts.admin')

@section('title', 'Edit Spelling Game - ' . $lesson->title)

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Edit Spelling Game</h1>
        <div class="page-actions">
            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn">← Back to Lesson</a>
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

    <form action="{{ route('admin.lessons.spelling-games.update', [$lesson, $spellingGame]) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" class="form-control" 
                   value="{{ old('title', $spellingGame->title) }}" required>
        </div>

        <div class="form-group">
            <label for="difficulty">Difficulty Level</label>
            <select id="difficulty" name="difficulty" class="form-control" required>
                <option value="easy" {{ old('difficulty', $spellingGame->difficulty) == 'easy' ? 'selected' : '' }}>Easy (shows first letter)</option>
                <option value="medium" {{ old('difficulty', $spellingGame->difficulty) == 'medium' ? 'selected' : '' }}>Medium (shows some letters)</option>
                <option value="hard" {{ old('difficulty', $spellingGame->difficulty) == 'hard' ? 'selected' : '' }}>Hard (no hints)</option>
            </select>
            <small class="form-text">
                <strong>Easy:</strong> Shows first letter (e.g., "C___")<br>
                <strong>Medium:</strong> Shows some letters (e.g., "C_T")<br>
                <strong>Hard:</strong> No hints, full spelling required
            </small>
        </div>

        <div class="form-group">
            <label>Select Vocabulary Words</label>
            <div class="vocabulary-selection">
                @if($vocabulary->count() > 0)
                    <div class="vocab-grid">
                        @foreach($vocabulary as $vocab)
                            <div class="vocab-item">
                                <label class="vocab-checkbox">
                                    <input type="checkbox" name="vocabulary_ids[]" value="{{ $vocab->id }}" 
                                           {{ in_array($vocab->id, old('vocabulary_ids', $spellingGame->vocabulary_ids ?? [])) ? 'checked' : '' }}
                                           onchange="updateSelectionCount()">
                                    <div class="vocab-card">
                                        @if($vocab->image_path)
                                            <img src="{{ asset('storage/' . $vocab->image_path) }}" alt="{{ $vocab->english_word }}" class="vocab-image">
                                        @else
                                            <div class="vocab-placeholder">📝</div>
                                        @endif
                                        <div class="vocab-word">{{ $vocab->english_word }}</div>
                                        @if($vocab->word_audio_path)
                                            <div class="vocab-audio-indicator">🔊</div>
                                        @endif
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="selection-info">
                        <span id="selection-count">{{ count(old('vocabulary_ids', $spellingGame->vocabulary_ids ?? [])) }}</span> words selected
                    </div>
                @else
                    <div class="empty-state">
                        <p>No vocabulary items available. <a href="{{ route('admin.lessons.vocabulary.index', $lesson) }}">Add vocabulary first</a>.</p>
                    </div>
                @endif
            </div>
            @error('vocabulary_ids')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Spelling Game</button>
            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn">Cancel</a>
        </div>
    </form>
</div>

<script>
function updateSelectionCount() {
    const checked = document.querySelectorAll('input[name="vocabulary_ids[]"]:checked').length;
    document.getElementById('selection-count').textContent = checked;
}
</script>

<style>
.vocabulary-selection {
    margin-top: 1rem;
}

.vocab-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.vocab-item {
    position: relative;
}

.vocab-checkbox {
    display: block;
    cursor: pointer;
    margin: 0;
}

.vocab-checkbox input[type="checkbox"] {
    position: absolute;
    top: 0.5rem;
    left: 0.5rem;
    z-index: 2;
    width: 1.5rem;
    height: 1.5rem;
    cursor: pointer;
}

.vocab-card {
    border: 2px solid var(--color-border);
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
    transition: all 0.2s ease;
    background: white;
    position: relative;
}

.vocab-checkbox input[type="checkbox"]:checked + .vocab-card {
    border-color: var(--color-primary);
    background: #e7f3ff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.vocab-image {
    width: 100%;
    max-height: 100px;
    object-fit: contain;
    border-radius: 4px;
    margin-bottom: 0.5rem;
}

.vocab-placeholder {
    font-size: 3rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

.vocab-word {
    font-weight: 600;
    color: var(--color-text);
    font-size: 0.9rem;
}

.vocab-audio-indicator {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    font-size: 0.8rem;
}

.selection-info {
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 4px;
    text-align: center;
    font-weight: 600;
    color: var(--color-text);
}

.selection-info #selection-count {
    color: var(--color-primary);
    font-size: 1.2rem;
}
</style>
@endsection


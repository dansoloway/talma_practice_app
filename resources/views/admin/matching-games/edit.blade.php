@extends('layouts.admin')

@section('title', 'Edit Matching Game')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="back-link">&larr; Back to Lesson</a>
            <h1 class="page-title">Edit Matching Game</h1>
            <p class="page-subtitle">{{ $lesson->title }}</p>
        </div>
    </div>

    <form action="{{ route('admin.lessons.matching-games.update', [$lesson, $matchingGame]) }}" method="POST" class="form">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Game Settings</h2>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="title">Game Title</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $matchingGame->title) }}" required class="form-control">
                    @error('title')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="grid_size">Grid Size</label>
                    <select id="grid_size" name="grid_size" required class="form-control">
                        <option value="">Select Grid Size</option>
                        <option value="4" {{ old('grid_size', $matchingGame->grid_size) == 4 ? 'selected' : '' }}>4x4 (8 pairs)</option>
                        <option value="6" {{ old('grid_size', $matchingGame->grid_size) == 6 ? 'selected' : '' }}>6x6 (18 pairs)</option>
                        <option value="8" {{ old('grid_size', $matchingGame->grid_size) == 8 ? 'selected' : '' }}>8x8 (32 pairs)</option>
                    </select>
                    @error('grid_size')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" id="is_active" name="is_active" value="1" 
                               {{ old('is_active', $matchingGame->is_active) ? 'checked' : '' }} class="form-check-input">
                        <label for="is_active" class="form-check-label">Active</label>
                    </div>
                    <small class="form-text">Only active games are shown to students</small>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Vocabulary Selection</h2>
            </div>
            <div class="card-body">
                @if($vocabulary->count() > 0)
                    <p class="form-text">Select vocabulary words to include in this matching game:</p>
                    <div class="vocabulary-selection">
                        @foreach($vocabulary as $vocab)
                            <div class="vocab-checkbox">
                                <div class="form-check">
                                    <input type="checkbox" 
                                           name="vocabulary_ids[]" 
                                           value="{{ $vocab->id }}" 
                                           id="vocab_{{ $vocab->id }}"
                                           {{ in_array($vocab->id, old('vocabulary_ids', $matchingGame->vocabulary_ids)) ? 'checked' : '' }}
                                           class="form-check-input">
                                    <label for="vocab_{{ $vocab->id }}" class="form-check-label vocab-label">
                                        @if($vocab->image_path)
                                            <img src="{{ asset('storage/' . $vocab->image_path) }}" alt="{{ $vocab->english_word }}" class="vocab-thumb">
                                        @endif
                                        <span class="vocab-word">{{ $vocab->english_word }}</span>
                                        <div class="vocab-indicators">
                                            @if($vocab->image_path)
                                                <i class="fas fa-image text-success" title="Has image"></i>
                                            @endif
                                            @if($vocab->word_audio_path)
                                                <i class="fas fa-volume-up text-primary" title="Has audio"></i>
                                            @endif
                                        </div>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('vocabulary_ids')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                @else
                    <div class="alert alert-warning">
                        <p>No vocabulary words available for this lesson.</p>
                        <a href="{{ route('admin.lessons.vocabulary.create', $lesson) }}" class="btn btn-primary btn-sm">Add Vocabulary</a>
                    </div>
                @endif
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Matching Game</button>
            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
.vocabulary-selection {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: var(--spacing-md);
    margin-top: var(--spacing-md);
}

.vocab-checkbox {
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: var(--spacing-md);
    transition: var(--transition-fast);
}

.vocab-checkbox:hover {
    border-color: var(--color-primary);
    background: var(--color-primary-bg);
}

.vocab-label {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    cursor: pointer;
    margin: 0;
}

.vocab-thumb {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: var(--radius-sm);
    flex-shrink: 0;
}

.vocab-word {
    flex: 1;
    font-weight: 500;
}

.vocab-indicators {
    display: flex;
    gap: var(--spacing-xs);
}

.vocab-indicators i {
    font-size: 0.875rem;
}

.page-subtitle {
    color: var(--color-gray-600);
    font-size: 1rem;
    margin: 0;
}

.alert {
    padding: var(--spacing-lg);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-lg);
}

.alert-warning {
    background: var(--color-warning-bg);
    border: 1px solid var(--color-warning-light);
    color: var(--color-warning-dark);
}
</style>
@endpush

@extends('layouts.admin')

@section('title', 'Create Matching Game')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Create Matching Game</h1>
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

    <form action="{{ route('admin.lessons.matching-games.store', $lesson) }}" method="POST">
        @csrf
        
        <input type="hidden" name="title" value="{{ old('title', trim($lesson->title . ' Matching Game ' . ($lesson->matchingGames()->count() + 1))) }}">

        <div class="form-group">
            <label for="grid_size">Grid Size</label>
            <select id="grid_size" name="grid_size" class="form-control" onchange="updateVocabularyRequirement()">
                <option value="4" {{ old('grid_size', 4) == 4 ? 'selected' : '' }}>4x4 (8 pairs)</option>
                <option value="6" {{ old('grid_size') == 6 ? 'selected' : '' }}>6x6 (18 pairs)</option>
                <option value="8" {{ old('grid_size') == 8 ? 'selected' : '' }}>8x8 (32 pairs)</option>
            </select>
            <small id="grid-info">You need 8 vocabulary items for a 4x4 grid</small>
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
                                           {{ in_array($vocab->id, old('vocabulary_ids', $vocabulary->pluck('id')->toArray())) ? 'checked' : '' }}
                                           onchange="updateSelectionCount()">
                                    <div class="vocab-card">
                                        @if($vocab->image_path)
                                            <img src="{{ asset('storage/' . $vocab->image_path) }}" alt="{{ $vocab->english_word }}" class="vocab-image">
                                        @else
                                            <div class="vocab-placeholder">📝</div>
                                        @endif
                                        <div class="vocab-word">{{ $vocab->english_word }}</div>
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="selection-info">
                        <span id="selection-count">0</span> words selected (need <span id="required-count">8</span>)
                    </div>
                @else
                    <div class="empty-state">
                        <p>No vocabulary items available. <a href="{{ route('admin.lessons.vocabulary.index', $lesson) }}">Add vocabulary first</a>.</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                <span class="checkmark"></span>
                Active (students can play this game)
            </label>
        </div>

        <div class="form-group">
            <label for="sort_order">Sort Order</label>
            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-control" min="0">
            <small>Lower numbers appear first</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Matching Game</button>
            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn">Cancel</a>
        </div>
    </form>
</div>

<style>
.vocabulary-selection {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 1rem;
    background: #f9f9f9;
}

.vocab-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
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
    opacity: 0;
    pointer-events: none;
}

.vocab-card {
    border: 2px solid #ddd;
    border-radius: 8px;
    padding: 0.75rem;
    text-align: center;
    background: white;
    transition: all 0.2s;
    min-height: 100px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.vocab-checkbox input[type="checkbox"]:checked + .vocab-card {
    border-color: var(--color-primary);
    background: var(--color-primary-light);
}

.vocab-image {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
    margin: 0 auto 0.5rem;
}

.vocab-placeholder {
    width: 40px;
    height: 40px;
    background: #f0f0f0;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 0.5rem;
}

.vocab-word {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-text);
}

.selection-info {
    padding: 0.75rem;
    background: white;
    border-radius: 4px;
    border: 1px solid #ddd;
    font-weight: 500;
}

.selection-info span {
    color: var(--color-primary);
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: var(--color-text-light);
}
</style>

<script>
function updateVocabularyRequirement() {
    const gridSize = document.getElementById('grid_size').value;
    const requiredPairs = (gridSize * gridSize) / 2;
    document.getElementById('required-count').textContent = requiredPairs;
    document.getElementById('grid-info').textContent = `You need ${requiredPairs} vocabulary items for a ${gridSize}x${gridSize} grid`;
    updateSelectionCount();
}

function updateSelectionCount() {
    const checkboxes = document.querySelectorAll('input[name="vocabulary_ids[]"]:checked');
    const count = checkboxes.length;
    const required = parseInt(document.getElementById('required-count').textContent);
    
    document.getElementById('selection-count').textContent = count;
    
    if (count < required) {
        document.getElementById('selection-count').style.color = 'var(--color-danger)';
    } else {
        document.getElementById('selection-count').style.color = 'var(--color-success)';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateSelectionCount();
});
</script>
@endsection

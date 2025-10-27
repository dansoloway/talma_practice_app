@extends('layouts.admin')

@section('title', 'Matching Game: ' . $matchingGame->title)

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="back-link">&larr; Back to Lesson</a>
            <h1 class="page-title">{{ $matchingGame->title }}</h1>
            <p class="page-subtitle">{{ $lesson->title }}</p>
        </div>
        <div>
            <a href="{{ route('admin.lessons.matching-games.edit', [$lesson, $matchingGame]) }}" class="btn btn-primary">Edit Game</a>
            <a href="{{ route('matching-games.play', [$lesson, $matchingGame]) }}" class="btn btn-success" target="_blank">Play Game</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Game Details</h2>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <label>Grid Size:</label>
                    <span>{{ $matchingGame->grid_size }}x{{ $matchingGame->grid_size }}</span>
                </div>
                <div class="info-item">
                    <label>Status:</label>
                    <span class="status {{ $matchingGame->is_active ? 'active' : 'inactive' }}">
                        {{ $matchingGame->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <div class="info-item">
                    <label>Sort Order:</label>
                    <span>{{ $matchingGame->sort_order }}</span>
                </div>
                <div class="info-item">
                    <label>Vocabulary Words:</label>
                    <span>{{ count($matchingGame->vocabulary_ids) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Vocabulary Used</h2>
        </div>
        <div class="card-body">
            @if(count($matchingGame->vocabulary_ids) > 0)
                @php
                    $vocabulary = $lesson->vocabulary->whereIn('id', $matchingGame->vocabulary_ids);
                @endphp
                <div class="vocabulary-preview">
                    @foreach($vocabulary as $vocab)
                        <div class="vocab-item">
                            @if($vocab->image_path)
                                <img src="{{ asset('storage/' . $vocab->image_path) }}" alt="{{ $vocab->english_word }}" class="vocab-thumb">
                            @endif
                            <span class="vocab-word">{{ $vocab->english_word }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted">No vocabulary selected for this game.</p>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-lg);
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xs);
}

.info-item label {
    font-weight: 600;
    color: var(--color-gray-600);
    font-size: 0.875rem;
}

.info-item span {
    font-size: 1rem;
    color: var(--color-gray-900);
}

.status.active {
    color: var(--color-success);
    font-weight: 600;
}

.status.inactive {
    color: var(--color-danger);
    font-weight: 600;
}

.vocabulary-preview {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: var(--spacing-md);
}

.vocab-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: var(--spacing-md);
    background: var(--color-gray-50);
    border-radius: var(--radius-md);
    text-align: center;
}

.vocab-thumb {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: var(--radius-sm);
    margin-bottom: var(--spacing-sm);
}

.vocab-word {
    font-weight: 500;
    color: var(--color-gray-900);
}

.page-subtitle {
    color: var(--color-gray-600);
    font-size: 1rem;
    margin: 0;
}
</style>
@endpush

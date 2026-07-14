@extends('layouts.admin')

@section('title', 'Spelling Game: ' . $spellingGame->title)

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">{{ $spellingGame->title }}</h1>
        <div class="page-actions">
            <a href="{{ route('admin.lessons.spelling-games.index', $lesson) }}" class="btn">← Back to Spelling Games</a>
            <a href="{{ route('admin.lessons.spelling-games.edit', [$lesson, $spellingGame]) }}" class="btn btn-primary">Edit Game</a>
            <a href="{{ route('spelling-games.play', [$lesson, $spellingGame]) }}" class="btn btn-success" target="_blank">Play Game</a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Game Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <strong>Title:</strong>
                                <p class="mb-0">{{ $spellingGame->title }}</p>
                            </div>
                            <div class="mb-3">
                                <strong>Difficulty:</strong>
                                <p class="mb-0">
                                    <span class="badge badge-{{ $spellingGame->difficulty === 'easy' ? 'success' : ($spellingGame->difficulty === 'medium' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($spellingGame->difficulty) }}
                                    </span>
                                </p>
                            </div>
                            <div class="mb-3">
                                <strong>Vocabulary Words:</strong>
                                <p class="mb-0">{{ count($spellingGame->vocabulary_ids ?? []) }} words</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <strong>Status:</strong>
                                <p class="mb-0">
                                    <span class="status {{ $spellingGame->is_active ? 'active' : 'inactive' }}">
                                        {{ $spellingGame->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </p>
                            </div>
                            <div class="mb-3">
                                <strong>Sort Order:</strong>
                                <p class="mb-0">{{ $spellingGame->sort_order }}</p>
                            </div>
                            <div class="mb-3">
                                <strong>Created:</strong>
                                <p class="mb-0">{{ $spellingGame->created_at->format('M d, Y') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong>Difficulty Explanation:</strong>
                        <div class="mt-2">
                            @if($spellingGame->difficulty === 'easy')
                                <p class="mb-0"><strong>Easy:</strong> Shows first letter (e.g., "C___" for "CAT")</p>
                            @elseif($spellingGame->difficulty === 'medium')
                                <p class="mb-0"><strong>Medium:</strong> Shows some letters (e.g., "C_T" for "CAT")</p>
                            @else
                                <p class="mb-0"><strong>Hard:</strong> No hints, full spelling required</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Vocabulary Words</h5>
                </div>
                <div class="card-body">
                    @if($vocabulary->count() > 0)
                        <div class="vocab-list">
                            @foreach($vocabulary as $vocab)
                                <div class="vocab-item-card">
                                    @if($vocab->image_path)
                                        <img src="{{ asset('storage/' . $vocab->image_path) }}" 
                                             alt="{{ $vocab->english_word }}" 
                                             class="vocab-image-small">
                                    @endif
                                    <div class="vocab-word">{{ $vocab->english_word }}</div>
                                    @if($vocab->word_audio_path)
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary talma-audio-btn"
                                                data-audio-url="{{ $vocab->word_audio_url ?? asset('storage/' . $vocab->word_audio_path) }}"
                                                data-talma-audio-icon="volume-up"
                                                data-talma-audio-repeatable
                                                title="Play audio">
                                            <i class="fas fa-volume-up talma-audio-icon"></i>
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">No vocabulary words assigned to this game.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Game Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.lessons.spelling-games.edit', [$lesson, $spellingGame]) }}" 
                           class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Game
                        </a>
                        <a href="{{ route('spelling-games.play', [$lesson, $spellingGame]) }}" 
                           class="btn btn-success" target="_blank">
                            <i class="fas fa-play"></i> Play Game
                        </a>
                        <a href="{{ route('admin.lessons.spelling-games.index', $lesson) }}" 
                           class="btn btn-secondary">
                            <i class="fas fa-list"></i> All Spelling Games
                        </a>
                        <form action="{{ route('admin.lessons.spelling-games.destroy', [$lesson, $spellingGame]) }}" 
                              method="POST" 
                              onsubmit="return confirm('Are you sure you want to delete this spelling game?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="fas fa-trash"></i> Delete Game
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.vocab-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.vocab-item-card {
    border: 1px solid var(--color-border);
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
    background: white;
}

.vocab-image-small {
    width: 100%;
    max-height: 80px;
    object-fit: contain;
    border-radius: 4px;
    margin-bottom: 0.5rem;
}

.vocab-word {
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 0.5rem;
}
</style>
@endsection


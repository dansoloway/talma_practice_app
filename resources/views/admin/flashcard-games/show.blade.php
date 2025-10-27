@extends('layouts.admin')

@section('title', 'Flashcard Game: ' . $flashcardGame->title)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">{{ $flashcardGame->title }}</h1>
                    <p class="text-muted mb-0">{{ $lesson->title }}</p>
                </div>
                <div>
                    <a href="{{ route('admin.lessons.flashcard-games.index', $lesson) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Flashcard Games
                    </a>
                    <a href="{{ route('admin.lessons.flashcard-games.edit', [$lesson, $flashcardGame]) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Game
                    </a>
                    <a href="{{ route('flashcard-games.play', [$lesson, $flashcardGame]) }}" class="btn btn-success" target="_blank">
                        <i class="fas fa-play"></i> Play Game
                    </a>
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
                                        <p class="mb-0">{{ $flashcardGame->title }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Part:</strong>
                                        <p class="mb-0">{{ $flashcardGame->part ? $flashcardGame->part->title : 'No specific part' }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Cards per Game:</strong>
                                        <p class="mb-0">{{ $flashcardGame->cards_per_game }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <strong>Status:</strong>
                                        <span class="badge {{ $flashcardGame->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $flashcardGame->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Vocabulary Words:</strong>
                                        <p class="mb-0">{{ count($flashcardGame->vocabulary_ids) }} words</p>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Sort Order:</strong>
                                        <p class="mb-0">{{ $flashcardGame->sort_order }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <strong>Game Types:</strong>
                                <div class="mt-2">
                                    @foreach($flashcardGame->game_types as $type)
                                        <span class="badge bg-primary me-1">
                                            {{ \App\Models\FlashcardGame::getGameTypes()[$type] }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Vocabulary Words</h5>
                        </div>
                        <div class="card-body">
                            @if($flashcardGame->vocabulary->count() > 0)
                                <div class="row">
                                    @foreach($flashcardGame->vocabulary as $vocab)
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="vocabulary-item-card">
                                                @if($vocab->image_path)
                                                    <img src="{{ asset('storage/' . $vocab->image_path) }}" 
                                                         alt="{{ $vocab->english_word }}" 
                                                         class="vocab-image-small">
                                                @endif
                                                <div class="vocab-word">{{ $vocab->english_word }}</div>
                                                @if($vocab->word_audio_path)
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="playAudio('{{ asset('storage/' . $vocab->word_audio_path) }}')">
                                                        <i class="fas fa-volume-up"></i>
                                                    </button>
                                                @endif
                                            </div>
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
                                <a href="{{ route('admin.lessons.flashcard-games.edit', [$lesson, $flashcardGame]) }}" 
                                   class="btn btn-primary">
                                    <i class="fas fa-edit"></i> Edit Game
                                </a>
                                <a href="{{ route('flashcard-games.play', [$lesson, $flashcardGame]) }}" 
                                   class="btn btn-success" target="_blank">
                                    <i class="fas fa-play"></i> Play Game
                                </a>
                                <a href="{{ route('admin.lessons.flashcard-games.index', $lesson) }}" 
                                   class="btn btn-secondary">
                                    <i class="fas fa-list"></i> All Flashcard Games
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Game Types Explained</h5>
                        </div>
                        <div class="card-body">
                            @foreach($flashcardGame->game_types as $type)
                                <div class="mb-2">
                                    <strong>{{ \App\Models\FlashcardGame::getGameTypes()[$type] }}</strong>
                                    <p class="small text-muted mb-0">
                                        @if($type === 'image_to_word')
                                            Student sees an image and says the word
                                        @elseif($type === 'image_to_audio')
                                            Student sees an image and chooses correct audio
                                        @elseif($type === 'audio_to_image')
                                            Student hears a word and chooses correct image
                                        @elseif($type === 'audio_to_word')
                                            Student hears a word and chooses correct word
                                        @endif
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<audio id="audio-player" preload="auto"></audio>

<script>
function playAudio(audioPath) {
    const audio = document.getElementById('audio-player');
    audio.src = audioPath;
    audio.play();
}
</script>
@endsection

@extends('layouts.admin')

@section('title', 'Create Flashcard Game - ' . $lesson->title)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Create Flashcard Game</h1>
                    <p class="text-muted mb-0">{{ $lesson->title }}</p>
                </div>
                <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Lesson
                </a>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('admin.lessons.flashcard-games.store', $lesson) }}" method="POST">
                                @csrf
                                
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title</label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                           id="title" name="title" value="{{ old('title', $lesson->generateActivityName('flashcard', $lesson->flashcardGames()->count() + 1)) }}">
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>


                                <div class="mb-3">
                                    <div class="alert alert-info">
                                        <strong>Game Types:</strong> All flashcard types will be included automatically (Image → Word, Image → Audio, Audio → Image, Audio → Word)
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Vocabulary Words</label>
                                    <div class="row">
                                        @foreach($vocabulary as $vocab)
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="vocabulary_ids[]" value="{{ $vocab->id }}" 
                                                           id="vocab_{{ $vocab->id }}"
                                                           {{ in_array($vocab->id, old('vocabulary_ids', $vocabulary->pluck('id')->toArray())) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="vocab_{{ $vocab->id }}">
                                                        {{ $vocab->english_word }}
                                                        @if($vocab->image_path)
                                                            <i class="fas fa-image text-success ms-1"></i>
                                                        @endif
                                                        @if($vocab->word_audio_path)
                                                            <i class="fas fa-volume-up text-primary ms-1"></i>
                                                        @endif
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('vocabulary_ids')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="cards_per_game" class="form-label">Cards per Game</label>
                                    <input type="number" class="form-control @error('cards_per_game') is-invalid @enderror" 
                                           id="cards_per_game" name="cards_per_game" 
                                           value="{{ old('cards_per_game', 10) }}" min="1" max="50">
                                    <div class="form-text">Number of cards to show in each game (1-50)</div>
                                    @error('cards_per_game')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_active" 
                                               id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Active
                                        </label>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <a href="{{ route('admin.lessons.manage', $lesson) }}" 
                                       class="btn btn-secondary me-2">Cancel</a>
                                    <button type="submit" class="btn btn-primary">Create Flashcard Game</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

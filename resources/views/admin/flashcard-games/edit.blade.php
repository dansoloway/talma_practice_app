@extends('layouts.admin')

@section('title', 'Edit Flashcard Game - ' . $lesson->title)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Edit Flashcard Game</h1>
                    <p class="text-muted mb-0">{{ $lesson->title }}</p>
                </div>
                <a href="{{ route('admin.lessons.flashcard-games.show', [$lesson, $flashcardGame]) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Game
                </a>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('admin.lessons.flashcard-games.update', [$lesson, $flashcardGame]) }}" method="POST">
                                @csrf
                                @method('PUT')
                                
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title</label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                           id="title" name="title" value="{{ old('title', $flashcardGame->title) }}" required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="part_id" class="form-label">Part (Optional)</label>
                                    <select class="form-select @error('part_id') is-invalid @enderror" id="part_id" name="part_id">
                                        <option value="">No specific part</option>
                                        @foreach($parts as $part)
                                            <option value="{{ $part->id }}" {{ old('part_id', $flashcardGame->part_id) == $part->id ? 'selected' : '' }}>
                                                {{ $part->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('part_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Game Types</label>
                                    <div class="row">
                                        @foreach($gameTypes as $key => $label)
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="game_types[]" value="{{ $key }}" 
                                                           id="game_type_{{ $key }}"
                                                           {{ in_array($key, old('game_types', $flashcardGame->game_types ?? [])) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="game_type_{{ $key }}">
                                                        {{ $label }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('game_types')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
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
                                                           {{ in_array($vocab->id, old('vocabulary_ids', $flashcardGame->vocabulary_ids ?? [])) ? 'checked' : '' }}>
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
                                           value="{{ old('cards_per_game', $flashcardGame->cards_per_game) }}" min="1" max="50">
                                    <div class="form-text">Number of cards to show in each game (1-50)</div>
                                    @error('cards_per_game')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_active" 
                                               id="is_active" {{ old('is_active', $flashcardGame->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Active
                                        </label>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <a href="{{ route('admin.lessons.flashcard-games.show', [$lesson, $flashcardGame]) }}" 
                                       class="btn btn-secondary me-2">Cancel</a>
                                    <button type="submit" class="btn btn-primary">Update Flashcard Game</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Game Types Explained</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Image → Word</strong>
                                <p class="small text-muted mb-2">Student sees an image and says the word</p>
                            </div>
                            <div class="mb-3">
                                <strong>Image → Audio</strong>
                                <p class="small text-muted mb-2">Student sees an image and chooses correct audio</p>
                            </div>
                            <div class="mb-3">
                                <strong>Audio → Image</strong>
                                <p class="small text-muted mb-2">Student hears a word and chooses correct image</p>
                            </div>
                            <div class="mb-3">
                                <strong>Audio → Word</strong>
                                <p class="small text-muted mb-2">Student hears a word and chooses correct word</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

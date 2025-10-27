@extends('layouts.admin')

@section('title', 'Edit Flashcard Game - ' . $lesson->title)

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="back-link">← Back to Lesson</a>
            <h1 class="page-title">Edit Flashcard Game</h1>
            <p class="page-subtitle">{{ $lesson->title }}</p>
        </div>
    </div>

    <div class="form-container">
        <form action="{{ route('admin.lessons.flashcard-games.update', [$lesson, $flashcardGame]) }}" method="POST" class="form">
            @csrf
            @method('PUT')
            
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror" 
                       value="{{ old('title', $flashcardGame->title) }}" required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label>Game Types</label>
                <div class="info-message">
                    All flashcard types are included automatically: Image → Word, Image → Audio, Audio → Image, Audio → Word
                </div>
            </div>

            <div class="form-group">
                <label>Vocabulary Words</label>
                <div class="checkbox-grid">
                    @foreach($vocabulary as $vocab)
                        <div class="checkbox-item">
                            <input type="checkbox" name="vocabulary_ids[]" value="{{ $vocab->id }}" 
                                   id="vocab_{{ $vocab->id }}"
                                   {{ in_array($vocab->id, old('vocabulary_ids', $flashcardGame->vocabulary_ids ?? [])) ? 'checked' : '' }}>
                            <label for="vocab_{{ $vocab->id }}">
                                {{ $vocab->english_word }}
                                @if($vocab->image_path)
                                    <span class="indicator success">📷</span>
                                @endif
                                @if($vocab->word_audio_path)
                                    <span class="indicator primary">🔊</span>
                                @endif
                            </label>
                        </div>
                    @endforeach
                </div>
                @error('vocabulary_ids')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="cards_per_game">Cards per Game</label>
                <input type="number" id="cards_per_game" name="cards_per_game" 
                       class="form-control @error('cards_per_game') is-invalid @enderror"
                       value="{{ old('cards_per_game', $flashcardGame->cards_per_game) }}" min="1" max="50">
                <small class="form-text">Number of cards to show in each game (1-50)</small>
                @error('cards_per_game')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <div class="checkbox-item">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1" 
                           {{ old('is_active') ? (old('is_active') == '1' ? 'checked' : '') : ($flashcardGame->is_active ?? true ? 'checked' : '') }}>
                    <label for="is_active">Active</label>
                </div>
                @error('is_active')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Flashcard Game</button>
            </div>
        </form>
    </div>

</div>
@endsection

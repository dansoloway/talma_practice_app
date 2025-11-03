@extends('layouts.admin')

@section('title', 'Flashcard Games - ' . $lesson->title)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Flashcard Games</h1>
                    <p class="text-muted mb-0">{{ $lesson->title }}</p>
                </div>
                <div>
                    <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Lesson
                    </a>
                    <a href="{{ route('admin.lessons.flashcard-games.create', $lesson) }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Flashcard Game
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($flashcardGames->count() > 0)
                <div class="row">
                    @foreach($flashcardGames as $game)
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $game->title }}</h5>
                                    <p class="card-text">
                                        <small class="text-muted">
                                            {{ $game->cards_per_game }} cards per game
                                        </small>
                                    </p>
                                    <div class="mb-3">
                                        <strong>Game Types:</strong>
                                        <div class="mt-1">
                                            @foreach($game->game_types as $type)
                                                <span class="badge bg-primary me-1">
                                                    {{ \App\Models\FlashcardGame::getGameTypes()[$type] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Vocabulary:</strong> {{ count($game->vocabulary_ids) }} words
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge {{ $game->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $game->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.lessons.flashcard-games.show', [$lesson, $game]) }}" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.lessons.flashcard-games.edit', [$lesson, $game]) }}" 
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="{{ route('flashcard-games.play', [$lesson, $game]) }}" 
                                               class="btn btn-sm btn-outline-success" target="_blank">
                                                <i class="fas fa-play"></i>
                                            </a>
                                            <form action="{{ route('admin.lessons.flashcard-games.destroy', [$lesson, $game]) }}" 
                                                  method="POST" class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this flashcard game?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-gamepad fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Flashcard Games</h4>
                    <p class="text-muted">Create your first flashcard game to get started.</p>
                    <a href="{{ route('admin.lessons.flashcard-games.create', $lesson) }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Flashcard Game
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', 'Create True/False Game')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Create True/False Game</h1>
        <div class="page-actions">
            <a href="{{ route('admin.lessons.true-false-games.index', $lesson) }}" class="btn">← Back to Games</a>
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

    <form action="{{ route('admin.lessons.true-false-games.store', $lesson) }}" method="POST">
        @csrf
        
        <div class="form-group">
            <label for="title">Game Title</label>
            <input type="text" id="title" name="title" value="{{ old('title') }}" class="form-control" placeholder="e.g., True/False Game (Easy)">
            <small>Leave blank to auto-generate based on difficulty</small>
        </div>

        <div class="form-group">
            <label for="game_version">Difficulty Level</label>
            <select id="game_version" name="game_version" class="form-control" required>
                <option value="easy" {{ old('game_version') == 'easy' ? 'selected' : '' }}>Easy - Simple vocabulary usage, no negation</option>
                <option value="medium" {{ old('game_version') == 'medium' ? 'selected' : '' }}>Medium - One reasoning lever, may include negation</option>
                <option value="hard" {{ old('game_version') == 'hard' ? 'selected' : '' }}>Hard - Near-miss meanings, complex reasoning</option>
            </select>
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
            <button type="submit" class="btn btn-primary">Create Game</button>
            <a href="{{ route('admin.lessons.true-false-games.index', $lesson) }}" class="btn">Cancel</a>
        </div>
    </form>
</div>
@endsection

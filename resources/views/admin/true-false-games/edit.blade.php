@extends('layouts.admin')

@section('title', 'Edit True/False Game')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Edit True/False Game</h1>
        <div class="page-actions">
            <a href="{{ route('admin.lessons.true-false-games.show', [$lesson, $trueFalseGame]) }}" class="btn">← Back to Game</a>
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

    <form action="{{ route('admin.lessons.true-false-games.update', [$lesson, $trueFalseGame]) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="form-group">
            <label for="title">Game Title</label>
            <input type="text" id="title" name="title" value="{{ old('title', $trueFalseGame->title) }}" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="game_version">Difficulty Level</label>
            <select id="game_version" name="game_version" class="form-control" required>
                <option value="easy" {{ old('game_version', $trueFalseGame->game_version) == 'easy' ? 'selected' : '' }}>Easy - Simple vocabulary usage, no negation</option>
                <option value="medium" {{ old('game_version', $trueFalseGame->game_version) == 'medium' ? 'selected' : '' }}>Medium - One reasoning lever, may include negation</option>
                <option value="hard" {{ old('game_version', $trueFalseGame->game_version) == 'hard' ? 'selected' : '' }}>Hard - Near-miss meanings, complex reasoning</option>
            </select>
            <small class="form-text">Note: Changing difficulty will affect future AI-generated questions, but existing questions will keep their current difficulty.</small>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $trueFalseGame->is_active) ? 'checked' : '' }}>
                <span class="checkmark"></span>
                Active (students can play this game)
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Game</button>
            <a href="{{ route('admin.lessons.true-false-games.show', [$lesson, $trueFalseGame]) }}" class="btn">Cancel</a>
        </div>
    </form>
</div>
@endsection

@extends('layouts.admin')

@section('title', 'Edit Sentence Builder Game - ' . $game->title)

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Edit Sentence Builder Game</h1>
        <div class="page-actions">
            <a href="{{ route('admin.lessons.sentence-builder-games.show', [$lesson, $game]) }}" class="btn">← Back to Game</a>
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

    <form action="{{ route('admin.lessons.sentence-builder-games.update', [$lesson, $game]) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" class="form-control" 
                   value="{{ old('title', $game->title) }}" required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Game</button>
            <a href="{{ route('admin.lessons.sentence-builder-games.show', [$lesson, $game]) }}" class="btn">Cancel</a>
        </div>
    </form>
</div>
@endsection


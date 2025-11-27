@extends('layouts.admin')

@section('title', 'Create Sentence Builder Game - ' . $lesson->title)

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Create Sentence Builder Game</h1>
        <div class="page-actions">
            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn">← Back to Lesson</a>
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

    <form action="{{ route('admin.lessons.sentence-builder-games.store', $lesson) }}" method="POST">
        @csrf
        
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" class="form-control" 
                   value="{{ old('title', trim($lesson->title . ' Sentence Builder ' . ($lesson->sentenceBuilderGames()->count() + 1))) }}">
            <small class="form-text">Leave blank to auto-generate</small>
        </div>

        <div class="form-group">
            <label for="sort_order">Sort Order</label>
            <input type="number" id="sort_order" name="sort_order" class="form-control" 
                   value="{{ old('sort_order', $lesson->sentenceBuilderGames()->max('sort_order') + 1 ?? 0) }}">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Game</button>
            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn">Cancel</a>
        </div>
    </form>

    <div class="alert alert-info mt-4">
        <strong>Note:</strong> After creating the game, you can generate questions using AI or add them manually.
    </div>
</div>
@endsection


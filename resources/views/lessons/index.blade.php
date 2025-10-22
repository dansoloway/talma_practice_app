@extends('layouts.app')

@section('title', 'Lessons')

@section('content')
<div class="container">
    <h1 class="page-title">Choose a Lesson</h1>
    
    @if($lessons->isEmpty())
        <div class="empty-state">
            <p>No lessons available yet.</p>
            <a href="{{ route('admin.lessons.create') }}" class="btn btn-primary">Create Your First Lesson</a>
        </div>
    @else
        <div class="lesson-grid">
            @foreach($lessons as $lesson)
                <div class="lesson-card">
                    <h2>{{ $lesson->title }}</h2>
                    <p>{{ $lesson->prompts->count() }} prompts</p>
                    <a href="{{ route('lessons.show', $lesson->slug) }}" class="btn btn-primary">Start Lesson</a>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection


@extends('layouts.admin')

@section('title', 'Sentence Builder Games - ' . $lesson->title)

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Sentence Builder Games</h1>
        <div class="page-actions">
            <a href="{{ route('admin.lessons.sentence-builder-games.create', $lesson) }}" class="btn btn-primary">+ Create Game</a>
            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn">← Back to Lesson</a>
        </div>
    </div>

    @if($games->count() > 0)
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Questions</th>
                        <th>Status</th>
                        <th>Sort Order</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($games as $game)
                        <tr>
                            <td>
                                <a href="{{ route('admin.lessons.sentence-builder-games.show', [$lesson, $game]) }}">
                                    {{ $game->title }}
                                </a>
                            </td>
                            <td>{{ $game->questions()->count() }}</td>
                            <td>
                                <span class="status {{ $game->is_active ? 'active' : 'inactive' }}">
                                    {{ $game->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $game->sort_order }}</td>
                            <td>{{ $game->created_at->format('M d, Y') }}</td>
                            <td>
                                <a href="{{ route('admin.lessons.sentence-builder-games.show', [$lesson, $game]) }}" class="btn btn-xs">View</a>
                                <a href="{{ route('admin.lessons.sentence-builder-games.edit', [$lesson, $game]) }}" class="btn btn-xs">Edit</a>
                                <a href="{{ route('sentence-builder-games.play', [$lesson, $game]) }}" class="btn btn-xs btn-success" target="_blank">Play</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <p>No sentence builder games yet.</p>
            <a href="{{ route('admin.lessons.sentence-builder-games.create', $lesson) }}" class="btn btn-primary">Create First Game</a>
        </div>
    @endif
</div>
@endsection


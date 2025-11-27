@extends('layouts.admin')

@section('title', 'Spelling Games for ' . $lesson->title)

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Spelling Games for: {{ $lesson->title }}</h1>
        <div class="page-actions">
            <a href="{{ route('admin.lessons.spelling-games.create', $lesson) }}" class="btn btn-primary">Create Spelling Game</a>
            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn">Back to Lesson</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($spellingGames->count() > 0)
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Difficulty</th>
                        <th>Vocabulary Count</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($spellingGames as $game)
                        <tr>
                            <td><strong>{{ $game->title }}</strong></td>
                            <td>
                                <span class="badge badge-{{ $game->difficulty === 'easy' ? 'success' : ($game->difficulty === 'medium' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($game->difficulty) }}
                                </span>
                            </td>
                            <td>{{ count($game->vocabulary_ids ?? []) }} words</td>
                            <td>
                                <span class="status {{ $game->is_active ? 'active' : 'inactive' }}">
                                    {{ $game->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="actions">
                                <a href="{{ route('admin.lessons.spelling-games.show', [$lesson, $game]) }}" class="btn btn-sm">View</a>
                                <a href="{{ route('admin.lessons.spelling-games.edit', [$lesson, $game]) }}" class="btn btn-sm">Edit</a>
                                <a href="{{ route('spelling-games.play', [$lesson, $game]) }}" class="btn btn-sm btn-success" target="_blank">Play</a>
                                <form action="{{ route('admin.lessons.spelling-games.destroy', [$lesson, $game]) }}" method="POST" class="inline-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this spelling game?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <h3>No Spelling Games Yet</h3>
            <p>Create your first spelling practice game to help students learn!</p>
            <a href="{{ route('admin.lessons.spelling-games.create', $lesson) }}" class="btn btn-primary">Create Spelling Game</a>
        </div>
    @endif
</div>
@endsection


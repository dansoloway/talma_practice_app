@extends('layouts.admin')

@section('title', 'Matching Games for ' . $lesson->title)

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Matching Games for: {{ $lesson->title }}</h1>
        <div class="page-actions">
            <a href="{{ route('admin.lessons.matching-games.create', $lesson) }}" class="btn btn-primary">Create Matching Game</a>
            <a href="{{ route('admin.lessons.show', $lesson) }}" class="btn">Back to Lesson</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($matchingGames->count() > 0)
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Part</th>
                        <th>Grid Size</th>
                        <th>Vocabulary Count</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($matchingGames as $game)
                        <tr>
                            <td><strong>{{ $game->title }}</strong></td>
                            <td>
                                @if($game->part)
                                    {{ $game->part->title }}
                                @else
                                    <span class="text-muted">No specific part</span>
                                @endif
                            </td>
                            <td>{{ $game->grid_size }}x{{ $game->grid_size }}</td>
                            <td>{{ count($game->vocabulary_ids) }} words</td>
                            <td>
                                <span class="status {{ $game->is_active ? 'active' : 'inactive' }}">
                                    {{ $game->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="actions">
                                <a href="{{ route('admin.lessons.matching-games.show', [$lesson, $game]) }}" class="btn btn-sm">View</a>
                                <a href="{{ route('admin.lessons.matching-games.edit', [$lesson, $game]) }}" class="btn btn-sm">Edit</a>
                                <a href="{{ route('matching-games.play', [$lesson, $game]) }}" class="btn btn-sm btn-success" target="_blank">Play</a>
                                <form action="{{ route('admin.lessons.matching-games.destroy', [$lesson, $game]) }}" method="POST" class="inline-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this matching game?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <h3>No Matching Games Yet</h3>
            <p>Create your first vocabulary matching game to help students learn!</p>
            <a href="{{ route('admin.lessons.matching-games.create', $lesson) }}" class="btn btn-primary">Create Matching Game</a>
        </div>
    @endif
</div>
@endsection

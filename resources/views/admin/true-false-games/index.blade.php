@extends('layouts.admin')

@section('title', 'True/False Games for ' . $lesson->title)

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">True/False Games for: {{ $lesson->title }}</h1>
        <div class="page-actions">
            <a href="{{ route('admin.lessons.true-false-games.create', $lesson) }}" class="btn btn-primary">Create True/False Game</a>
            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn">Back to Lesson</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($games->count() > 0)
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Difficulty</th>
                        <th>Questions</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($games as $game)
                        @php
                            $questionCounts = [
                                'total' => $game->questions()->count(),
                                'approved' => $game->questions()->where('is_approved', true)->where('is_active', true)->count(),
                                'pending' => $game->questions()->where('is_approved', false)->count(),
                            ];
                        @endphp
                        <tr>
                            <td><strong>{{ $game->title }}</strong></td>
                            <td>
                                <span class="badge badge-{{ $game->game_version === 'easy' ? 'success' : ($game->game_version === 'medium' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($game->game_version) }}
                                </span>
                            </td>
                            <td>
                                {{ $questionCounts['approved'] }} approved
                                @if($questionCounts['pending'] > 0)
                                    <span class="text-muted">({{ $questionCounts['pending'] }} pending)</span>
                                @endif
                            </td>
                            <td>
                                <span class="status {{ $game->is_active ? 'active' : 'inactive' }}">
                                    {{ $game->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="actions">
                                <a href="{{ route('admin.lessons.true-false-games.show', [$lesson, $game]) }}" class="btn btn-sm">Manage Questions</a>
                                <a href="{{ route('admin.lessons.true-false-games.edit', [$lesson, $game]) }}" class="btn btn-sm">Edit</a>
                                @if($questionCounts['approved'] > 0)
                                    <a href="{{ route('true-false-games.play', [$lesson, $game]) }}" class="btn btn-sm btn-success" target="_blank">Play</a>
                                @endif
                                <form action="{{ route('admin.lessons.true-false-games.destroy', [$lesson, $game]) }}" method="POST" class="inline-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this True/False game? All questions will be deleted too.')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <h3>No True/False Games Yet</h3>
            <p>Create your first True/False game to help students test vocabulary understanding!</p>
            <a href="{{ route('admin.lessons.true-false-games.create', $lesson) }}" class="btn btn-primary">Create True/False Game</a>
        </div>
    @endif
</div>
@endsection

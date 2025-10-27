@extends('layouts.admin')

@section('title', 'Archived Lessons')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Archived Lessons</h1>
        <a href="{{ route('admin.lessons.index') }}" class="btn">Back to Active Lessons</a>
    </div>

    @if($lessons->count() > 0)
        <div class="results-info">
            <p>Showing {{ $lessons->count() }} archived lesson{{ $lessons->count() !== 1 ? 's' : '' }}</p>
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Grade</th>
                    <th>Session</th>
                    <th>Session Title</th>
                    <th>Activities</th>
                    <th>Archived</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lessons as $lesson)
                    <tr class="archived-row">
                        <td><strong>{{ $lesson->title }}</strong></td>
                        <td>{{ $lesson->grade_level ? 'Grade ' . $lesson->grade_level : '-' }}</td>
                        <td>{{ $lesson->session_number ?: '-' }}</td>
                        <td>{{ $lesson->session_title ?: '-' }}</td>
                        <td>
                            <span class="activity-count">
                                {{ $lesson->vocabulary->count() }} vocab, 
                                {{ $lesson->prompts->count() + $lesson->matchingGames->count() + $lesson->flashcardGames->count() }} activities
                            </span>
                        </td>
                        <td>
                            <span class="archived-date">
                                <i class="fas fa-archive"></i>
                                {{ $lesson->archived_at->format('M j, Y') }}
                                <small>({{ $lesson->archived_at->diffForHumans() }})</small>
                            </span>
                        </td>
                        <td class="actions">
                            <form action="{{ route('admin.lessons.unarchive', $lesson) }}" method="POST" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to restore this lesson? It will become visible to students again.')">
                                    <i class="fas fa-undo"></i> Restore
                                </button>
                            </form>
                            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn btn-sm btn-secondary">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-archive"></i>
            </div>
            <h3>No Archived Lessons</h3>
            <p>You haven't archived any lessons yet. When you archive lessons, they'll appear here and can be restored if needed.</p>
            <a href="{{ route('admin.lessons.index') }}" class="btn btn-primary">View Active Lessons</a>
        </div>
    @endif
</div>
@endsection

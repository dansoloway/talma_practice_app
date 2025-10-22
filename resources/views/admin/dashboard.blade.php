@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="container">
    <h1 class="page-title">Admin Dashboard</h1>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">{{ $stats['lessons_count'] }}</div>
            <div class="stat-label">Lessons</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $stats['prompts_count'] }}</div>
            <div class="stat-label">Prompts</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $stats['options_count'] }}</div>
            <div class="stat-label">Options</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $stats['responses_count'] }}</div>
            <div class="stat-label">Responses</div>
        </div>
    </div>

    <div class="dashboard-sections">
        <div class="dashboard-section">
            <h2>Recent Lessons</h2>
            @if($recentLessons->isEmpty())
                <p class="empty-text">No lessons yet.</p>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Slug</th>
                            <th>Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentLessons as $lesson)
                            <tr>
                                <td>{{ $lesson->title }}</td>
                                <td>{{ $lesson->slug }}</td>
                                <td>{{ $lesson->is_active ? 'Yes' : 'No' }}</td>
                                <td>
                                    <a href="{{ route('admin.lessons.show', $lesson) }}" class="btn btn-sm">View</a>
                                    <a href="{{ route('admin.lessons.edit', $lesson) }}" class="btn btn-sm">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            <a href="{{ route('admin.lessons.create') }}" class="btn btn-primary">Create New Lesson</a>
        </div>

        <div class="dashboard-section">
            <h2>Recent Responses</h2>
            @if($recentResponses->isEmpty())
                <p class="empty-text">No responses yet.</p>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>Lesson</th>
                            <th>Prompt</th>
                            <th>Option</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentResponses as $response)
                            <tr>
                                <td>{{ $response->lesson->title }}</td>
                                <td>{{ Str::limit($response->prompt->prompt_text, 40) }}</td>
                                <td>{{ $response->option->label }}</td>
                                <td>{{ $response->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection


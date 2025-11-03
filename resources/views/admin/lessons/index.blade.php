@extends('layouts.admin')

@section('title', 'Lessons')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Lessons</h1>
        <a href="{{ route('admin.lessons.create') }}" class="btn btn-primary">Create Lesson</a>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" action="{{ route('admin.lessons.index') }}" class="filters-form">
            <div class="filter-group">
                <label for="grade_level">Grade Level:</label>
                <select name="grade_level" id="grade_level" class="form-control">
                    <option value="">All Grades</option>
                    @foreach($gradeLevels as $grade)
                        <option value="{{ $grade }}" {{ request('grade_level') == $grade ? 'selected' : '' }}>
                            Grade {{ $grade }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="filter-group">
                <label for="search">Search:</label>
                <input type="text" name="search" id="search" class="form-control" 
                       placeholder="Search lessons..." value="{{ request('search') }}">
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('admin.lessons.index') }}" class="btn btn-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>

    @if($lessons->isEmpty())
        <div class="empty-state">
            @if(request()->hasAny(['grade_level', 'search']))
                <h3>No lessons found</h3>
                <p>No lessons match your current filters. Try adjusting your search criteria or <a href="{{ route('admin.lessons.index') }}">clear all filters</a>.</p>
            @else
                <h3>No lessons yet</h3>
                <p>You haven't created any lessons yet. <a href="{{ route('admin.lessons.create') }}">Create your first lesson</a> to get started.</p>
            @endif
        </div>
    @else
        <div class="results-info">
            <p>Showing {{ $lessons->count() }} lesson{{ $lessons->count() !== 1 ? 's' : '' }}
            @if(request()->hasAny(['grade_level', 'search']))
                matching your filters
            @endif
            </p>
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Grade</th>
                    <th>Session</th>
                    <th>Session Title</th>
                    <th>Slug</th>
                    <th>Activities</th>
                    <th>Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lessons as $lesson)
                    <tr>
                        <td><strong>{{ $lesson->title }}</strong></td>
                        <td>{{ $lesson->grade_level ? 'Grade ' . $lesson->grade_level : '-' }}</td>
                        <td>{{ $lesson->session_number ? 'Session ' . $lesson->session_number : '-' }}</td>
                        <td>{{ $lesson->session_title ?? '-' }}</td>
                        <td><code>{{ $lesson->slug }}</code></td>
                        <td>
                            @php
                                $activityCount = ($lesson->prompts->count() > 0 ? 1 : 0) + $lesson->matchingGames->count() + $lesson->flashcardGames->count();
                                $vocabCount = $lesson->vocabulary->count();
                            @endphp
                            {{ $activityCount }} activities
                            @if($vocabCount > 0)
                                <br><small>{{ $vocabCount }} vocab words</small>
                            @endif
                        </td>
                        <td>{{ $lesson->is_active ? '✓' : '✗' }}</td>
                        <td class="actions">
                            <a href="{{ route('admin.lessons.show', $lesson) }}" class="btn btn-sm">View</a>
                            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn btn-sm">Edit</a>
                            <form action="{{ route('admin.lessons.archive', $lesson) }}" method="POST" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to archive this lesson? Students will no longer be able to access it, but it can be restored from the archived lessons page.')">Archive</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection


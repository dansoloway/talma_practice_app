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
                <label for="session_number">Session Number:</label>
                <select name="session_number" id="session_number" class="form-control">
                    <option value="">All Sessions</option>
                    @foreach($sessionNumbers as $session)
                        <option value="{{ $session }}" {{ request('session_number') == $session ? 'selected' : '' }}>
                            Session {{ $session }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="filter-group">
                <label for="search">Search:</label>
                <input type="text" name="search" id="search" class="form-control" 
                       placeholder="Search by title, session title, or slug..." value="{{ request('search') }}">
            </div>
            
            <div class="filter-group">
                <label class="checkbox-label" style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" name="view_archived" value="1" id="view_archived" 
                           {{ $showArchived ?? false ? 'checked' : '' }}>
                    <span>View Archived Lessons</span>
                </label>
            </div>
            
            <!-- Preserve sort parameters -->
            @if(request('sort_by'))
                <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
            @endif
            @if(request('sort_dir'))
                <input type="hidden" name="sort_dir" value="{{ request('sort_dir') }}">
            @endif
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('admin.lessons.index') }}" class="btn btn-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>

    @if($lessons->isEmpty())
        <div class="empty-state">
            @if(request()->hasAny(['grade_level', 'session_number', 'search', 'view_archived']))
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
            @if(request()->hasAny(['grade_level', 'session_number', 'search', 'view_archived']))
                matching your filters
            @endif
            </p>
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>
                        @php
                            $newSortDir = ($sortBy == 'title' && $sortDir == 'asc') ? 'desc' : 'asc';
                            $sortUrl = request()->fullUrlWithQuery(array_merge(request()->except(['sort_by', 'sort_dir']), ['sort_by' => 'title', 'sort_dir' => $newSortDir]));
                        @endphp
                        <a href="{{ $sortUrl }}" 
                           class="sortable-header" title="Click to sort">
                            <span>Title</span>
                            @if($sortBy == 'title')
                                <span class="sort-indicator active">{{ $sortDir == 'asc' ? '↑' : '↓' }}</span>
                            @else
                                <span class="sort-indicator">↕</span>
                            @endif
                        </a>
                    </th>
                    <th>Grade</th>
                    <th>Session</th>
                    <th>Part</th>
                    <th>
                        @php
                            $newSortDir = ($sortBy == 'slug' && $sortDir == 'asc') ? 'desc' : 'asc';
                            $sortUrl = request()->fullUrlWithQuery(array_merge(request()->except(['sort_by', 'sort_dir']), ['sort_by' => 'slug', 'sort_dir' => $newSortDir]));
                        @endphp
                        <a href="{{ $sortUrl }}" 
                           class="sortable-header" title="Click to sort">
                            <span>Slug</span>
                            @if($sortBy == 'slug')
                                <span class="sort-indicator active">{{ $sortDir == 'asc' ? '↑' : '↓' }}</span>
                            @else
                                <span class="sort-indicator">↕</span>
                            @endif
                        </a>
                    </th>
                    <th>Activities</th>
                    <th>
                        @php
                            $newSortDir = ($sortBy == 'updated_at' && $sortDir == 'asc') ? 'desc' : 'asc';
                            $sortUrl = request()->fullUrlWithQuery(array_merge(request()->except(['sort_by', 'sort_dir']), ['sort_by' => 'updated_at', 'sort_dir' => $newSortDir]));
                        @endphp
                        <a href="{{ $sortUrl }}" 
                           class="sortable-header" title="Click to sort">
                            <span>Last Modified</span>
                            @if($sortBy == 'updated_at')
                                <span class="sort-indicator active">{{ $sortDir == 'asc' ? '↑' : '↓' }}</span>
                            @else
                                <span class="sort-indicator">↕</span>
                            @endif
                        </a>
                    </th>
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
                        <td>{{ $lesson->part_number ? 'Part ' . $lesson->part_number : '-' }}</td>
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
                        <td>
                            <small>{{ $lesson->updated_at->diffForHumans() }}</small>
                            <br><small style="color: var(--color-text-muted);">{{ $lesson->updated_at->format('M d, Y') }}</small>
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

<style>
.sortable-header {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    margin: -0.5rem -0.75rem;
    border-radius: 4px;
    text-decoration: none;
    color: inherit;
    cursor: pointer;
    transition: all 0.2s ease;
    user-select: none;
}

.sortable-header:hover {
    background-color: rgba(0, 123, 255, 0.1);
    color: var(--color-primary, #007bff) !important;
    text-decoration: underline;
}

.sortable-header:active {
    background-color: rgba(0, 123, 255, 0.2);
}

.sort-indicator {
    font-size: 0.875em;
    opacity: 0.5;
    font-weight: normal;
    transition: opacity 0.2s ease;
}

.sortable-header:hover .sort-indicator {
    opacity: 1;
}

.sort-indicator.active {
    opacity: 1;
    font-weight: bold;
    color: var(--color-primary, #007bff);
}

th {
    position: relative;
}
</style>
@endsection


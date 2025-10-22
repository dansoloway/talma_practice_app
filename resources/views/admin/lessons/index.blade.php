@extends('layouts.admin')

@section('title', 'Lessons')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Lessons</h1>
        <a href="{{ route('admin.lessons.create') }}" class="btn btn-primary">Create Lesson</a>
    </div>

    @if($lessons->isEmpty())
        <div class="empty-state">
            <p>No lessons yet.</p>
        </div>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Prompts</th>
                    <th>Active</th>
                    <th>Sort</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lessons as $lesson)
                    <tr>
                        <td><strong>{{ $lesson->title }}</strong></td>
                        <td>{{ $lesson->slug }}</td>
                        <td>{{ $lesson->prompts->count() }}</td>
                        <td>{{ $lesson->is_active ? '✓' : '✗' }}</td>
                        <td>{{ $lesson->sort_order }}</td>
                        <td class="actions">
                            <a href="{{ route('admin.lessons.show', $lesson) }}" class="btn btn-sm">View</a>
                            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn btn-sm">Edit</a>
                            <form action="{{ route('admin.lessons.destroy', $lesson) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection


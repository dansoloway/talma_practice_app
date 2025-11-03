@extends('layouts.admin')

@section('title', 'Parts for ' . $lesson->title)

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Parts for: {{ $lesson->title }}</h1>
        <div class="page-actions">
            <a href="{{ route('admin.lessons.parts.create', $lesson) }}" class="btn btn-primary">Add Part</a>
            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn">Back to Lesson</a>
        </div>
    </div>

    @if($parts->count() > 0)
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Sort Order</th>
                        <th>Status</th>
                        <th>Prompts</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($parts as $part)
                        <tr>
                            <td>{{ $part->title }}</td>
                            <td>{{ Str::limit($part->description, 50) }}</td>
                            <td>{{ $part->sort_order }}</td>
                            <td>
                                <span class="status {{ $part->is_active ? 'active' : 'inactive' }}">
                                    {{ $part->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $part->prompts->count() }} prompts</td>
                            <td class="actions">
                                <a href="{{ route('admin.lessons.parts.show', [$lesson, $part]) }}" class="btn btn-sm">View</a>
                                <a href="{{ route('admin.lessons.parts.edit', [$lesson, $part]) }}" class="btn btn-sm">Edit</a>
                                <form action="{{ route('admin.lessons.parts.destroy', [$lesson, $part]) }}" method="POST" class="inline-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <h3>No parts yet</h3>
            <p>This lesson doesn't have any parts yet. Create the first part to get started.</p>
            <a href="{{ route('admin.lessons.parts.create', $lesson) }}" class="btn btn-primary">Create First Part</a>
        </div>
    @endif
</div>
@endsection

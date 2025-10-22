@extends('layouts.admin')

@section('title', $lesson->title)

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.lessons.index') }}" class="back-link">&larr; Back to Lessons</a>
            <h1 class="page-title">{{ $lesson->title }}</h1>
        </div>
        <div>
            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn btn-primary">Manage Lesson</a>
            <a href="{{ route('admin.lessons.edit', $lesson) }}" class="btn">Quick Edit</a>
        </div>
    </div>

    <div class="info-box">
        <p><strong>Slug:</strong> {{ $lesson->slug }}</p>
        <p><strong>Active:</strong> {{ $lesson->is_active ? 'Yes' : 'No' }}</p>
        <p><strong>Sort Order:</strong> {{ $lesson->sort_order }}</p>
        <p><strong>Parts:</strong> {{ $lesson->parts->count() }}</p>
        <p><strong>Vocabulary:</strong> {{ $lesson->vocabulary->count() }}</p>
        <p><strong>Total Prompts:</strong> {{ $lesson->prompts->count() }}</p>
    </div>

    <h2>Parts</h2>

    @if($lesson->parts->isEmpty())
        <div class="empty-state">
            <p>No parts yet. Create parts to organize your lesson content.</p>
            <a href="{{ route('admin.lessons.parts.create', $lesson) }}" class="btn btn-primary">Create First Part</a>
        </div>
    @else
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Prompts</th>
                        <th>Sort Order</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lesson->parts as $part)
                        <tr>
                            <td>{{ $part->title }}</td>
                            <td>{{ Str::limit($part->description, 50) }}</td>
                            <td>{{ $part->prompts->count() }} prompts</td>
                            <td>{{ $part->sort_order }}</td>
                            <td>
                                <span class="status {{ $part->is_active ? 'active' : 'inactive' }}">
                                    {{ $part->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="actions">
                                <a href="{{ route('admin.lessons.parts.show', [$lesson, $part]) }}" class="btn btn-sm">View</a>
                                <a href="{{ route('admin.lessons.parts.edit', [$lesson, $part]) }}" class="btn btn-sm">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection


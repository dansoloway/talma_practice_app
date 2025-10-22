@extends('layouts.admin')

@section('title', 'Part: ' . $part->title)

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">{{ $part->title }}</h1>
        <div class="page-actions">
            <a href="{{ route('admin.lessons.parts.edit', [$lesson, $part]) }}" class="btn">Edit Part</a>
            <a href="{{ route('admin.lessons.parts.index', $lesson) }}" class="btn">Back to Parts</a>
        </div>
    </div>

    <div class="part-details">
        <div class="detail-group">
            <h3>Description</h3>
            <p>{{ $part->description ?: 'No description provided' }}</p>
        </div>

        <div class="detail-group">
            <h3>Status</h3>
            <span class="status {{ $part->is_active ? 'active' : 'inactive' }}">
                {{ $part->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>

        <div class="detail-group">
            <h3>Sort Order</h3>
            <p>{{ $part->sort_order }}</p>
        </div>
    </div>

    <div class="prompts-section">
        <div class="section-header">
            <h2>Prompts ({{ $part->prompts->count() }})</h2>
            <a href="{{ route('admin.prompts.create', ['lesson' => $lesson->id, 'part' => $part->id]) }}" class="btn btn-primary">Add Prompt</a>
        </div>

        @if($part->prompts->count() > 0)
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Prompt Text</th>
                            <th>Template</th>
                            <th>Options</th>
                            <th>Sort Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($part->prompts as $prompt)
                            <tr>
                                <td>{{ Str::limit($prompt->prompt_text, 50) }}</td>
                                <td>{{ Str::limit($prompt->template, 30) }}</td>
                                <td>{{ $prompt->options->count() }} options</td>
                                <td>{{ $prompt->sort_order }}</td>
                                <td class="actions">
                                    <a href="{{ route('admin.prompts.show', $prompt) }}" class="btn btn-sm">View</a>
                                    <a href="{{ route('admin.prompts.edit', $prompt) }}" class="btn btn-sm">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <h3>No prompts yet</h3>
                <p>This part doesn't have any prompts yet. Add the first prompt to get started.</p>
                <a href="{{ route('admin.prompts.create', ['lesson' => $lesson->id, 'part' => $part->id]) }}" class="btn btn-primary">Add First Prompt</a>
            </div>
        @endif
    </div>
</div>
@endsection

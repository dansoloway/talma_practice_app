@extends('layouts.admin')

@section('title', 'Prompt Details')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.lessons.show', $prompt->lesson) }}" class="back-link">&larr; Back to {{ $prompt->lesson->title }}</a>
            <h1 class="page-title">{{ $prompt->prompt_text }}</h1>
        </div>
        <div>
            <a href="{{ route('admin.prompts.edit', $prompt) }}" class="btn">Edit Prompt</a>
            <a href="{{ route('admin.prompts.options.create', $prompt) }}" class="btn btn-primary">Add Option</a>
        </div>
    </div>

    <div class="info-box">
        <p><strong>Lesson:</strong> {{ $prompt->lesson->title }}</p>
        <p><strong>Template:</strong> <code>{{ $prompt->template }}</code></p>
        <p><strong>TTS Voice:</strong> {{ $prompt->tts_voice }}</p>
        <p><strong>Sort Order:</strong> {{ $prompt->sort_order }}</p>
        <p><strong>Options:</strong> {{ $prompt->options->count() }}</p>
    </div>

    <h2>Options</h2>

    @if($prompt->options->isEmpty())
        <div class="empty-state">
            <p>No options yet.</p>
            <a href="{{ route('admin.prompts.options.create', $prompt) }}" class="btn btn-primary">Add First Option</a>
        </div>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>Sort</th>
                    <th>Label</th>
                    <th>Image Path</th>
                    <th>Active</th>
                    <th>Generated Sentence</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($prompt->options as $option)
                    <tr>
                        <td>{{ $option->sort_order }}</td>
                        <td><strong>{{ $option->label }}</strong></td>
                        <td><code>{{ $option->image_path }}</code></td>
                        <td>{{ $option->is_active ? '✓' : '✗' }}</td>
                        <td>{{ Str::of($prompt->template)->replace('{' . '{answer}' . '}', $option->label) }}</td>
                        <td class="actions">
                            <a href="{{ route('admin.options.edit', $option) }}" class="btn btn-sm">Edit</a>
                            <form action="{{ route('admin.options.destroy', $option) }}" method="POST" style="display: inline;">
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


@extends('layouts.admin')

@section('title', 'Prompt Details')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.lessons.manage', $prompt->lesson) }}" class="back-link">&larr; Back to {{ $prompt->lesson->title }}</a>
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
                    <th>TTS Status</th>
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
                        <td>
                            @if(!empty($option->word_audio_path))
                                <span class="badge badge-success">✓ Word</span>
                            @else
                                <span class="badge badge-warning">No Word Audio</span>
                            @endif
                            <br>
                            @if(!empty($option->sentence_audio_path))
                                <span class="badge badge-success">✓ Sentence</span>
                                <br>
                                <a href="{{ asset($option->sentence_audio_path) }}" target="_blank" class="audio-link" title="Play audio">
                                    🔊
                                </a>
                            @else
                                <span class="badge badge-warning">No Sentence Audio</span>
                            @endif
                        </td>
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

@push('styles')
<style>
.badge { display: inline-block; padding: 0.25em 0.6em; border-radius: 0.25rem; font-size: 0.85em; font-weight: 500; }
.badge-success { background-color: #d4edda; color: #155724; }
.badge-warning { background-color: #fff3cd; color: #856404; }
.audio-link { display: inline-block; margin-top: 0.25rem; font-size: 1.2em; text-decoration: none; opacity: 0.8; transition: opacity 0.2s; }
.audio-link:hover { opacity: 1; }
</style>
@endpush


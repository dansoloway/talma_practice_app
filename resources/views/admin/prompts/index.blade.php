@extends('layouts.admin')

@section('title', 'Prompts - ' . $lesson->title)

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="back-link">&larr; Back to Lesson</a>
            <h1 class="page-title">Prompts</h1>
            <p class="page-subtitle">{{ $lesson->title }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.lessons.prompts.create', $lesson) }}" class="btn btn-primary">+ Add Prompt</a>
            <a href="{{ route('admin.lessons.prompts.import', $lesson) }}" class="btn btn-secondary">Import CSV</a>
        </div>
    </div>

    @if($prompts->count() === 0)
        <div class="empty-state">
            <p>No prompts yet. <a href="{{ route('admin.lessons.prompts.create', $lesson) }}">Create the first prompt</a> or <a href="{{ route('admin.lessons.prompts.import', $lesson) }}">import from CSV</a>.</p>
        </div>
    @else
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Prompt Text</th>
                        <th>Template</th>
                        <th>TTS</th>
                        <th>Correct</th>
                        <th>Options</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($prompts as $idx => $prompt)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>{{ $prompt->prompt_text }}</td>
                            <td><code>{{ $prompt->template }}</code></td>
                            <td>
                                @php
                                    $hasSentenceAudio = $prompt->options->contains(function($option) {
                                        return !empty($option->sentence_audio_path);
                                    });
                                    $hasPromptAudio = !empty($prompt->prompt_audio_path);
                                @endphp
                                
                                @if($hasSentenceAudio)
                                    <span class="badge badge-success">✓ Generated</span>
                                    <br>
                                    <span class="text-muted" style="font-size: 0.75em;">{{ $prompt->options->where('sentence_audio_path', '!=', null)->count() }}/{{ $prompt->options->count() }} sentences</span>
                                @elseif($hasPromptAudio)
                                    <span class="badge badge-success">✓ Generated</span>
                                    <br>
                                    <a href="{{ asset($prompt->prompt_audio_path) }}" target="_blank" class="audio-link" title="Play audio">
                                        🔊
                                    </a>
                                @else
                                    <span class="badge badge-warning">Not Generated</span>
                                @endif
                            </td>
                            <td>
                                @if($prompt->correct_answer)
                                    {{ $prompt->correct_answer }}
                                    @php $ci = $prompt->correct_answer - 1; @endphp
                                    @if(isset($prompt->options[$ci]))
                                        — "{{ $prompt->options[$ci]->label }}"
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($prompt->options->count() === 0)
                                    0
                                @else
                                    {{ $prompt->options->count() }}
                                    <div class="option-chips">
                                        @foreach($prompt->options as $o)
                                            <span class="chip">{{ $o->label }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td>{{ $prompt->is_active ? 'Yes' : 'No' }}</td>
                            <td>
                                <a href="{{ route('admin.prompts.show', $prompt) }}" class="btn btn-xs">View</a>
                                <a href="{{ route('admin.prompts.edit', $prompt) }}" class="btn btn-xs">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
.page-subtitle { color: var(--color-gray-600); margin: 0; }
.table-container { overflow-x: auto; }
table.table th, table.table td { vertical-align: top; }
code { background: var(--color-gray-100); padding: 0.1em 0.3em; border-radius: 4px; }
.option-chips { margin-top: 0.25rem; display: flex; flex-wrap: wrap; gap: 0.25rem; }
.chip { background: var(--color-gray-200); padding: 0.1rem 0.4rem; border-radius: 999px; font-size: 0.8rem; }
.badge { display: inline-block; padding: 0.25em 0.6em; border-radius: 0.25rem; font-size: 0.85em; font-weight: 500; }
.badge-success { background-color: #d4edda; color: #155724; }
.badge-warning { background-color: #fff3cd; color: #856404; }
.audio-link { display: inline-block; margin-top: 0.25rem; font-size: 1.2em; text-decoration: none; opacity: 0.8; transition: opacity 0.2s; }
.audio-link:hover { opacity: 1; }
</style>
@endpush



@extends('layouts.admin')

@section('title', 'Edit Prompt')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Edit Prompt</h1>
        <a href="{{ route('admin.prompts.show', $prompt) }}" class="btn">Cancel</a>
    </div>

    <form action="{{ route('admin.prompts.update', $prompt) }}" method="POST" class="form">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="prompt_text">Prompt Text *</label>
            <input type="text" id="prompt_text" name="prompt_text" value="{{ old('prompt_text', $prompt->prompt_text) }}" required class="form-control">
        </div>

        <div class="form-group">
            <label for="template">Template *</label>
            <input type="text" id="template" name="template" value="{{ old('template', $prompt->template) }}" required class="form-control">
            <small>Use <code>{}</code> as placeholder for the answer</small>
        </div>

        <div class="form-group">
            <label for="tts_voice">TTS Voice</label>
            <input type="text" id="tts_voice" name="tts_voice" value="{{ old('tts_voice', $prompt->tts_voice) }}" class="form-control">
        </div>

        <div class="form-group">
            <label for="correct_answer">Correct Answer</label>
            <select id="correct_answer" name="correct_answer" class="form-control">
                <option value="">— None —</option>
                @foreach($prompt->options as $index => $opt)
                    <option value="{{ $index + 1 }}" {{ (string)old('correct_answer', $prompt->correct_answer) === (string)($index + 1) ? 'selected' : '' }}>
                        {{ $index + 1 }} — {{ $opt->label }}
                    </option>
                @endforeach
            </select>
            <small>Select the 1-based option number that is correct, or leave empty.</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Prompt</button>
            <a href="{{ route('admin.prompts.show', $prompt) }}" class="btn">Cancel</a>
        </div>
    </form>
</div>
@endsection


@extends('layouts.admin')

@section('title', 'Create Prompt')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Create Prompt for {{ $lesson->title }}</h1>
        <a href="{{ route('admin.lessons.show', $lesson) }}" class="btn">Cancel</a>
    </div>

    <form action="{{ route('admin.lessons.prompts.store', $lesson) }}" method="POST" class="form">
        @csrf

        <div class="form-group">
            <label for="prompt_text">Prompt Text *</label>
            <input type="text" id="prompt_text" name="prompt_text" value="{{ old('prompt_text') }}" required class="form-control">
            <small>The question students will see (e.g., "What is your favorite color?")</small>
        </div>

        <div class="form-group">
            <label for="template">Template *</label>
            <input type="text" id="template" name="template" value="{{ old('template') }}" required class="form-control">
            <small>Use @{{ '{' }}{{ '{answer}' }}{{ '}' }} as placeholder (e.g., "My favorite color is @{{ '{' }}{{ '{answer}' }}{{ '}' }}.")</small>
        </div>

        <div class="form-group">
            <label for="tts_voice">TTS Voice</label>
            <input type="text" id="tts_voice" name="tts_voice" value="{{ old('tts_voice', 'default') }}" class="form-control">
            <small>ElevenLabs voice ID to use for this prompt</small>
        </div>

        <div class="form-group">
            <label for="sort_order">Sort Order</label>
            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-control">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Prompt</button>
            <a href="{{ route('admin.lessons.show', $lesson) }}" class="btn">Cancel</a>
        </div>
    </form>
</div>
@endsection


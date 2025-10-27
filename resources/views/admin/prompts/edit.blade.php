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
            <label for="sort_order">Sort Order</label>
            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $prompt->sort_order) }}" class="form-control">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Prompt</button>
            <a href="{{ route('admin.prompts.show', $prompt) }}" class="btn">Cancel</a>
        </div>
    </form>
</div>
@endsection


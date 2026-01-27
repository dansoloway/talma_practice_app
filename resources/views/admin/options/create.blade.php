@extends('layouts.admin')

@section('title', 'Create Option')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Add Option to "{{ $prompt->prompt_text }}"</h1>
        <a href="{{ route('admin.prompts.show', $prompt) }}" class="btn">Cancel</a>
    </div>

    <div class="info-box">
        <p><strong>Lesson:</strong> {{ $prompt->lesson->title }}</p>
        <p><strong>Template:</strong> <code>{{ $prompt->template }}</code></p>
    </div>

    <form action="{{ route('admin.prompts.options.store', $prompt) }}" method="POST" enctype="multipart/form-data" class="form">
        @csrf

        <div class="form-group">
            <label for="label">Label *</label>
            <input type="text" id="label" name="label" value="{{ old('label') }}" required class="form-control">
            <small>The answer text (e.g., "red", "blue")</small>
        </div>

        <div class="form-group">
            <label for="option_type">Display Type *</label>
            <select id="option_type" name="option_type" class="form-control" onchange="toggleOptionType()">
                <option value="image" {{ old('option_type', 'image') == 'image' ? 'selected' : '' }}>Image</option>
                <option value="text" {{ old('option_type') == 'text' ? 'selected' : '' }}>Text (e.g., Translation)</option>
            </select>
        </div>

        <div class="form-group" id="image_upload_group">
            <label for="image">Upload Image</label>
            <input type="file" id="image" name="image" accept="image/*" class="form-control">
            <small>Upload a new image (JPG, PNG, GIF, SVG - max 2MB)</small>
        </div>

        <div class="form-group" id="image_path_group">
            <label for="image_path">Or Image Path</label>
            <input type="text" id="image_path" name="image_path" value="{{ old('image_path') }}" class="form-control">
            <small>Or use existing path (e.g., "images/colors/red.png")</small>
        </div>

        <div class="form-group" id="option_text_group" style="display: none;">
            <label for="option_text">Text (e.g., Hebrew Translation) *</label>
            <input type="text" id="option_text" name="option_text" value="{{ old('option_text') }}" class="form-control">
            <small>Text to display instead of image (e.g., "אדום" for red in Hebrew)</small>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                Active
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Option</button>
            <a href="{{ route('admin.prompts.show', $prompt) }}" class="btn">Cancel</a>
        </div>
    </form>

    <script>
    function toggleOptionType() {
        const type = document.getElementById('option_type').value;
        const imageUpload = document.getElementById('image_upload_group');
        const imagePath = document.getElementById('image_path_group');
        const optionText = document.getElementById('option_text_group');
        
        if (type === 'image') {
            imageUpload.style.display = 'block';
            imagePath.style.display = 'block';
            optionText.style.display = 'none';
        } else {
            imageUpload.style.display = 'none';
            imagePath.style.display = 'none';
            optionText.style.display = 'block';
        }
    }
    
    // Initialize on page load
    toggleOptionType();
    </script>
</div>
@endsection


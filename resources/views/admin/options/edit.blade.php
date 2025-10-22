@extends('layouts.admin')

@section('title', 'Edit Option')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Edit Option: {{ $option->label }}</h1>
        <a href="{{ route('admin.prompts.show', $option->prompt) }}" class="btn">Cancel</a>
    </div>

    <div class="info-box">
        <p><strong>Lesson:</strong> {{ $option->prompt->lesson->title }}</p>
        <p><strong>Prompt:</strong> {{ $option->prompt->prompt_text }}</p>
    </div>

    <form action="{{ route('admin.options.update', $option) }}" method="POST" enctype="multipart/form-data" class="form">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="label">Label *</label>
            <input type="text" id="label" name="label" value="{{ old('label', $option->label) }}" required class="form-control">
        </div>

        <div class="form-group">
            <label for="option_type">Display Type *</label>
            <select id="option_type" name="option_type" class="form-control" onchange="toggleOptionType()">
                <option value="image" {{ old('option_type', $option->option_type) == 'image' ? 'selected' : '' }}>Image</option>
                <option value="text" {{ old('option_type', $option->option_type) == 'text' ? 'selected' : '' }}>Text (e.g., Translation)</option>
            </select>
        </div>

        <div class="form-group" id="image_upload_group">
            <label for="image">Upload New Image</label>
            <input type="file" id="image" name="image" accept="image/*" class="form-control">
            <small>Upload a new image to replace current (JPG, PNG, GIF, SVG - max 2MB)</small>
            @if($option->image_path && $option->option_type == 'image')
                <div style="margin-top: 10px;">
                    <img src="{{ asset($option->image_path) }}" alt="{{ $option->label }}" style="max-width: 150px; max-height: 150px;">
                </div>
            @endif
        </div>

        <div class="form-group" id="image_path_group">
            <label for="image_path">Or Image Path</label>
            <input type="text" id="image_path" name="image_path" value="{{ old('image_path', $option->image_path) }}" class="form-control">
        </div>

        <div class="form-group" id="option_text_group">
            <label for="option_text">Text (e.g., Hebrew Translation)</label>
            <input type="text" id="option_text" name="option_text" value="{{ old('option_text', $option->option_text) }}" class="form-control">
            <small>Text to display instead of image</small>
        </div>

        <div class="form-group">
            <label for="sort_order">Sort Order</label>
            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $option->sort_order) }}" class="form-control">
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $option->is_active) ? 'checked' : '' }}>
                Active
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Option</button>
            <a href="{{ route('admin.prompts.show', $option->prompt) }}" class="btn">Cancel</a>
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


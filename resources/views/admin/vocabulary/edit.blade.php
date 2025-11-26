@extends('layouts.admin')

@section('title', 'Edit Vocabulary')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Edit Vocabulary: {{ $vocabulary->english_word }}</h1>
        <a href="{{ route('admin.lessons.vocabulary.index', $lesson) }}" class="btn">Cancel</a>
    </div>

    <form action="{{ route('admin.lessons.vocabulary.update', [$lesson, $vocabulary]) }}" method="POST" class="form" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="english_word">English Word *</label>
            <input type="text" id="english_word" name="english_word" value="{{ old('english_word', $vocabulary->english_word) }}" required class="form-control">
        </div>

        <div class="translations-section">
            <h3>Translations</h3>
            
            <div class="form-group">
                <label for="hebrew_translation">
                    Hebrew Translation (עברית)
                </label>
                <input type="text" 
                       id="hebrew_translation" 
                       name="hebrew_translation" 
                       value="{{ old('hebrew_translation', $vocabulary->hebrew_translation) }}" 
                       class="form-control" 
                       placeholder="e.g., משתנה, מסקנה, השערה..."
                       dir="rtl">
                <small>Enter the Hebrew translation of the word</small>
            </div>

            <div class="form-group">
                <label for="arabic_translation">
                    Arabic Translation (عربي)
                </label>
                <input type="text" 
                       id="arabic_translation" 
                       name="arabic_translation" 
                       value="{{ old('arabic_translation', $vocabulary->arabic_translation) }}" 
                       class="form-control" 
                       placeholder="e.g., متغير، استنتاج، فرضية..."
                       dir="rtl">
                <small>Enter the Arabic translation of the word</small>
            </div>
        </div>

        @if($vocabulary->image_path)
            <div class="form-group">
                <label>Current Image</label>
                <div>
                    <img src="{{ $vocabulary->image_url }}" alt="{{ $vocabulary->english_word }}" style="max-width: 200px; height: auto; border-radius: 4px;">
                </div>
            </div>
        @endif

        <div class="form-group">
            <label for="image">New Image</label>
            <input type="file" id="image" name="image" accept="image/*" class="form-control">
            <small>Upload a new image to replace the current one (JPG, PNG, GIF, SVG - max 2MB)</small>
        </div>


        <div class="form-group">
            <label for="sort_order">Sort Order</label>
            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $vocabulary->sort_order) }}" class="form-control">
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $vocabulary->is_active) ? 'checked' : '' }}>
                Active
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Vocabulary</button>
            <a href="{{ route('admin.lessons.vocabulary.index', $lesson) }}" class="btn">Cancel</a>
        </div>
    </form>
</div>

@push('styles')
<style>
.translations-section {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
    margin: 1.5rem 0;
}

.translations-section h3 {
    margin-top: 0;
    margin-bottom: 1rem;
    color: #495057;
    font-size: 1.2rem;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
    padding-bottom: 0.5rem;
}

.translations-section .form-group {
    margin-bottom: 1rem;
}

.translations-section .form-group:last-child {
    margin-bottom: 0;
}

.translations-section input[dir="rtl"] {
    text-align: right;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    font-size: 1rem;
}

.translations-section label {
    font-weight: 500;
    color: #495057;
}
</style>
@endpush
@endsection

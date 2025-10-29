@extends('layouts.admin')

@section('title', 'Add Vocabulary')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Add Vocabulary for: {{ $lesson->title }}</h1>
        <a href="{{ route('admin.lessons.vocabulary.index', $lesson) }}" class="btn">Cancel</a>
    </div>

    <form action="{{ route('admin.lessons.vocabulary.store', $lesson) }}" method="POST" class="form" enctype="multipart/form-data">
        @csrf

        <div class="form-group">
            <label for="english_word">English Word *</label>
            <input type="text" id="english_word" name="english_word" value="{{ old('english_word') }}" required class="form-control" placeholder="e.g., variable, conclusion, hypothesis...">
        </div>

        <div class="form-group">
            <label for="hebrew_translation">Hebrew Translation</label>
            <input type="text" id="hebrew_translation" name="hebrew_translation" value="{{ old('hebrew_translation') }}" class="form-control" placeholder="e.g., משתנה, מסקנה, השערה...">
            <small>Enter the Hebrew translation of the word</small>
        </div>

        <div class="form-group">
            <label for="arabic_translation">Arabic Translation</label>
            <input type="text" id="arabic_translation" name="arabic_translation" value="{{ old('arabic_translation') }}" class="form-control" placeholder="e.g., متغير، استنتاج، فرضية...">
            <small>Enter the Arabic translation of the word</small>
        </div>

        <div class="form-group">
            <label for="image">Image</label>
            <input type="file" id="image" name="image" accept="image/*" class="form-control">
            <small>Upload an image for this vocabulary word (JPG, PNG, GIF, SVG - max 2MB)</small>
        </div>


        <div class="form-group">
            <label for="sort_order">Sort Order</label>
            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-control">
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                Active
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add Vocabulary</button>
            <a href="{{ route('admin.lessons.vocabulary.index', $lesson) }}" class="btn">Cancel</a>
        </div>
    </form>
</div>
@endsection

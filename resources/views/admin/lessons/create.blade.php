@extends('layouts.admin')

@section('title', 'Create Lesson')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Create Lesson</h1>
        <a href="{{ route('admin.lessons.index') }}" class="btn">Cancel</a>
    </div>

    <form action="{{ route('admin.lessons.store') }}" method="POST" class="form">
        @csrf

        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" value="{{ old('title') }}" required class="form-control">
        </div>

        <div class="form-group">
            <label for="slug">Slug (leave blank to auto-generate)</label>
            <input type="text" id="slug" name="slug" value="{{ old('slug') }}" class="form-control">
            <small>URL-friendly identifier (e.g., "colors" for /lessons/colors)</small>
        </div>

        <div class="form-group">
            <label for="grade_level">Grade Level</label>
            <select id="grade_level" name="grade_level" class="form-control">
                <option value="">Select Grade Level</option>
                <option value="K" {{ old('grade_level') == 'K' ? 'selected' : '' }}>Kindergarten (K)</option>
                <option value="1" {{ old('grade_level') == '1' ? 'selected' : '' }}>1st Grade</option>
                <option value="2" {{ old('grade_level') == '2' ? 'selected' : '' }}>2nd Grade</option>
                <option value="3" {{ old('grade_level') == '3' ? 'selected' : '' }}>3rd Grade</option>
                <option value="4" {{ old('grade_level') == '4' ? 'selected' : '' }}>4th Grade</option>
                <option value="5" {{ old('grade_level') == '5' ? 'selected' : '' }}>5th Grade</option>
                <option value="6" {{ old('grade_level') == '6' ? 'selected' : '' }}>6th Grade</option>
                <option value="7" {{ old('grade_level') == '7' ? 'selected' : '' }}>7th Grade</option>
                <option value="8" {{ old('grade_level') == '8' ? 'selected' : '' }}>8th Grade</option>
                <option value="9" {{ old('grade_level') == '9' ? 'selected' : '' }}>9th Grade</option>
                <option value="10" {{ old('grade_level') == '10' ? 'selected' : '' }}>10th Grade</option>
                <option value="11" {{ old('grade_level') == '11' ? 'selected' : '' }}>11th Grade</option>
                <option value="12" {{ old('grade_level') == '12' ? 'selected' : '' }}>12th Grade</option>
            </select>
        </div>

        <div class="form-group">
            <label for="session_number">Session Number</label>
            <input type="number" id="session_number" name="session_number" value="{{ old('session_number') }}" min="1" class="form-control" placeholder="e.g., 1, 2, 3...">
            <small>Optional session number for this lesson</small>
        </div>

        <div class="form-group">
            <label for="session_title">Session Title</label>
            <input type="text" id="session_title" name="session_title" value="{{ old('session_title') }}" class="form-control" placeholder="e.g., Introduction to Colors, Environmental Vocabulary...">
            <small>Optional descriptive title for this session</small>
        </div>

        <div class="form-group">
            <label for="sort_order">Sort Order</label>
            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order') }}" class="form-control" placeholder="Leave blank to add at end">
            <small>Leave blank to automatically add this lesson at the end of its grade level</small>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                Active
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Lesson</button>
            <a href="{{ route('admin.lessons.index') }}" class="btn">Cancel</a>
        </div>
    </form>
</div>
@endsection


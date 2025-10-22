@extends('layouts.admin')

@section('title', 'Create Part')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Create Part for: {{ $lesson->title }}</h1>
        <a href="{{ route('admin.lessons.parts.index', $lesson) }}" class="btn">Cancel</a>
    </div>

    <form action="{{ route('admin.lessons.parts.store', $lesson) }}" method="POST" class="form">
        @csrf

        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" value="{{ old('title') }}" required class="form-control">
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4" class="form-control" placeholder="Optional description for this part...">{{ old('description') }}</textarea>
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
            <button type="submit" class="btn btn-primary">Create Part</button>
            <a href="{{ route('admin.lessons.parts.index', $lesson) }}" class="btn">Cancel</a>
        </div>
    </form>
</div>
@endsection

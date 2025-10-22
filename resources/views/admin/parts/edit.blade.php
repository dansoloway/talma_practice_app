@extends('layouts.admin')

@section('title', 'Edit Part')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Edit Part: {{ $part->title }}</h1>
        <a href="{{ route('admin.lessons.parts.show', [$lesson, $part]) }}" class="btn">Cancel</a>
    </div>

    <form action="{{ route('admin.lessons.parts.update', [$lesson, $part]) }}" method="POST" class="form">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" value="{{ old('title', $part->title) }}" required class="form-control">
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4" class="form-control" placeholder="Optional description for this part...">{{ old('description', $part->description) }}</textarea>
        </div>

        <div class="form-group">
            <label for="sort_order">Sort Order</label>
            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $part->sort_order) }}" class="form-control">
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $part->is_active) ? 'checked' : '' }}>
                Active
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Part</button>
            <a href="{{ route('admin.lessons.parts.show', [$lesson, $part]) }}" class="btn">Cancel</a>
        </div>
    </form>
</div>
@endsection

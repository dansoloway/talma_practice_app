@extends('layouts.admin')

@section('title', 'Create Organization')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Create Organization</h1>
        <a href="{{ route('admin.organizations.index') }}" class="btn">Cancel</a>
    </div>

    <form action="{{ route('admin.organizations.store') }}" method="POST" class="form">
        @csrf

        <div class="form-group">
            <label for="name">Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required class="form-control" placeholder="e.g. We Speak">
        </div>

        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" id="slug" name="slug" value="{{ old('slug') }}" class="form-control" placeholder="e.g. we-speak (auto-generated from name if empty)">
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
        </div>

        <div class="form-group">
            <label for="access_mode">Access Mode *</label>
            <select id="access_mode" name="access_mode" required class="form-control">
                <option value="open" {{ old('access_mode', 'open') === 'open' ? 'selected' : '' }}>Open (public, no sign-in required)</option>
                <option value="restricted" {{ old('access_mode') === 'restricted' ? 'selected' : '' }}>Restricted (sign-in + membership required)</option>
            </select>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                Active
            </label>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="retain_voice_recordings" value="1" {{ old('retain_voice_recordings') ? 'checked' : '' }}>
                Retain anonymized voice recordings for training
            </label>
            <small class="text-gray-500">When enabled, registering students must provide age, gender, and consent. Recordings are stored without user linkage.</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Organization</button>
            <a href="{{ route('admin.organizations.index') }}" class="btn">Cancel</a>
        </div>
    </form>
</div>
@endsection

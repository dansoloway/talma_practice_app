@extends('layouts.admin')

@section('title', 'Edit Organization')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Edit {{ $organization->name }}</h1>
        <a href="{{ route('admin.organizations.index') }}" class="btn">Cancel</a>
    </div>

    <form action="{{ route('admin.organizations.update', $organization) }}" method="POST" class="form">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="name">Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $organization->name) }}" required class="form-control">
        </div>

        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" id="slug" name="slug" value="{{ old('slug', $organization->slug) }}" class="form-control">
            <small class="text-gray-500">Used in URLs: /o/<strong>{{ $organization->slug }}</strong>/</small>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3" class="form-control">{{ old('description', $organization->description) }}</textarea>
        </div>

        <div class="form-group">
            <label for="access_mode">Access Mode *</label>
            <select id="access_mode" name="access_mode" required class="form-control">
                <option value="open" {{ old('access_mode', $organization->access_mode) === 'open' ? 'selected' : '' }}>Open (public, no sign-in required)</option>
                <option value="restricted" {{ old('access_mode', $organization->access_mode) === 'restricted' ? 'selected' : '' }}>Restricted (sign-in + membership required)</option>
            </select>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $organization->is_active) ? 'checked' : '' }}>
                Active
            </label>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="retain_voice_recordings" value="1" {{ old('retain_voice_recordings', $organization->retain_voice_recordings) ? 'checked' : '' }}>
                Retain anonymized voice recordings for training
            </label>
            <small class="text-gray-500">When enabled, registering students must provide age, gender, and consent. Recordings are stored without user linkage.</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Organization</button>
            <a href="{{ route('admin.organizations.index') }}" class="btn">Cancel</a>
        </div>
    </form>
</div>
@endsection

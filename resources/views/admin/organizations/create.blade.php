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
                <input type="checkbox" name="allow_self_registration" value="1" {{ old('allow_self_registration') ? 'checked' : '' }}>
                Allow self-registration
            </label>
            <small class="text-gray-500">When enabled on a restricted org, learners can create accounts at /o/{slug}/register.</small>
        </div>

        <div class="form-group">
            <label for="registration_type">Registration Type *</label>
            <select id="registration_type" name="registration_type" required class="form-control">
                <option value="student" {{ old('registration_type', 'student') === 'student' ? 'selected' : '' }}>Student self-signup (simple form)</option>
                <option value="parent_signup" {{ old('registration_type') === 'parent_signup' ? 'selected' : '' }}>Parent / guardian signup (multi-child form)</option>
            </select>
            <small class="text-gray-500">Parent signup requires restricted access and self-registration. Includes privacy terms acceptance.</small>
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

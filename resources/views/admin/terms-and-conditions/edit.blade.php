@extends('layouts.admin')

@section('title', 'Edit Terms and Conditions')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Edit Terms and Conditions</h1>
        <a href="{{ route('admin.terms-and-conditions.index') }}" class="btn">Back</a>
    </div>

    <form action="{{ route('admin.terms-and-conditions.update', $termsAndCondition) }}" method="POST" class="form">
        @csrf
        @method('PUT')

        <p class="text-sm text-gray-600 mb-4">
            Type: <strong>{{ ucfirst(str_replace('_', ' ', $termsAndCondition->type)) }}</strong>
        </p>

        @if($termsAndCondition->type === 'privacy_policy')
            <p class="text-sm text-gray-600 mb-4">
                Parent signup shows separate <strong>English</strong> and <strong>Hebrew</strong> links. Each opens this document in a modal using the matching language below.
            </p>
        @endif

        <div class="form-group">
            <label for="title">English title *</label>
            <input type="text" id="title" name="title" value="{{ old('title', $termsAndCondition->title) }}" required class="form-control">
        </div>

        <div class="form-group">
            <label for="content">English content *</label>
            <textarea id="content" name="content" rows="18" required class="form-control font-mono text-sm">{{ old('content', $termsAndCondition->content) }}</textarea>
            <small class="text-gray-500">Plain text. Line breaks are preserved in the modal.</small>
        </div>

        @php
            $hebrew = old('translations.he', $termsAndCondition->translations['he'] ?? []);
        @endphp

        <div class="form-group">
            <label for="translations_he_title">Hebrew title</label>
            <input type="text" id="translations_he_title" name="translations[he][title]" value="{{ $hebrew['title'] ?? '' }}" class="form-control" dir="rtl">
        </div>

        <div class="form-group">
            <label for="translations_he_content">Hebrew content</label>
            <textarea id="translations_he_content" name="translations[he][content]" rows="18" class="form-control font-mono text-sm" dir="rtl">{{ $hebrew['content'] ?? '' }}</textarea>
            <small class="text-gray-500">Required for privacy policy Hebrew modal. Plain text; line breaks preserved.</small>
        </div>

        <div class="form-group">
            <label for="version">Version *</label>
            <input type="text" id="version" name="version" value="{{ old('version', $termsAndCondition->version) }}" required class="form-control">
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="active" value="1" {{ old('active', $termsAndCondition->active) ? 'checked' : '' }}>
                Active (shown on registration forms)
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('admin.terms-and-conditions.index') }}" class="btn">Cancel</a>
        </div>
    </form>
</div>
@endsection

@extends('layouts.admin')

@section('title', 'Vocabulary: ' . $vocabulary->english_word)

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">{{ $vocabulary->english_word }}</h1>
        <div class="page-actions">
            <a href="{{ route('admin.lessons.vocabulary.edit', [$lesson, $vocabulary]) }}" class="btn">Edit</a>
            <a href="{{ route('admin.lessons.vocabulary.index', $lesson) }}" class="btn">Back to Vocabulary</a>
        </div>
    </div>

    <div class="vocabulary-details">
        <div class="detail-group">
            <h3>English Word</h3>
            <p><strong>{{ $vocabulary->english_word }}</strong></p>
        </div>

        @if($vocabulary->hebrew_translation)
            <div class="detail-group">
                <h3>Hebrew Translation</h3>
                <p><span class="translation hebrew">{{ $vocabulary->hebrew_translation }}</span></p>
            </div>
        @endif

        @if($vocabulary->arabic_translation)
            <div class="detail-group">
                <h3>Arabic Translation</h3>
                <p><span class="translation arabic">{{ $vocabulary->arabic_translation }}</span></p>
            </div>
        @endif

        @if($vocabulary->image_path)
            <div class="detail-group">
                <h3>Image</h3>
                <img src="{{ $vocabulary->image_url }}" alt="{{ $vocabulary->english_word }}" style="max-width: 300px; height: auto; border-radius: 8px;">
            </div>
        @endif


        <div class="detail-group">
            <h3>Status</h3>
            <span class="status {{ $vocabulary->is_active ? 'active' : 'inactive' }}">
                {{ $vocabulary->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>

        <div class="detail-group">
            <h3>Sort Order</h3>
            <p>{{ $vocabulary->sort_order }}</p>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.translation {
    font-size: 1.1rem;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    display: inline-block;
    font-weight: 500;
}

.translation.hebrew {
    background-color: #e8f4fd;
    color: #1e40af;
    border: 1px solid #bfdbfe;
}

.translation.arabic {
    background-color: #f0fdf4;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.detail-group {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 8px;
    border-left: 4px solid #3b82f6;
}

.detail-group h3 {
    margin-bottom: 0.5rem;
    color: #374151;
    font-size: 1rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
</style>
@endpush

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

        @if($vocabulary->image_path)
            <div class="detail-group">
                <h3>Image</h3>
                <img src="{{ asset($vocabulary->image_path) }}" alt="{{ $vocabulary->english_word }}" style="max-width: 300px; height: auto; border-radius: 8px;">
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

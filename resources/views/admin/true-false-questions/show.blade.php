@extends('layouts.admin')

@section('title', 'View True/False Question')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.lessons.true-false-games.questions.index', [$lesson, $trueFalseGame]) }}" class="back-link">&larr; Back to Questions</a>
            <h1 class="page-title">True/False Question</h1>
            <p class="page-subtitle">{{ $lesson->title }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.lessons.true-false-games.questions.edit', [$lesson, $trueFalseGame, $trueFalseQuestion]) }}" class="btn btn-primary">Edit</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Question Details</h2>
        </div>
        <div class="card-body">
            <div class="detail-row">
                <div class="detail-label">Statement:</div>
                <div class="detail-value">
                    <strong>{{ $trueFalseQuestion->statement }}</strong>
                    @if($trueFalseQuestion->audio_path)
                        <div class="mt-2">
                            <audio controls>
                                <source src="{{ asset($trueFalseQuestion->audio_path) }}" type="audio/mpeg">
                            </audio>
                        </div>
                    @endif
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Correct Answer:</div>
                <div class="detail-value">
                    <span class="badge {{ $trueFalseQuestion->is_true ? 'badge-success' : 'badge-danger' }}">
                        {{ $trueFalseQuestion->is_true ? 'TRUE' : 'FALSE' }}
                    </span>
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Explanation:</div>
                <div class="detail-value">{{ $trueFalseQuestion->explanation }}</div>
            </div>

            @if($trueFalseQuestion->category)
                <div class="detail-row">
                    <div class="detail-label">Category:</div>
                    <div class="detail-value">
                        <span class="badge badge-secondary">{{ ucfirst(str_replace('_', ' ', $trueFalseQuestion->category)) }}</span>
                    </div>
                </div>
            @endif

            <div class="detail-row">
                <div class="detail-label">Status:</div>
                <div class="detail-value">
                    @if($trueFalseQuestion->is_approved)
                        <span class="status active">Approved</span>
                    @else
                        <span class="status pending">Pending Approval</span>
                    @endif
                    @if($trueFalseQuestion->is_active)
                        <span class="status active ml-2">Active</span>
                    @else
                        <span class="status inactive ml-2">Inactive</span>
                    @endif
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Sort Order:</div>
                <div class="detail-value">{{ $trueFalseQuestion->sort_order }}</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Created:</div>
                <div class="detail-value">{{ $trueFalseQuestion->created_at->format('M j, Y g:i A') }}</div>
            </div>

            @if($trueFalseQuestion->updated_at != $trueFalseQuestion->created_at)
                <div class="detail-row">
                    <div class="detail-label">Last Updated:</div>
                    <div class="detail-value">{{ $trueFalseQuestion->updated_at->format('M j, Y g:i A') }}</div>
                </div>
            @endif
        </div>
    </div>

    <div class="form-actions">
        <a href="{{ route('admin.lessons.true-false-games.questions.edit', [$lesson, $trueFalseGame, $trueFalseQuestion]) }}" class="btn btn-primary">Edit Question</a>
        @if(!$trueFalseQuestion->is_approved)
            <form action="{{ route('admin.lessons.true-false-games.questions.approve', [$lesson, $trueFalseGame, $trueFalseQuestion]) }}" method="POST" class="inline-form">
                @csrf
                <button type="submit" class="btn btn-success">Approve</button>
            </form>
        @endif
        <form action="{{ route('admin.lessons.true-false-games.questions.destroy', [$lesson, $trueFalseGame, $trueFalseQuestion]) }}" method="POST" class="inline-form">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this question?')">Delete</button>
        </form>
        <a href="{{ route('admin.lessons.true-false-games.questions.index', [$lesson, $trueFalseGame]) }}" class="btn">Back to Questions</a>
    </div>
</div>

<style>
.card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.card-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #ddd;
    background: #f8f9fa;
}

.card-title {
    margin: 0;
    font-size: 1.25rem;
}

.card-body {
    padding: 1.5rem;
}

.detail-row {
    display: grid;
    grid-template-columns: 150px 1fr;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid #eee;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: 500;
    color: #666;
}

.detail-value {
    color: #333;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-danger {
    background: #f8d7da;
    color: #721c24;
}

.badge-secondary {
    background: #e2e3e5;
    color: #383d41;
}

.status {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 500;
}

.status.active {
    background: #d4edda;
    color: #155724;
}

.status.pending {
    background: #fff3cd;
    color: #856404;
}

.status.inactive {
    background: #f8d7da;
    color: #721c24;
}

.ml-2 {
    margin-left: 0.5rem;
}

.mt-2 {
    margin-top: 0.5rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.inline-form {
    display: inline-block;
}
</style>
@endsection


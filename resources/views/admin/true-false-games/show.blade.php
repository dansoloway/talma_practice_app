@extends('layouts.admin')

@section('title', $trueFalseGame->title . ' - Questions')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.lessons.true-false-games.index', $lesson) }}" class="back-link">&larr; Back to Games</a>
            <h1 class="page-title">{{ $trueFalseGame->title }}</h1>
            <p class="page-subtitle">{{ $lesson->title }} • <span class="badge badge-{{ $trueFalseGame->game_version === 'easy' ? 'success' : ($trueFalseGame->game_version === 'medium' ? 'warning' : 'danger') }}">{{ ucfirst($trueFalseGame->game_version) }}</span></p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.lessons.true-false-games.questions.create', [$lesson, $trueFalseGame]) }}" class="btn btn-primary">+ Create Question</a>
            <a href="{{ route('admin.lessons.true-false-games.edit', [$lesson, $trueFalseGame]) }}" class="btn">Edit Game</a>
            @if($approvedCount > 0)
                <a href="{{ route('true-false-games.play', [$lesson, $trueFalseGame]) }}" class="btn btn-success" target="_blank">Play</a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-value">{{ $questions->count() }}</div>
            <div class="stat-label">Total Questions</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $approvedCount }}</div>
            <div class="stat-label">Approved & Active</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-value">{{ $pendingCount }}</div>
            <div class="stat-label">Pending Approval</div>
        </div>
    </div>

    <!-- Generate Questions Form -->
    <div class="generate-section">
        <h3>Generate Questions with AI</h3>
        <p>Use AI to automatically generate 5-8 vocabulary-focused True/False questions for this {{ $trueFalseGame->game_version }} difficulty game.</p>
        
        <form action="{{ route('admin.lessons.true-false-games.questions.generate', [$lesson, $trueFalseGame]) }}" method="POST" id="generate-form">
            @csrf
            <div class="generate-form-fields">
                <div class="form-group-inline">
                    <label for="count">Number of Questions:</label>
                    <select id="count" name="count" class="form-control-sm" required>
                        <option value="5">5</option>
                        <option value="6" selected>6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                    </select>
                </div>
                
                <div class="form-group-inline">
                    <label class="checkbox-label">
                        <input type="checkbox" name="auto_approve" value="1">
                        <span class="checkmark"></span>
                        Auto-approve (skip review)
                    </label>
                </div>
                
                <div class="form-group-inline">
                    <label class="checkbox-label">
                        <input type="checkbox" name="generate_audio" value="1">
                        <span class="checkmark"></span>
                        Generate audio
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary" id="generate-btn">
                    <i class="fas fa-magic"></i> Generate Questions
                </button>
            </div>
        </form>
        
        <div id="generate-status" style="display: none; margin-top: 1rem;">
            <div class="alert alert-info">
                <i class="fas fa-spinner fa-spin"></i> Generating questions... This may take a moment.
            </div>
        </div>
    </div>

    <!-- Questions List -->
    @if($questions->count() > 0)
        <!-- Pending Questions -->
        @php
            $pendingQuestions = $questions->where('is_approved', false);
        @endphp
        @if($pendingQuestions->count() > 0)
            <div class="questions-section">
                <div class="section-header">
                    <h2>Pending Approval ({{ $pendingQuestions->count() }})</h2>
                    @if($pendingQuestions->count() > 1)
                        <form action="{{ route('admin.lessons.true-false-games.questions.bulk-approve', [$lesson, $trueFalseGame]) }}" method="POST" style="display: inline;">
                            @csrf
                            <input type="hidden" name="question_ids" value="{{ $pendingQuestions->pluck('id')->toJson() }}">
                            <button type="submit" class="btn btn-sm btn-success">Approve All</button>
                        </form>
                    @endif
                </div>
                
                <div class="questions-list">
                    @foreach($pendingQuestions as $question)
                        @include('admin.true-false-questions.question-card', ['question' => $question, 'lesson' => $lesson, 'trueFalseGame' => $trueFalseGame])
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Approved Questions -->
        @php
            $approvedQuestions = $questions->where('is_approved', true);
        @endphp
        @if($approvedQuestions->count() > 0)
            <div class="questions-section">
                <div class="section-header">
                    <h2>Approved Questions ({{ $approvedQuestions->count() }})</h2>
                </div>
                
                <div class="questions-list">
                    @foreach($approvedQuestions as $question)
                        @include('admin.true-false-questions.question-card', ['question' => $question, 'lesson' => $lesson, 'trueFalseGame' => $trueFalseGame])
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <div class="empty-state">
            <h3>No Questions Yet</h3>
            <p>Create your first question manually or use AI to generate questions.</p>
            <a href="{{ route('admin.lessons.true-false-games.questions.create', [$lesson, $trueFalseGame]) }}" class="btn btn-primary">Create Question</a>
        </div>
    @endif
</div>

<script>
document.getElementById('generate-form').addEventListener('submit', function() {
    document.getElementById('generate-status').style.display = 'block';
    document.getElementById('generate-btn').disabled = true;
});
</script>
@endsection

@extends('layouts.admin')

@section('title', 'Sentence Builder Game: ' . $game->title)

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">{{ $game->title }}</h1>
        <div class="page-actions">
            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn">← Back to Lesson</a>
            <a href="{{ route('admin.lessons.sentence-builder-games.edit', [$lesson, $game]) }}" class="btn btn-primary">Edit Game</a>
            <a href="{{ route('sentence-builder-games.play', [$lesson, $game]) }}" class="btn btn-success" target="_blank">Play Game</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <!-- Generate Questions Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Generate Questions with AI</h5>
                </div>
                <div class="card-body">
                    <p>Use AI to automatically generate sentence builder questions from this lesson's content using CEFR A1 level English.</p>
                    
                    <form action="{{ route('admin.lessons.sentence-builder-games.generate', [$lesson, $game]) }}" method="POST" id="generate-form">
                        @csrf
                        <div class="form-group">
                            <label for="count">Number of Questions:</label>
                            <select id="count" name="count" class="form-control" style="max-width: 200px;" required>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                                <option value="6" selected>6</option>
                                <option value="7">7</option>
                                <option value="8">8</option>
                                <option value="9">9</option>
                                <option value="10">10</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" id="generate-btn">
                            <i class="fas fa-magic"></i> Generate Questions
                        </button>
                    </form>
                    
                    <div id="generate-status" style="display: none; margin-top: 1rem;">
                        <div class="alert alert-info">
                            <i class="fas fa-spinner fa-spin"></i> Generating questions... This may take a moment.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questions List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Questions ({{ $questions->count() }})</h5>
                </div>
                <div class="card-body">
                    @if($questions->count() > 0)
                        <div class="questions-list">
                            @foreach($questions as $question)
                                <div class="question-item">
                                    <div class="question-header">
                                        <div>
                                            <strong>Question {{ $loop->iteration }}</strong>
                                            <span class="badge badge-{{ $question->difficulty === 'easy' ? 'success' : ($question->difficulty === 'medium' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($question->difficulty) }}
                                            </span>
                                            @if(!$question->is_active)
                                                <span class="badge badge-secondary">Inactive</span>
                                            @endif
                                        </div>
                                        <div>
                                            <a href="#" class="btn btn-sm btn-outline-primary" onclick="editQuestion({{ $question->id }})">Edit</a>
                                            <form action="{{ route('admin.lessons.sentence-builder-games.delete-question', [$lesson, $game, $question]) }}" 
                                                  method="POST" 
                                                  style="display: inline;"
                                                  onsubmit="return confirm('Are you sure?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="question-content">
                                        <div class="mb-2">
                                            <strong>Correct Sentence:</strong>
                                            <div class="sentence-display">
                                                @foreach($question->correct_sentence as $word)
                                                    <span class="word-badge">{{ $word }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Word Options:</strong>
                                            <div class="sentence-display">
                                                @foreach($question->word_options as $word)
                                                    <span class="word-badge {{ in_array($word, $question->correct_sentence) ? 'correct' : 'distractor' }}">{{ $word }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="mb-0">
                                            <strong>Explanation:</strong>
                                            <p class="mb-0">{{ $question->explanation }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state">
                            <p class="text-muted">No questions yet. Generate questions using AI or add them manually.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Game Details</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Title:</strong>
                        <p class="mb-0">{{ $game->title }}</p>
                    </div>
                    <div class="mb-3">
                        <strong>Questions:</strong>
                        <p class="mb-0">{{ $questions->count() }} total</p>
                    </div>
                    <div class="mb-3">
                        <strong>Status:</strong>
                        <p class="mb-0">
                            <span class="status {{ $game->is_active ? 'active' : 'inactive' }}">
                                {{ $game->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </p>
                    </div>
                    <div class="mb-3">
                        <strong>Sort Order:</strong>
                        <p class="mb-0">{{ $game->sort_order }}</p>
                    </div>
                    <div class="mb-3">
                        <strong>Created:</strong>
                        <p class="mb-0">{{ $game->created_at->format('M d, Y') }}</p>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.lessons.sentence-builder-games.edit', [$lesson, $game]) }}" 
                           class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Game
                        </a>
                        <a href="{{ route('sentence-builder-games.play', [$lesson, $game]) }}" 
                           class="btn btn-success" target="_blank">
                            <i class="fas fa-play"></i> Play Game
                        </a>
                        <form action="{{ route('admin.lessons.sentence-builder-games.destroy', [$lesson, $game]) }}" 
                              method="POST" 
                              onsubmit="return confirm('Are you sure you want to delete this game?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="fas fa-trash"></i> Delete Game
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('generate-form').addEventListener('submit', function() {
    document.getElementById('generate-status').style.display = 'block';
    document.getElementById('generate-btn').disabled = true;
});

function editQuestion(questionId) {
    // TODO: Implement edit modal or redirect
    alert('Edit functionality coming soon!');
}
</script>

<style>
.questions-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.question-item {
    border: 1px solid var(--color-border);
    border-radius: 8px;
    padding: 1rem;
    background: white;
}

.question-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--color-border);
}

.question-content {
    font-size: 0.9rem;
}

.sentence-display {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.word-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: #f0f0f0;
    border-radius: 4px;
    font-weight: 500;
}

.word-badge.correct {
    background: #d4edda;
    color: #155724;
}

.word-badge.distractor {
    background: #fff3cd;
    color: #856404;
}

.empty-state {
    text-align: center;
    padding: 2rem;
}
</style>
@endsection


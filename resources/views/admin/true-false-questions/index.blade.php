@extends('layouts.admin')

@section('title', 'True/False Questions - ' . $lesson->title)

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="back-link">&larr; Back to Lesson</a>
            <h1 class="page-title">True/False Questions</h1>
            <p class="page-subtitle">{{ $lesson->title }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.lessons.true-false-games.questions.create', [$lesson, $trueFalseGame]) }}" class="btn btn-primary">+ Create Question</a>
            <a href="{{ route('admin.lessons.manage', $lesson) }}" class="btn">Back to Lesson</a>
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
        <p>Use AI to automatically generate 5-8 vocabulary-focused True/False questions.</p>
        
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
                    <label for="game_version">Difficulty:</label>
                    <select id="game_version" name="game_version" class="form-control-sm" required>
                        <option value="easy">Easy</option>
                        <option value="medium">Medium</option>
                        <option value="hard">Hard</option>
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

    <!-- Filter Questions -->
    <div class="filter-section" style="margin-bottom: 1.5rem;">
        <form method="GET" action="{{ route('admin.lessons.true-false-games.questions.index', [$lesson, $trueFalseGame]) }}" id="filter-form">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <label for="filter_version" style="margin: 0; font-weight: 500;">Filter by Difficulty:</label>
                <select id="filter_version" name="filter_version" class="form-control-sm" onchange="document.getElementById('filter-form').submit();" style="min-width: 150px;">
                    <option value="">All Difficulties</option>
                    <option value="easy" {{ $filterVersion === 'easy' ? 'selected' : '' }}>Easy ({{ $versionCounts['easy']['total'] ?? 0 }})</option>
                    <option value="medium" {{ $filterVersion === 'medium' ? 'selected' : '' }}>Medium ({{ $versionCounts['medium']['total'] ?? 0 }})</option>
                    <option value="hard" {{ $filterVersion === 'hard' ? 'selected' : '' }}>Hard ({{ $versionCounts['hard']['total'] ?? 0 }})</option>
                </select>
            </div>
        </form>
    </div>

    @if($questions->count() > 0)
        <!-- Pending Questions -->
        @if($pendingCount > 0)
            <div class="section-header">
                <h2>Pending Approval ({{ $pendingCount }})</h2>
                <form action="{{ route('admin.lessons.true-false-games.questions.bulk-approve', [$lesson, $trueFalseGame]) }}" method="POST" id="bulk-approve-form" style="display: none;">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success">Approve Selected</button>
                </form>
            </div>

            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="select-all-pending" onchange="togglePendingSelection()">
                            </th>
                            <th>Statement</th>
                            <th>Answer</th>
                            <th>Difficulty</th>
                            <th>Vocabulary</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($questions->where('is_approved', false) as $question)
                            <tr class="{{ !$question->is_active ? 'inactive' : '' }}">
                                <td>
                                    <input type="checkbox" class="pending-checkbox" value="{{ $question->id }}" onchange="updateBulkApproveButton()">
                                </td>
                                <td>
                                    <strong>{{ Str::limit($question->statement, 80) }}</strong>
                                    @if($question->audio_path)
                                        <span class="badge badge-info" title="Has audio">
                                            <i class="fas fa-volume-up"></i> Audio
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $question->is_true ? 'badge-success' : 'badge-danger' }}">
                                        {{ $question->is_true ? 'TRUE' : 'FALSE' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-secondary">{{ ucfirst($question->game_version) }}</span>
                                </td>
                                <td>
                                    @if($question->vocabulary->isNotEmpty())
                                        @foreach($question->vocabulary->take(3) as $vocab)
                                            <span class="badge badge-info">{{ $vocab->english_word }}</span>
                                        @endforeach
                                        @if($question->vocabulary->count() > 3)
                                            <span class="text-muted">+{{ $question->vocabulary->count() - 3 }}</span>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($question->category)
                                        <span class="badge badge-secondary">{{ $question->category }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="status pending">Pending</span>
                                </td>
                                <td class="actions">
                                    <a href="{{ route('admin.lessons.true-false-games.questions.edit', [$lesson, $trueFalseGame, $question]) }}" class="btn btn-sm">Edit</a>
                                    <form action="{{ route('admin.lessons.true-false-games.questions.approve', [$lesson, $trueFalseGame, $question]) }}" method="POST" class="inline-form">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <form action="{{ route('admin.lessons.true-false-games.questions.destroy', [$lesson, $trueFalseGame, $question]) }}" method="POST" class="inline-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this question?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Approved Questions -->
        <div class="section-header">
            <h2>Approved Questions ({{ $approvedCount }})</h2>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Statement</th>
                        <th>Answer</th>
                        <th>Difficulty</th>
                        <th>Vocabulary</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Sort Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($questions->where('is_approved', true)->sortBy('sort_order') as $question)
                        <tr class="{{ !$question->is_active ? 'inactive' : '' }}">
                            <td>
                                <strong>{{ Str::limit($question->statement, 80) }}</strong>
                                @if($question->audio_path)
                                    <span class="badge badge-info" title="Has audio">
                                        <i class="fas fa-volume-up"></i> Audio
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $question->is_true ? 'badge-success' : 'badge-danger' }}">
                                    {{ $question->is_true ? 'TRUE' : 'FALSE' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-secondary">{{ ucfirst($question->game_version) }}</span>
                            </td>
                            <td>
                                @if($question->vocabulary->isNotEmpty())
                                    @foreach($question->vocabulary->take(3) as $vocab)
                                        <span class="badge badge-info">{{ $vocab->english_word }}</span>
                                    @endforeach
                                    @if($question->vocabulary->count() > 3)
                                        <span class="text-muted">+{{ $question->vocabulary->count() - 3 }}</span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($question->category)
                                    <span class="badge badge-secondary">{{ $question->category }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="status {{ $question->is_active ? 'active' : 'inactive' }}">
                                    {{ $question->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $question->sort_order }}</td>
                            <td class="actions">
                                <a href="{{ route('admin.lessons.true-false-games.questions.show', [$lesson, $trueFalseGame, $question]) }}" class="btn btn-sm">View</a>
                                <a href="{{ route('admin.lessons.true-false-games.questions.edit', [$lesson, $trueFalseGame, $question]) }}" class="btn btn-sm">Edit</a>
                                <form action="{{ route('admin.lessons.true-false-games.questions.destroy', [$lesson, $trueFalseGame, $question]) }}" method="POST" class="inline-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this question?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <h3>No True/False Questions Yet</h3>
            <p>Create questions manually or generate them using AI.</p>
            <div class="empty-state-actions">
                <a href="{{ route('admin.lessons.true-false-games.questions.create', [$lesson, $trueFalseGame]) }}" class="btn btn-primary">Create Question</a>
            </div>
        </div>
    @endif
</div>

<style>
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
}

.stat-card.warning {
    border-color: #ffc107;
    background: #fffbf0;
}

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    color: var(--color-primary);
}

.stat-card.warning .stat-value {
    color: #ffc107;
}

.stat-label {
    font-size: 0.875rem;
    color: #666;
    margin-top: 0.5rem;
}

.generate-section {
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.generate-section h3 {
    margin-top: 0;
    margin-bottom: 0.5rem;
}

.generate-section p {
    margin-bottom: 1rem;
    color: #666;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 2rem 0 1rem 0;
}

.section-header h2 {
    margin: 0;
    font-size: 1.5rem;
}

.status.pending {
    background: #ffc107;
    color: #000;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status.active {
    background: #d4edda;
    color: #155724;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status.inactive {
    background: #e2e3e5;
    color: #383d41;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.inline-form {
    display: inline-block;
    margin-left: 0.5rem;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
    margin-right: 0.25rem;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-danger {
    background: #f8d7da;
    color: #721c24;
}

.badge-info {
    background: #d1ecf1;
    color: #0c5460;
}

.badge-secondary {
    background: #e2e3e5;
    color: #383d41;
}

tr.inactive {
    opacity: 0.6;
}

.generate-form-fields {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    align-items: center;
    margin-top: 1rem;
}

.form-group-inline {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-group-inline label {
    margin: 0;
    font-weight: normal;
}

.form-control-sm {
    padding: 0.375rem 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.875rem;
}

#generate-btn {
    margin-left: auto;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    margin: 0;
}

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: var(--shadow-md);
}

.empty-state h3 {
    color: var(--color-text);
    margin-bottom: 1rem;
}

.empty-state-actions {
    margin-top: 1.5rem;
}
</style>

<script>
function togglePendingSelection() {
    const selectAll = document.getElementById('select-all-pending');
    const checkboxes = document.querySelectorAll('.pending-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateBulkApproveButton();
}

function updateBulkApproveButton() {
    const checked = document.querySelectorAll('.pending-checkbox:checked');
    const form = document.getElementById('bulk-approve-form');
    const button = form.querySelector('button');
    
    if (checked.length > 0) {
        const ids = Array.from(checked).map(cb => parseInt(cb.value));
        const existingInputs = form.querySelectorAll('input[name="question_ids[]"]');
        existingInputs.forEach(input => input.remove());
        
        ids.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'question_ids[]';
            input.value = id;
            form.appendChild(input);
        });
        
        form.style.display = 'inline-block';
        button.textContent = `Approve Selected (${checked.length})`;
    } else {
        form.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('generate-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const btn = document.getElementById('generate-btn');
            const status = document.getElementById('generate-status');
            
            if (btn) btn.disabled = true;
            if (btn) btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            if (status) status.style.display = 'block';
        });
    }
});
</script>
@endsection

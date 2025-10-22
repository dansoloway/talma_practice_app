@extends('layouts.admin')

@section('title', 'Manage Lesson: ' . $lesson->title)

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Manage Lesson: {{ $lesson->title }}</h1>
        <a href="{{ route('admin.lessons.index') }}" class="btn">Back to Lessons</a>
    </div>

    <!-- Lesson Details Section -->
    <div class="management-section">
        <div class="section-header">
            <h2>Lesson Details</h2>
            <button id="edit-lesson-btn" class="btn btn-sm">Edit Details</button>
        </div>
        
        <div class="lesson-info" id="lesson-info">
            <div class="info-grid">
                <div class="info-item">
                    <label>Title:</label>
                    <span id="lesson-title">{{ $lesson->title }}</span>
                </div>
                <div class="info-item">
                    <label>Slug:</label>
                    <span id="lesson-slug">{{ $lesson->slug }}</span>
                </div>
                <div class="info-item">
                    <label>Grade Level:</label>
                    <span id="lesson-grade">{{ $lesson->grade_level ?: 'Not set' }}</span>
                </div>
                <div class="info-item">
                    <label>Session Number:</label>
                    <span id="lesson-session">{{ $lesson->session_number ?: 'Not set' }}</span>
                </div>
                <div class="info-item">
                    <label>Session Title:</label>
                    <span id="lesson-session-title">{{ $lesson->session_title ?: 'Not set' }}</span>
                </div>
                <div class="info-item">
                    <label>Status:</label>
                    <span class="status {{ $lesson->is_active ? 'active' : 'inactive' }}">
                        {{ $lesson->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
            
            @if($lesson->instructions)
                <div class="info-item full-width">
                    <label>Instructions:</label>
                    <p id="lesson-instructions">{{ $lesson->instructions }}</p>
                </div>
            @endif
        </div>

        <!-- Edit Form (Hidden by default) -->
        <div class="edit-form hidden" id="lesson-edit-form">
            <form action="{{ route('admin.lessons.update', $lesson) }}" method="POST" class="form">
                @csrf
                @method('PUT')
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" id="title" name="title" value="{{ $lesson->title }}" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="slug">Slug *</label>
                        <input type="text" id="slug" name="slug" value="{{ $lesson->slug }}" required class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="grade_level">Grade Level</label>
                        <select id="grade_level" name="grade_level" class="form-control">
                            <option value="">Select Grade Level</option>
                            <option value="K" {{ $lesson->grade_level == 'K' ? 'selected' : '' }}>Kindergarten (K)</option>
                            <option value="1" {{ $lesson->grade_level == '1' ? 'selected' : '' }}>1st Grade</option>
                            <option value="2" {{ $lesson->grade_level == '2' ? 'selected' : '' }}>2nd Grade</option>
                            <option value="3" {{ $lesson->grade_level == '3' ? 'selected' : '' }}>3rd Grade</option>
                            <option value="4" {{ $lesson->grade_level == '4' ? 'selected' : '' }}>4th Grade</option>
                            <option value="5" {{ $lesson->grade_level == '5' ? 'selected' : '' }}>5th Grade</option>
                            <option value="6" {{ $lesson->grade_level == '6' ? 'selected' : '' }}>6th Grade</option>
                            <option value="7" {{ $lesson->grade_level == '7' ? 'selected' : '' }}>7th Grade</option>
                            <option value="8" {{ $lesson->grade_level == '8' ? 'selected' : '' }}>8th Grade</option>
                            <option value="9" {{ $lesson->grade_level == '9' ? 'selected' : '' }}>9th Grade</option>
                            <option value="10" {{ $lesson->grade_level == '10' ? 'selected' : '' }}>10th Grade</option>
                            <option value="11" {{ $lesson->grade_level == '11' ? 'selected' : '' }}>11th Grade</option>
                            <option value="12" {{ $lesson->grade_level == '12' ? 'selected' : '' }}>12th Grade</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="session_number">Session Number</label>
                        <input type="number" id="session_number" name="session_number" value="{{ $lesson->session_number }}" min="1" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label for="session_title">Session Title</label>
                    <input type="text" id="session_title" name="session_title" value="{{ $lesson->session_title }}" class="form-control">
                </div>

                <div class="form-group">
                    <label for="instructions">Instructions for Students</label>
                    <textarea id="instructions" name="instructions" rows="4" class="form-control">{{ $lesson->instructions }}</textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="sort_order">Sort Order</label>
                        <input type="number" id="sort_order" name="sort_order" value="{{ $lesson->sort_order }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_active" value="1" {{ $lesson->is_active ? 'checked' : '' }}>
                            Active
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <button type="button" id="cancel-edit-btn" class="btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Vocabulary Section -->
    <div class="management-section">
        <div class="section-header">
            <h2>Vocabulary ({{ $lesson->vocabulary->count() }})</h2>
            <div class="section-actions">
                <a href="{{ route('admin.lessons.vocabulary.create', $lesson) }}" class="btn btn-primary btn-sm">Add Vocabulary</a>
                <a href="{{ route('admin.lessons.vocabulary.csv.upload', $lesson) }}" class="btn btn-secondary btn-sm">Upload CSV</a>
                <a href="{{ route('admin.lessons.vocabulary.auto-images', $lesson) }}" class="btn btn-success btn-sm">🔍 Auto-Find Images</a>
            </div>
        </div>

        @if($lesson->vocabulary->count() > 0)
            <div class="vocabulary-grid">
                @foreach($lesson->vocabulary as $vocab)
                    <div class="vocabulary-card">
                        @if($vocab->image_path)
                            <img src="{{ asset('storage/' . $vocab->image_path) }}" alt="{{ $vocab->english_word }}" class="vocab-image">
                        @endif
                        <div class="vocab-content">
                            <h4>{{ $vocab->english_word }}</h4>
                            <div class="vocab-actions">
                                <a href="{{ route('admin.lessons.vocabulary.edit', [$lesson, $vocab]) }}" class="btn btn-sm">Edit</a>
                                <form action="{{ route('admin.lessons.vocabulary.destroy', [$lesson, $vocab]) }}" method="POST" class="inline-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this vocabulary item?')">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <p>No vocabulary items yet. <a href="{{ route('admin.lessons.vocabulary.create', $lesson) }}">Add the first vocabulary item</a>.</p>
            </div>
        @endif
    </div>

    <!-- Parts Section -->
    <div class="management-section">
        <div class="section-header">
            <h2>Parts ({{ $lesson->parts->count() }})</h2>
            <a href="{{ route('admin.lessons.parts.create', $lesson) }}" class="btn btn-primary btn-sm">Add Part</a>
        </div>

        @if($lesson->parts->count() > 0)
            <div class="parts-list">
                @foreach($lesson->parts as $part)
                    <div class="part-card">
                        <div class="part-header">
                            <h4>{{ $part->title }}</h4>
                            <span class="part-status {{ $part->is_active ? 'active' : 'inactive' }}">
                                {{ $part->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        @if($part->description)
                            <p class="part-description">{{ $part->description }}</p>
                        @endif
                        <div class="part-stats">
                            <span>{{ $part->prompts->count() }} prompts</span>
                            <span>Sort: {{ $part->sort_order }}</span>
                        </div>
                        <div class="part-actions">
                            <a href="{{ route('admin.lessons.parts.show', [$lesson, $part]) }}" class="btn btn-sm">View</a>
                            <a href="{{ route('admin.lessons.parts.edit', [$lesson, $part]) }}" class="btn btn-sm">Edit</a>
                            <form action="{{ route('admin.lessons.parts.destroy', [$lesson, $part]) }}" method="POST" class="inline-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this part?')">Delete</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <p>No parts yet. <a href="{{ route('admin.lessons.parts.create', $lesson) }}">Create the first part</a>.</p>
            </div>
        @endif
    </div>

    <!-- Matching Games Section -->
    <div class="management-section">
        <div class="section-header">
            <h2>Matching Games ({{ $lesson->matchingGames->count() }})</h2>
            <div class="section-actions">
                <a href="{{ route('admin.lessons.matching-games.create', $lesson) }}" class="btn btn-primary btn-sm">Create Matching Game</a>
            </div>
        </div>

        @if($lesson->matchingGames->count() > 0)
            <div class="matching-games-grid">
                @foreach($lesson->matchingGames as $game)
                    <div class="matching-game-card">
                        <div class="game-header">
                            <h3>{{ $game->title }}</h3>
                            <span class="game-status {{ $game->is_active ? 'active' : 'inactive' }}">
                                {{ $game->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="game-info">
                            <div class="game-stat">
                                <span class="stat-label">Grid:</span>
                                <span class="stat-value">{{ $game->grid_size }}x{{ $game->grid_size }}</span>
                            </div>
                            <div class="game-stat">
                                <span class="stat-label">Words:</span>
                                <span class="stat-value">{{ count($game->vocabulary_ids) }}</span>
                            </div>
                        </div>
                        <div class="game-actions">
                            <a href="{{ route('admin.lessons.matching-games.show', [$lesson, $game]) }}" class="btn btn-sm">View</a>
                            <a href="{{ route('admin.lessons.matching-games.edit', [$lesson, $game]) }}" class="btn btn-sm">Edit</a>
                            <a href="{{ route('matching-games.play', [$lesson, $game]) }}" class="btn btn-sm btn-success" target="_blank">Play</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="empty-text">No matching games yet. Create vocabulary matching games to help students learn!</p>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBtn = document.getElementById('edit-lesson-btn');
    const cancelBtn = document.getElementById('cancel-edit-btn');
    const lessonInfo = document.getElementById('lesson-info');
    const editForm = document.getElementById('lesson-edit-form');

    editBtn.addEventListener('click', function() {
        lessonInfo.classList.add('hidden');
        editForm.classList.remove('hidden');
    });

    cancelBtn.addEventListener('click', function() {
        lessonInfo.classList.remove('hidden');
        editForm.classList.add('hidden');
    });
});
</script>
@endsection

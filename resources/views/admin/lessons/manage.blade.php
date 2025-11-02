@extends('layouts.admin')

@section('title', 'Manage Lesson: ' . $lesson->title)

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Manage Lesson: {{ $lesson->title }}</h1>
            <div class="lesson-metadata">
                @if($lesson->grade_level)
                    <span class="metadata-item">Grade {{ $lesson->grade_level }}</span>
                @endif
                @if($lesson->session_number)
                    <span class="metadata-item">Session {{ $lesson->session_number }}</span>
                @endif
                @if($lesson->session_title)
                    <span class="metadata-item">"{{ $lesson->session_title }}"</span>
                @endif
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.lessons.index') }}" class="btn">Back to Lessons</a>
            <button onclick="archiveLesson()" class="btn btn-warning">Archive Lesson</button>
        </div>
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
                <a href="{{ route('admin.lessons.vocabulary.index', $lesson) }}" class="btn btn-primary btn-sm">Edit Vocabulary</a>
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

    <!-- Activities Section -->
    <div class="management-section">
        <div class="section-header">
            <h2>Activities</h2>
            <div class="section-actions">
                <a href="{{ route('admin.lessons.matching-games.create', $lesson) }}" class="btn btn-primary btn-sm">+ Matching</a>
                <a href="{{ route('admin.lessons.flashcard-games.create', $lesson) }}" class="btn btn-primary btn-sm">+ Flashcard</a>
            </div>
        </div>

        @php
            // Collect all activities with their sort orders
            $allActivities = collect();
            
            // Add prompts as a single group if there are any
            if($lesson->prompts->count() > 0) {
                $activePrompts = $lesson->prompts->where('is_active', true);
                $minSortOrder = $lesson->prompts->min('sort_order') ?? 999;
                
                $allActivities->push((object)[
                    'id' => 'prompts',
                    'type' => 'prompts',
                    'title' => 'Prompts (' . $lesson->prompts->count() . ')',
                    'sort_order' => $minSortOrder,
                    'is_active' => $activePrompts->count() > 0,
                    'model' => $lesson->prompts,
                    'count' => $lesson->prompts->count(),
                    'active_count' => $activePrompts->count()
                ]);
            }
            
            // Add matching games
            foreach($lesson->matchingGames as $game) {
                $allActivities->push((object)[
                    'id' => $game->id,
                    'type' => 'matching',
                    'title' => $game->title,
                    'sort_order' => $game->sort_order ?? 999,
                    'is_active' => $game->is_active ?? true,
                    'model' => $game
                ]);
            }
            
            // Add flashcard games
            foreach($lesson->flashcardGames as $game) {
                $allActivities->push((object)[
                    'id' => $game->id,
                    'type' => 'flashcard',
                    'title' => $game->title,
                    'sort_order' => $game->sort_order ?? 999,
                    'is_active' => $game->is_active ?? true,
                    'model' => $game
                ]);
            }
            
            // Sort by sort_order
            $allActivities = $allActivities->sortBy('sort_order');
        @endphp

        @if($allActivities->count() > 0)
            <div class="activities-list" id="activities-list">
                @foreach($allActivities as $index => $activity)
                    <div class="activity-item" data-type="{{ $activity->type }}" data-id="{{ $activity->id }}" data-order="{{ $index + 1 }}">
                        <div class="activity-handle">
                            <i class="fas fa-grip-vertical"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-header">
                                <span class="activity-type-badge {{ $activity->type }}">
                                    {{ ucfirst($activity->type) }}
                                </span>
                                <h4 class="activity-title">{{ $activity->title }}</h4>
                                <span class="activity-status {{ $activity->is_active ? 'active' : 'inactive' }}">
                                    {{ $activity->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div class="activity-order">
                                Order: {{ $index + 1 }}
                            </div>
                        </div>
                        <div class="activity-actions">
                            @if($activity->type === 'prompts')
                                <a href="{{ route('admin.lessons.prompts.index', $lesson) }}" class="btn btn-xs">Edit</a>
                                @if($activity->count > 0)
                                    <a href="{{ route('prompts.play', $lesson) }}" class="btn btn-xs btn-success" target="_blank">Play</a>
                                    <button class="btn btn-xs btn-danger" onclick="deleteAllPrompts('{{ addslashes($activity->title) }}')">Delete All</button>
                                @endif
                            @elseif($activity->type === 'matching')
                                <a href="{{ route('admin.lessons.matching-games.edit', [$lesson, $activity->model]) }}" class="btn btn-xs">Edit</a>
                                <a href="{{ route('matching-games.play', [$lesson, $activity->model]) }}" class="btn btn-xs btn-success" target="_blank">Play</a>
                                <button class="btn btn-xs btn-danger delete-activity-btn" data-type="{{ $activity->type }}" data-id="{{ $activity->id }}" data-title="{{ addslashes($activity->title) }}">Delete</button>
                            @elseif($activity->type === 'flashcard')
                                <a href="{{ route('admin.lessons.flashcard-games.edit', [$lesson, $activity->model]) }}" class="btn btn-xs">Edit</a>
                                <a href="{{ route('flashcard-games.play', [$lesson, $activity->model]) }}" class="btn btn-xs btn-success" target="_blank">Play</a>
                                <button class="btn btn-xs btn-danger delete-activity-btn" data-type="{{ $activity->type }}" data-id="{{ $activity->id }}" data-title="{{ addslashes($activity->title) }}">Delete</button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="activity-order-controls">
                <button class="btn btn-primary" onclick="saveActivityOrder()">Save Order</button>
                <span class="order-status" id="order-status"></span>
            </div>
        @else
            <div class="empty-state">
                <p>No activities yet. Create your first activity using the buttons above.</p>
            </div>
        @endif
    </div>
</div>

<span id="start-tts-flag" data-start="0" style="display:none;"></span>

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
    // Bind delete activity buttons
    document.querySelectorAll('.delete-activity-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const type = this.getAttribute('data-type');
            const id = this.getAttribute('data-id');
            const title = this.getAttribute('data-title');
            deleteActivity(type, id, title);
        });
    });
});

// Activity ordering functions
function saveActivityOrder() {
    const activities = [];
    const activityItems = document.querySelectorAll('.activity-item');
    
    activityItems.forEach((item, index) => {
        const activityId = item.dataset.id;
        activities.push({
            type: item.dataset.type,
            id: activityId === 'prompts' ? 'prompts' : parseInt(activityId),
            sort_order: index + 1
        });
    });
    
    const statusElement = document.getElementById('order-status');
    statusElement.textContent = 'Saving...';
    
    // Make AJAX request to save activity order
    fetch(`/admin/lessons/{{ $lesson->id }}/update-activity-order`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            activities: activities
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusElement.textContent = 'Order saved!';
            statusElement.style.color = 'green';
            setTimeout(() => {
                statusElement.textContent = '';
            }, 3000);
        } else {
            statusElement.textContent = 'Error saving order';
            statusElement.style.color = 'red';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        statusElement.textContent = 'Error saving order';
        statusElement.style.color = 'red';
    });
}

// Make activities sortable (simple drag and drop)
document.addEventListener('DOMContentLoaded', function() {
    const activitiesList = document.getElementById('activities-list');
    if (activitiesList) {
        // Simple drag and drop implementation
        let draggedElement = null;
        
        activitiesList.addEventListener('dragstart', function(e) {
            if (e.target.closest('.activity-item')) {
                draggedElement = e.target.closest('.activity-item');
                e.dataTransfer.effectAllowed = 'move';
                draggedElement.style.opacity = '0.5';
            }
        });
        
        activitiesList.addEventListener('dragend', function(e) {
            if (draggedElement) {
                draggedElement.style.opacity = '';
                draggedElement = null;
            }
        });
        
        activitiesList.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });
        
        activitiesList.addEventListener('drop', function(e) {
            e.preventDefault();
            if (draggedElement) {
                const dropTarget = e.target.closest('.activity-item');
                if (dropTarget && dropTarget !== draggedElement) {
                    const rect = dropTarget.getBoundingClientRect();
                    const midpoint = rect.top + rect.height / 2;
                    
                    if (e.clientY < midpoint) {
                        activitiesList.insertBefore(draggedElement, dropTarget);
                    } else {
                        activitiesList.insertBefore(draggedElement, dropTarget.nextSibling);
                    }
                    
                    // Update order numbers
                    updateOrderNumbers();
                }
            }
        });
        
        // Make activity items draggable
        activitiesList.querySelectorAll('.activity-item').forEach(item => {
            item.draggable = true;
        });
    }
});

function updateOrderNumbers() {
    const activityItems = document.querySelectorAll('.activity-item');
    activityItems.forEach((item, index) => {
        const orderElement = item.querySelector('.activity-order');
        if (orderElement) {
            orderElement.textContent = `Order: ${index + 1}`;
        }
        item.dataset.order = index + 1;
    });
}

// Delete activity function
function deleteActivity(type, activityId, title) {
    if (!confirm(`Are you sure you want to delete "${title}"? This action cannot be undone.`)) {
        return;
    }
    
    // Make AJAX request to delete activity
    fetch(`/admin/lessons/{{ $lesson->id }}/delete-activity`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            activity_type: type,
            activity_id: parseInt(activityId)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Refresh to show updated list
        } else {
            alert('Error deleting activity: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting activity');
    });
}

function archiveLesson() {
    if (confirm('Are you sure you want to archive this lesson? Students will no longer be able to access it, but it can be restored from the archived lessons page.')) {
        document.getElementById('archive-form').submit();
    }
}

// Delete all prompts function
function deleteAllPrompts(title) {
    if (!confirm(`Are you sure you want to delete all prompts in "${title}"? This action cannot be undone.`)) {
        return;
    }
    
    // Make AJAX request to delete all prompts
    fetch(`/admin/lessons/{{ $lesson->id }}/delete-activity`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            activity_type: 'prompts',
            activity_id: 'all'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Refresh to show updated list
        } else {
            alert('Error deleting prompts: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting prompts');
    });
}
</script>

<!-- Hidden form for archiving -->
<form id="archive-form" action="{{ route('admin.lessons.archive', $lesson) }}" method="POST" style="display: none;">
    @csrf
</form>

@endsection

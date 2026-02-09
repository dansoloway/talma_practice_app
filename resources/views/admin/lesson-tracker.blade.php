@extends('layouts.admin')

@section('title', 'Lesson Tracker')

@section('content')
<div class="container">
    <h1 class="page-title">Lesson Tracker</h1>

    <!-- Filters -->
    <div class="dashboard-section" style="margin-bottom: 2rem;">
        <h2>Filters</h2>
        <form method="GET" action="{{ route('admin.lesson-tracker') }}" class="filters-form">
            <div class="filters-horizontal">
                <div class="filter-group">
                    <label for="assigned_to">Assigned To</label>
                    <select name="assigned_to" id="assigned_to" class="form-control">
                        <option value="">All</option>
                        <option value="Unassigned" {{ $assignedTo === 'Unassigned' ? 'selected' : '' }}>Unassigned</option>
                        <option value="Leila" {{ $assignedTo === 'Leila' ? 'selected' : '' }}>Leila</option>
                        <option value="Jen" {{ $assignedTo === 'Jen' ? 'selected' : '' }}>Jen</option>
                        <option value="Daniel" {{ $assignedTo === 'Daniel' ? 'selected' : '' }}>Daniel</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="grade_level">Grade Level</label>
                    <select name="grade_level" id="grade_level" class="form-control">
                        <option value="">All Grades</option>
                        @foreach($gradeLevels as $grade)
                            <option value="{{ $grade }}" {{ $gradeLevel == $grade ? 'selected' : '' }}>
                                Grade {{ $grade }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label for="session_number">Session Number</label>
                    <select name="session_number" id="session_number" class="form-control">
                        <option value="">All Sessions</option>
                        @foreach($sessionNumbers as $session)
                            <option value="{{ $session }}" {{ $sessionNumber == $session ? 'selected' : '' }}>
                                Session {{ $session }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="not_started" {{ $status === 'not_started' ? 'selected' : '' }}>Not Started</option>
                        <option value="in_progress" {{ $status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="done" {{ $status === 'done' ? 'selected' : '' }}>Done</option>
                        <option value="stuck" {{ $status === 'stuck' ? 'selected' : '' }}>Stuck</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="search">Search</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="Search by title, session title, or slug..." 
                           value="{{ $search ?? '' }}">
                </div>
                <div class="filter-group">
                    <label class="flex items-center gap-2 cursor-pointer" style="margin-top: 1.5rem;">
                        <input type="checkbox" name="view_archived" value="1" id="view_archived" 
                               {{ $showArchived ?? false ? 'checked' : '' }} 
                               class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-400">
                        <span class="text-sm font-medium text-gray-700">View Archived Lessons</span>
                    </label>
                </div>
                <div class="filter-group filter-actions">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    @if($assignedTo || $gradeLevel || $status || $sessionNumber || $search || $showArchived)
                        <a href="{{ route('admin.lesson-tracker') }}" class="btn" style="margin-left: 0.5rem;">Clear</a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <div class="dashboard-section">
        @if($lessons->isEmpty())
            <p class="empty-text">No lessons found matching the selected filters.</p>
        @else
            <div class="results-info" style="margin-bottom: 1rem;">
                <p>Showing {{ $lessons->count() }} lesson{{ $lessons->count() !== 1 ? 's' : '' }}
                @if($assignedTo || $gradeLevel || $status || $sessionNumber || $search || $showArchived)
                    matching your filters
                @endif
                </p>
            </div>
            <table class="table tracker-table">
            <thead>
                <tr>
                    <th>Lesson</th>
                    <th>Components</th>
                    <th>Assigned To</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lessons as $item)
                    @php
                        $lesson = $item['lesson'];
                        $components = $item['components'];
                    @endphp
                    <tr data-lesson-id="{{ $lesson->id }}">
                        <td>
                            <div class="lesson-info">
                                <strong class="text-gray-800">{{ $lesson->title }}</strong>
                                @if($lesson->is_review)
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-purple-100 text-purple-700">
                                        <i class="fas fa-redo mr-1"></i> Review
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="components-checklist">
                                <div class="component-item">
                                    <span class="component-label">Vocabulary:</span>
                                    @if($components['vocabulary']['has'])
                                        <span class="checkmark">✓</span>
                                        <span class="component-count">({{ $components['vocabulary']['count'] }})</span>
                                        @if($components['vocabulary']['with_images'] < $components['vocabulary']['count'])
                                            <span class="warning">⚠ {{ $components['vocabulary']['with_images'] }}/{{ $components['vocabulary']['count'] }} images</span>
                                        @else
                                            <span class="success">✓ All images</span>
                                        @endif
                                        @if($components['vocabulary']['with_tts'] < $components['vocabulary']['count'])
                                            <span class="warning">⚠ {{ $components['vocabulary']['with_tts'] }}/{{ $components['vocabulary']['count'] }} TTS</span>
                                        @else
                                            <span class="success">✓ All TTS</span>
                                        @endif
                                    @else
                                        <span class="missing">✗</span>
                                    @endif
                                </div>
                                
                                <div class="component-item">
                                    <span class="component-label">Matching Games:</span>
                                    @if($components['matching_games']['has'])
                                        <span class="checkmark">✓</span>
                                        <span class="component-count">({{ $components['matching_games']['count'] }})</span>
                                    @else
                                        <span class="missing">✗</span>
                                    @endif
                                </div>
                                
                                <div class="component-item">
                                    <span class="component-label">Flashcard Games:</span>
                                    @if($components['flashcard_games']['has'])
                                        <span class="checkmark">✓</span>
                                        <span class="component-count">({{ $components['flashcard_games']['count'] }})</span>
                                    @else
                                        <span class="missing">✗</span>
                                    @endif
                                </div>
                                
                                <div class="component-item">
                                    <span class="component-label">Spelling Games:</span>
                                    @if($components['spelling_games']['has'])
                                        <span class="checkmark">✓</span>
                                        <span class="component-count">({{ $components['spelling_games']['count'] }})</span>
                                    @else
                                        <span class="missing">✗</span>
                                    @endif
                                </div>
                                
                                <div class="component-item">
                                    <span class="component-label">Sentence Builder:</span>
                                    @if($components['sentence_builder_games']['has'])
                                        <span class="checkmark">✓</span>
                                        <span class="component-count">({{ $components['sentence_builder_games']['count'] }})</span>
                                    @else
                                        <span class="missing">✗</span>
                                    @endif
                                </div>
                                
                                <div class="component-item">
                                    <span class="component-label">True/False:</span>
                                    @if($components['true_false_questions']['has'])
                                        <span class="checkmark">✓</span>
                                        <span class="component-count">({{ $components['true_false_questions']['count'] }})</span>
                                    @else
                                        <span class="missing">✗</span>
                                    @endif
                                </div>
                                
                                <div class="component-item">
                                    <span class="component-label">Prompts:</span>
                                    @if($components['prompts']['has'])
                                        <span class="checkmark">✓</span>
                                        <span class="component-count">({{ $components['prompts']['count'] }})</span>
                                    @else
                                        <span class="missing">✗</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <select class="form-control assigned-to-select" data-lesson-id="{{ $lesson->id }}">
                                <option value="Unassigned" {{ $lesson->assigned_to === null ? 'selected' : '' }}>Unassigned</option>
                                <option value="Leila" {{ $lesson->assigned_to === 'Leila' ? 'selected' : '' }}>Leila</option>
                                <option value="Jen" {{ $lesson->assigned_to === 'Jen' ? 'selected' : '' }}>Jen</option>
                                <option value="Daniel" {{ $lesson->assigned_to === 'Daniel' ? 'selected' : '' }}>Daniel</option>
                            </select>
                        </td>
                        <td>
                            <select class="form-control status-select" data-lesson-id="{{ $lesson->id }}">
                                <option value="not_started" {{ $lesson->status === 'not_started' ? 'selected' : '' }}>Not Started</option>
                                <option value="in_progress" {{ $lesson->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="done" {{ $lesson->status === 'done' ? 'selected' : '' }}>Done</option>
                                <option value="stuck" {{ $lesson->status === 'stuck' ? 'selected' : '' }}>Stuck</option>
                            </select>
                        </td>
                        <td>
                            <div class="flex gap-2" style="flex-wrap: wrap;">
                                <a href="{{ route('admin.lessons.manage', $lesson) }}" 
                                   class="btn btn-sm" 
                                   style="padding: 0.5rem 1rem; font-size: 0.875rem; background: #3b82f6; color: white; border-radius: 6px; text-decoration: none; display: inline-block;">
                                    Edit Lesson
                                </a>
                                <button type="button" 
                                        class="btn btn-sm notes-btn" 
                                        data-lesson-id="{{ $lesson->id }}"
                                        data-notes="{{ $lesson->admin_notes ?? '' }}"
                                        style="padding: 0.5rem 1rem; font-size: 0.875rem; background: #10b981; color: white; border-radius: 6px; border: none; cursor: pointer;">
                                    Notes
                                    @if($lesson->admin_notes)
                                        <span style="margin-left: 0.25rem;">📝</span>
                                    @endif
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

<!-- Notes Modal -->
<div id="notesModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: #fefefe; margin: 10% auto; padding: 2rem; border: 1px solid #888; width: 80%; max-width: 600px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="margin: 0; font-size: 1.5rem; font-weight: 600;">Admin Notes</h2>
            <span class="close-modal" style="color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; line-height: 20px;">&times;</span>
        </div>
        <form id="notesForm">
            <input type="hidden" id="notesLessonId" name="lesson_id">
            <div style="margin-bottom: 1rem;">
                <label for="notesTextarea" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">Notes:</label>
                <textarea id="notesTextarea" 
                          name="admin_notes" 
                          rows="8" 
                          style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem; font-family: inherit; resize: vertical;"
                          placeholder="Add notes about this lesson's creation process..."></textarea>
            </div>
            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                <button type="button" class="btn cancel-notes-btn" style="background: #6b7280; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Cancel</button>
                <button type="submit" class="btn" style="background: #3b82f6; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Save Notes</button>
            </div>
        </form>
    </div>
</div>

@push('styles')
<style>
.filters-form {
    margin-top: 1rem;
}
.filters-horizontal {
    display: flex;
    flex-direction: row;
    gap: 1.5rem;
    align-items: flex-end;
    flex-wrap: wrap;
}
.filter-group {
    display: flex;
    flex-direction: column;
    min-width: 150px;
}
.filter-actions {
    flex-direction: row;
    align-items: flex-end;
}
.filter-group label {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--color-text-light);
    font-size: 0.9rem;
}
.filter-group .form-control {
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    background: #fff;
}
.filter-group .form-control:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s;
}
.btn-primary {
    background: var(--color-primary);
    color: white;
}
.btn-primary:hover {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
.btn:not(.btn-primary) {
    background: #f3f4f6;
    color: var(--color-text);
}
.btn:not(.btn-primary):hover {
    background: #e5e7eb;
}
.results-info {
    color: #6b7280;
    font-size: 0.9rem;
}
.empty-text {
    color: var(--color-text-muted);
    font-style: italic;
    text-align: center;
    padding: 2rem;
}
.tracker-table {
    width: 100%;
    border-collapse: collapse;
}
.tracker-table th {
    background: #f3f4f6;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    border-bottom: 2px solid #e5e7eb;
}
.tracker-table td {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: top;
}
.tracker-table tbody tr:hover {
    background: #f9fafb;
}
.lesson-info {
    min-width: 200px;
}
.lesson-info strong {
    display: block;
    margin-bottom: 0.25rem;
}
.lesson-meta {
    display: inline-block;
    background: #e5e7eb;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.85rem;
    margin-right: 0.5rem;
    margin-top: 0.25rem;
}
.lesson-subtitle {
    color: #6b7280;
    font-size: 0.9rem;
    margin-top: 0.5rem;
}
.components-checklist {
    min-width: 300px;
}
.component-item {
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}
.component-label {
    font-weight: 500;
    display: inline-block;
    min-width: 140px;
}
.checkmark {
    color: #10b981;
    font-weight: bold;
    margin: 0 0.25rem;
}
.missing {
    color: #ef4444;
    font-weight: bold;
    margin-left: 0.25rem;
}
.component-count {
    color: #6b7280;
    font-size: 0.85rem;
    margin-left: 0.25rem;
}
.success {
    color: #10b981;
    font-size: 0.85rem;
    margin-left: 0.5rem;
}
.warning {
    color: #f59e0b;
    font-size: 0.85rem;
    margin-left: 0.5rem;
}
.form-control {
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.9rem;
    min-width: 150px;
}
.form-control:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
.assigned-to-select,
.status-select {
    cursor: pointer;
}
.status-select[data-status="not_started"] {
    background-color: #fef2f2;
}
.status-select[data-status="in_progress"] {
    background-color: #fef3c7;
}
.status-select[data-status="done"] {
    background-color: #d1fae5;
}
.status-select[data-status="stuck"] {
    background-color: #fee2e2;
}
@media (max-width: 1200px) {
    .tracker-table {
        font-size: 0.9rem;
    }
    .components-checklist {
        min-width: 250px;
    }
    .component-label {
        min-width: 120px;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const notesModal = document.getElementById('notesModal');
    const notesForm = document.getElementById('notesForm');
    const notesTextarea = document.getElementById('notesTextarea');
    const notesLessonId = document.getElementById('notesLessonId');
    
    // Handle assigned_to changes
    document.querySelectorAll('.assigned-to-select').forEach(select => {
        select.addEventListener('change', function() {
            const lessonId = this.dataset.lessonId;
            const assignedTo = this.value;
            
            updateLesson(lessonId, { assigned_to: assignedTo });
        });
    });
    
    // Handle status changes
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            const lessonId = this.dataset.lessonId;
            const status = this.value;
            
            // Update visual styling
            this.setAttribute('data-status', status);
            
            updateLesson(lessonId, { status: status });
        });
    });
    
    // Handle Notes button clicks
    document.querySelectorAll('.notes-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const lessonId = this.dataset.lessonId;
            const notes = this.dataset.notes || '';
            
            notesLessonId.value = lessonId;
            notesTextarea.value = notes;
            notesModal.style.display = 'block';
        });
    });
    
    // Close modal when clicking X or Cancel
    document.querySelector('.close-modal').addEventListener('click', function() {
        notesModal.style.display = 'none';
    });
    
    document.querySelector('.cancel-notes-btn').addEventListener('click', function() {
        notesModal.style.display = 'none';
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === notesModal) {
            notesModal.style.display = 'none';
        }
    });
    
    // Handle notes form submission
    notesForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const lessonId = notesLessonId.value;
        const adminNotes = notesTextarea.value;
        
        updateLesson(lessonId, { admin_notes: adminNotes }, function() {
            notesModal.style.display = 'none';
            // Update the notes button indicator
            const notesBtn = document.querySelector(`.notes-btn[data-lesson-id="${lessonId}"]`);
            if (notesBtn) {
                notesBtn.dataset.notes = adminNotes;
                if (adminNotes) {
                    if (!notesBtn.querySelector('span')) {
                        const span = document.createElement('span');
                        span.style.marginLeft = '0.25rem';
                        span.textContent = '📝';
                        notesBtn.appendChild(span);
                    }
                } else {
                    const span = notesBtn.querySelector('span');
                    if (span) {
                        span.remove();
                    }
                }
            }
        });
    });
    
    function updateLesson(lessonId, data, callback) {
        fetch(`/admin/lesson-tracker/${lessonId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(data),
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Optionally show a success message
                console.log('Lesson updated successfully');
                if (callback) {
                    callback();
                }
            } else {
                alert('Error updating lesson. Please try again.');
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating lesson. Please try again.');
            location.reload();
        });
    }
    
    // Set initial status styling
    document.querySelectorAll('.status-select').forEach(select => {
        select.setAttribute('data-status', select.value);
    });
});
</script>
@endpush
@endsection


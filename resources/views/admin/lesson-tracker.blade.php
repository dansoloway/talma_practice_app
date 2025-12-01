@extends('layouts.admin')

@section('title', 'Lesson Tracker')

@section('content')
<div class="container">
    <h1 class="page-title">Lesson Tracker</h1>

    <div class="dashboard-section">
        <table class="table tracker-table">
            <thead>
                <tr>
                    <th>Lesson</th>
                    <th>Components</th>
                    <th>Assigned To</th>
                    <th>Status</th>
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
                                <strong>{{ $lesson->title }}</strong>
                                @if($lesson->grade_level)
                                    <span class="lesson-meta">Grade {{ $lesson->grade_level }}</span>
                                @endif
                                @if($lesson->session_number)
                                    <span class="lesson-meta">Session {{ $lesson->session_number }}</span>
                                @endif
                                @if($lesson->session_title)
                                    <div class="lesson-subtitle">{{ $lesson->session_title }}</div>
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
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('styles')
<style>
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
    
    function updateLesson(lessonId, data) {
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


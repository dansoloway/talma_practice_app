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
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.lessons.index') }}" class="btn">Back to Lessons</a>
            <a href="{{ route('lessons.show', $lesson->slug) }}" class="btn btn-success" target="_blank">Play as Student</a>
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
                    <label>Status:</label>
                    <span class="status {{ $lesson->is_active ? 'active' : 'inactive' }}">
                        {{ $lesson->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
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

                <div class="form-row">
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

    <!-- Cover Image Section -->
    <div class="management-section">
        <div class="section-header">
            <h2>Cover Image</h2>
        </div>
        
        <div class="cover-image-section">
            @if($lesson->cover_image_path)
                <div class="current-cover-image">
                    <label>Current Cover Image:</label>
                    <div class="cover-image-preview">
                        <img src="{{ $lesson->cover_image_url }}" alt="Cover image" style="max-width: 300px; max-height: 200px; border-radius: 8px; border: 1px solid var(--color-border);">
                    </div>
                </div>
            @else
                <div class="no-cover-image">
                    <p style="color: var(--color-text-muted);">No cover image set.</p>
                </div>
            @endif

            <div class="cover-image-options" style="margin-top: 1.5rem;">
                <h3 style="font-size: 1rem; margin-bottom: 1rem;">Set Cover Image</h3>
                
                <!-- Option 1: Select from Vocabulary Images -->
                @if($lesson->vocabulary->whereNotNull('image_path')->count() > 0)
                    <div class="vocab-images-selector" style="margin-bottom: 2rem;">
                        <label style="display: block; margin-bottom: 0.75rem; font-weight: 500;">Choose from Vocabulary Images:</label>
                        <div class="vocab-images-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                            @foreach($lesson->vocabulary->whereNotNull('image_path') as $vocab)
                                <div class="vocab-image-option" 
                                     data-image-path="{{ $vocab->image_path }}"
                                     onclick="selectVocabImage('{{ $vocab->image_path }}', this)"
                                     style="cursor: pointer; border: 2px solid var(--color-border); border-radius: 8px; padding: 0.5rem; transition: all 0.2s; {{ $lesson->cover_image_path === $vocab->image_path ? 'border-color: var(--color-primary); background: var(--color-primary-bg);' : '' }}">
                                    <img src="{{ $vocab->image_url }}" 
                                         alt="{{ $vocab->english_word }}" 
                                         style="width: 100%; height: 100px; object-fit: cover; border-radius: 4px; margin-bottom: 0.5rem;">
                                    <div style="font-size: 0.75rem; text-align: center; color: var(--color-text-muted);">{{ $vocab->english_word }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Option 2: Upload New Image -->
                <div class="upload-cover-image">
                    <label style="display: block; margin-bottom: 0.75rem; font-weight: 500;">Upload New Image:</label>
                    <form id="cover-image-form" action="{{ route('admin.lessons.update-cover-image', $lesson) }}" method="POST" enctype="multipart/form-data" style="display: flex; gap: 1rem; align-items: flex-end;" onsubmit="return handleCoverImageSubmit(event);">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="cover_image_source" id="cover_image_source" value="">
                        <div style="flex: 1;">
                            <input type="file" 
                                   id="cover_image_file" 
                                   name="cover_image" 
                                   accept="image/jpeg,image/png,image/jpg,image/gif,image/svg"
                                   class="form-control"
                                   style="padding: 0.5rem;">
                            <small style="color: var(--color-text-muted); display: block; margin-top: 0.25rem;">JPEG, PNG, GIF, or SVG (max 2MB)</small>
                        </div>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </form>
                </div>

                <!-- Remove Cover Image -->
                @if($lesson->cover_image_path)
                    <div style="margin-top: 1rem;">
                        <form action="{{ route('admin.lessons.remove-cover-image', $lesson) }}" method="POST" onsubmit="return confirm('Remove cover image?');">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-danger btn-sm">Remove Cover Image</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Vocabulary Section -->
    <div class="management-section">
        <div class="section-header">
            <h2>Vocabulary ({{ $lesson->vocabulary->count() }})</h2>
            <div class="section-actions">
                <a href="{{ route('admin.lessons.vocabulary.index', $lesson) }}" class="btn btn-primary btn-sm">Edit Vocabulary</a>
                <a href="{{ route('admin.lessons.vocabulary.csv.upload', $lesson) }}" class="btn btn-secondary btn-sm">Upload CSV</a>
            </div>
        </div>

        @if($lesson->vocabulary->count() > 0)
            <div class="vocabulary-grid">
                @foreach($lesson->vocabulary as $vocab)
                    <div class="vocabulary-card">
                        @if($vocab->image_path)
                            <img src="{{ $vocab->image_url }}" alt="{{ $vocab->english_word }}" class="vocab-image">
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

    <!-- Clause Exercises Section -->
    <div class="management-section">
        <div class="section-header">
            <h2>Clause Exercises ({{ $lesson->clauseExercises->count() }})</h2>
            <div class="section-actions">
                <a href="{{ route('admin.lessons.clause-exercises.create', $lesson) }}" class="btn btn-primary btn-sm">+ Create Clause Exercise</a>
            </div>
        </div>

        @if($lesson->clauseExercises->count() > 0)
            <div class="clause-exercises-list" style="margin-top: 1rem;">
                @foreach($lesson->clauseExercises as $exercise)
                    <div class="clause-exercise-card" style="background: white; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                            <div>
                                <h3 style="margin: 0 0 0.5rem 0; color: var(--color-primary);">{{ $exercise->title }}</h3>
                                <div style="font-size: 0.875rem; color: var(--color-text-muted);">
                                    Blanks: {{ count($exercise->correct_answers ?? []) }}
                                    @if($exercise->grammarSet)
                                        | Grammar Set: {{ $exercise->grammarSet->title }}
                                    @endif
                                </div>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="{{ route('clause-exercises.play', [$lesson, $exercise]) }}" class="btn btn-xs btn-success" target="_blank">Play</a>
                                <a href="{{ route('admin.lessons.clause-exercises.edit', [$lesson, $exercise]) }}" class="btn btn-secondary btn-sm">Edit</a>
                                <form action="{{ route('admin.lessons.clause-exercises.destroy', [$lesson, $exercise]) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Delete this clause exercise?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </div>
                        <div style="background: var(--color-gray-50); padding: 1rem; border-radius: var(--radius-sm); font-family: monospace; white-space: pre-wrap;">{{ \Illuminate\Support\Str::limit($exercise->paragraph_text, 200) }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <p>No clause exercises yet. <a href="{{ route('admin.lessons.clause-exercises.create', $lesson) }}">Create your first clause exercise</a> using AI.</p>
            </div>
        @endif
    </div>

    <!-- Activities Section -->
    <div class="management-section">
        <div class="section-header">
            <h2>Activities</h2>
            <div class="section-actions">
                <a href="{{ route('admin.lessons.prompts.create', $lesson) }}" class="btn btn-primary btn-sm">+ Prompts</a>
                <a href="{{ route('admin.lessons.matching-games.create', $lesson) }}" class="btn btn-primary btn-sm">+ Matching</a>
                <a href="{{ route('admin.lessons.flashcard-games.create', $lesson) }}" class="btn btn-primary btn-sm">+ Flashcard</a>
                <a href="{{ route('admin.lessons.spelling-games.create', $lesson) }}" class="btn btn-primary btn-sm">+ Spelling</a>
                {{-- <a href="{{ route('admin.lessons.sentence-builder-games.create', $lesson) }}" class="btn btn-primary btn-sm">+ Sentence Builder</a> --}}
                <a href="{{ route('admin.lessons.true-false-games.index', $lesson) }}" class="btn btn-primary btn-sm">True/False</a>
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
            
            // Add spelling games
            foreach($lesson->spellingGames as $game) {
                $allActivities->push((object)[
                    'id' => $game->id,
                    'type' => 'spelling',
                    'title' => $game->title,
                    'sort_order' => $game->sort_order ?? 999,
                    'is_active' => $game->is_active ?? true,
                    'model' => $game
                ]);
            }
            
            // Add sentence builder games (DISABLED)
            // foreach($lesson->sentenceBuilderGames as $game) {
            //     $questionCount = $game->questions()->count();
            //     $allActivities->push((object)[
            //         'id' => $game->id,
            //         'type' => 'sentence_builder',
            //         'title' => $game->title . ($questionCount > 0 ? ' (' . $questionCount . ' questions)' : ''),
            //         'sort_order' => $game->sort_order ?? 999,
            //         'is_active' => $game->is_active ?? true,
            //         'model' => $game
            //     ]);
            // }
            
            // Add True/False games
            foreach($lesson->trueFalseGames as $game) {
                if(!$game->is_active) continue;
                
                $approvedCount = $game->questions()
                    ->where('is_approved', true)
                    ->where('is_active', true)
                    ->count();
                
                if($approvedCount > 0) {
                    $allActivities->push((object)[
                        'id' => $game->id,
                        'type' => 'true_false',
                        'title' => $game->title . ' (' . $approvedCount . ' questions)',
                        'sort_order' => $game->sort_order ?? 999,
                        'is_active' => $game->is_active ?? true,
                        'model' => $game
                    ]);
                }
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
                                    @if($activity->type === 'prompts')
                                        📝
                                    @elseif($activity->type === 'matching')
                                        🔗
                                    @elseif($activity->type === 'flashcard')
                                        🎴
                                    @elseif($activity->type === 'spelling')
                                        ✍️
                                    {{-- @elseif($activity->type === 'sentence_builder')
                                        🏗️ --}}
                                    @elseif($activity->type === 'true_false')
                                        ✓✗
                                    @endif
                                    {{ ucfirst(str_replace('_', ' ', $activity->type)) }}
                                </span>
                                <h4 class="activity-title">{{ $activity->title }}</h4>
                                <span class="activity-status {{ $activity->is_active ? 'active' : 'inactive' }}">
                                    {{ $activity->is_active ? 'Active' : 'Inactive' }}
                                </span>
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
                            @elseif($activity->type === 'spelling')
                                <a href="{{ route('admin.lessons.spelling-games.edit', [$lesson, $activity->model]) }}" class="btn btn-xs">Edit</a>
                                <a href="{{ route('spelling-games.play', [$lesson, $activity->model]) }}" class="btn btn-xs btn-success" target="_blank">Play</a>
                                <button class="btn btn-xs btn-danger delete-activity-btn" data-type="{{ $activity->type }}" data-id="{{ $activity->id }}" data-title="{{ addslashes($activity->title) }}">Delete</button>
                            {{-- @elseif($activity->type === 'sentence_builder')
                                <a href="{{ route('admin.lessons.sentence-builder-games.show', [$lesson, $activity->model]) }}" class="btn btn-xs">Edit</a>
                                <a href="{{ route('sentence-builder-games.play', [$lesson, $activity->model]) }}" class="btn btn-xs btn-success" target="_blank">Play</a>
                                <button class="btn btn-xs btn-danger delete-activity-btn" data-type="{{ $activity->type }}" data-id="{{ $activity->id }}" data-title="{{ addslashes($activity->title) }}">Delete</button> --}}
                            @elseif($activity->type === 'true_false')
                                <a href="{{ route('admin.lessons.true-false-games.show', [$lesson, $activity->model]) }}" class="btn btn-xs">Edit</a>
                                <a href="{{ route('true-false-games.play', [$lesson, $activity->model]) }}" class="btn btn-xs btn-success" target="_blank">Play</a>
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

<span id="start-tts-flag" data-start="{{ session('start_tts') ? 1 : 0 }}" style="display:none;"></span>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if TTS generation should start
    const startTtsFlag = document.getElementById('start-tts-flag');
    if (startTtsFlag && startTtsFlag.dataset.start === '1') {
        // Start TTS generation process
        console.log('Starting TTS generation...');
        startTtsGeneration({{ $lesson->id }});
    }
    
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

function startTtsGeneration(lessonId) {
    // Generate word TTS first
    generateWordTtsBatch(lessonId, function() {
        // Then generate sentence TTS
        generateSentenceTtsBatch(lessonId);
    });
}

function generateWordTtsBatch(lessonId, callback) {
    fetch('/admin/lessons/' + lessonId + '/prompts/generate-word-tts', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        if (data.completed || data.locked) {
            console.log('Word TTS completed or locked');
            if (callback) callback();
        } else {
            console.log('Processed word TTS, ' + data.remaining + ' remaining');
            if (data.remaining > 0) {
                setTimeout(() => generateWordTtsBatch(lessonId, callback), 2000);
            } else {
                if (callback) callback();
            }
        }
    })
    .catch(error => {
        console.error('Error generating word TTS:', error);
        if (callback) callback();
    });
}

function generateSentenceTtsBatch(lessonId) {
    fetch('/admin/lessons/' + lessonId + '/prompts/generate-sentence-tts', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        if (data.completed || data.locked) {
            console.log('Sentence TTS completed or locked');
        } else {
            console.log('Processed sentence TTS, ' + data.remaining + ' remaining');
            if (data.remaining > 0) {
                setTimeout(() => generateSentenceTtsBatch(lessonId), 2000);
            }
        }
    })
    .catch(error => {
        console.error('Error generating sentence TTS:', error);
    });
}

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

// Cover image selection
function selectVocabImage(imagePath, element) {
    // Update visual selection
    document.querySelectorAll('.vocab-image-option').forEach(option => {
        option.style.borderColor = 'var(--color-border)';
        option.style.background = '';
    });
    if (element) {
        element.style.borderColor = 'var(--color-primary)';
        element.style.background = 'var(--color-primary-bg)';
    }
    
    // Set hidden input and submit form
    document.getElementById('cover_image_source').value = imagePath;
    document.getElementById('cover_image_file').value = ''; // Clear file input
    
    // Submit form via AJAX
    const form = document.getElementById('cover-image-form');
    const formData = new FormData(form);
    formData.append('cover_image_source', imagePath);
    
    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Refresh to show updated cover image
        } else {
            alert('Error setting cover image: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error setting cover image');
    });
}

// Handle cover image form submission
function handleCoverImageSubmit(event) {
    const fileInput = document.getElementById('cover_image_file');
    const sourceInput = document.getElementById('cover_image_source');
    
    // If no file selected and no source selected, prevent submission
    if (!fileInput.files.length && !sourceInput.value) {
        event.preventDefault();
        alert('Please select an image from vocabulary or upload a new image.');
        return false;
    }
    
    // If file is selected, allow normal form submission
    if (fileInput.files.length) {
        return true; // Allow normal form submission
    }
    
    // If source is selected but no file, prevent normal submission (already handled by AJAX)
    if (sourceInput.value && !fileInput.files.length) {
        event.preventDefault();
        return false;
    }
    
    return true;
}
</script>

<!-- Hidden form for archiving -->
<form id="archive-form" action="{{ route('admin.lessons.archive', $lesson) }}" method="POST" style="display: none;">
    @csrf
</form>


@endsection

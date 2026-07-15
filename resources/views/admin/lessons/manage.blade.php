@extends('layouts.admin')

@section('title', 'Manage Lesson: ' . $lesson->title)

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">
    <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-3">Manage Lesson: {{ $lesson->title }}</h1>
            <div class="flex flex-wrap gap-3">
                @if($lesson->grade_level)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-700">Grade {{ $lesson->grade_level }}</span>
                @endif
                @if($lesson->session_number)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-purple-100 text-purple-700">Session {{ $lesson->session_number }}</span>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.lessons.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-all duration-200">Back to Lessons</a>
            <a href="{{ route('lessons.show', $lesson->slug) }}" class="px-4 py-2 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 transition-all duration-200 shadow-sm hover:shadow-md" target="_blank">Play as Student</a>
            <button onclick="archiveLesson()" class="px-4 py-2 bg-yellow-100 text-yellow-700 font-semibold rounded-xl hover:bg-yellow-200 transition-all duration-200">Archive Lesson</button>
        </div>
    </div>

    <!-- Lesson Details Section -->
    <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-6 mb-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Lesson Details</h2>
            <div class="flex gap-3">
                <button id="edit-lesson-btn" class="px-4 py-2 bg-blue-100 text-blue-700 font-semibold rounded-xl hover:bg-blue-200 transition-all duration-200">Edit Details</button>
                <button id="edit-cover-image-btn" class="px-4 py-2 bg-purple-100 text-purple-700 font-semibold rounded-xl hover:bg-purple-200 transition-all duration-200">Edit Cover Image</button>
            </div>
        </div>
        
        <div class="lesson-info" id="lesson-info">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Title</label>
                    <span id="lesson-title" class="text-gray-800 font-medium">{{ $lesson->title }}</span>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Slug</label>
                    <span id="lesson-slug" class="text-gray-800 font-medium font-mono text-sm">{{ $lesson->slug }}</span>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Course</label>
                    <span id="lesson-course" class="text-gray-800 font-medium">{{ $lesson->course ? $lesson->course->title : 'Not set' }}</span>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Grade Level</label>
                    <span id="lesson-grade" class="text-gray-800 font-medium">{{ $lesson->grade_level ?: 'Not set' }}</span>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Session Number</label>
                    <span id="lesson-session" class="text-gray-800 font-medium">{{ $lesson->session_number ?: 'Not set' }}</span>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Status</label>
                    @if($lesson->is_active)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Active</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">Inactive</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Edit Form (Hidden by default) -->
        <div class="edit-form hidden" id="lesson-edit-form">
            <form action="{{ route('admin.lessons.update', $lesson) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">Title <span class="text-red-500">*</span></label>
                        <input type="text" id="title" name="title" value="{{ $lesson->title }}" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
                    </div>
                    <div>
                        <label for="slug" class="block text-sm font-semibold text-gray-700 mb-2">Slug <span class="text-red-500">*</span></label>
                        <input type="text" id="slug" name="slug" value="{{ $lesson->slug }}" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="course_id" class="block text-sm font-semibold text-gray-700 mb-2">Course</label>
                        <select id="course_id" name="course_id" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
                            <option value="">Select Course</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ $lesson->course_id == $course->id ? 'selected' : '' }}>
                                    {{ $course->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="grade_level" class="block text-sm font-semibold text-gray-700 mb-2">Grade Level</label>
                        <select id="grade_level" name="grade_level" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
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
                    <div>
                        <label for="session_number" class="block text-sm font-semibold text-gray-700 mb-2">Session Number</label>
                        <input type="number" id="session_number" name="session_number" value="{{ $lesson->session_number }}" min="1" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
                    </div>
                </div>

                <div>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ $lesson->is_active ? 'checked' : '' }} 
                               class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-400">
                        <span class="text-gray-700 font-medium">Active</span>
                    </label>
                </div>

                <div>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_review" value="1" {{ $lesson->is_review ? 'checked' : '' }} 
                               id="is_review_checkbox"
                               class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-400">
                        <span class="text-gray-700 font-medium">Review Lesson</span>
                    </label>
                    <p class="mt-2 text-sm text-gray-600">Review lessons combine vocabulary from other lessons</p>
                </div>

                <div id="review_sources_section" style="display: {{ $lesson->is_review ? 'block' : 'none' }};">
                    <label for="review_source_lessons" class="block text-sm font-semibold text-gray-700 mb-2">Source Lessons</label>
                    <select id="review_source_lessons" name="review_source_lessons[]" multiple
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200"
                            style="min-height: 150px;">
                        @foreach($allLessons as $sourceLesson)
                            <option value="{{ $sourceLesson->id }}" 
                                    {{ $lesson->is_review && $lesson->reviewSources->contains($sourceLesson->id) ? 'selected' : '' }}
                                    {{ $sourceLesson->id == $lesson->id ? 'disabled' : '' }}>
                                {{ $sourceLesson->title }} @if($sourceLesson->session_number)(Session {{ $sourceLesson->session_number }})@endif
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-sm text-gray-600">Select the lessons this review lesson will combine vocabulary from</p>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">Save Changes</button>
                    <button type="button" id="cancel-edit-btn" class="px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 active:scale-95 transition-all duration-200">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cover Image Section -->
    <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-6 mb-6 hidden" id="cover-image-section">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Cover Image</h2>
            <button id="close-cover-image-btn" class="px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-all duration-200">Close</button>
        </div>
        
        <div class="space-y-6">
            @if($lesson->cover_image_path)
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Current Cover Image</label>
                    <div class="inline-block">
                        <img src="{{ $lesson->cover_image_url }}" alt="Cover image" class="max-w-xs max-h-48 rounded-xl border border-gray-200 shadow-sm">
                    </div>
                </div>
            @else
                <div>
                    <p class="text-gray-600">No cover image set.</p>
                </div>
            @endif

            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Set Cover Image</h3>
                
                <!-- Option 1: Select from Vocabulary Images -->
                @if($lesson->vocabulary->whereNotNull('image_path')->count() > 0)
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-3">Choose from Vocabulary Images</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                            @foreach($lesson->vocabulary->whereNotNull('image_path') as $vocab)
                                <div class="vocab-image-option cursor-pointer border-2 rounded-xl p-2 transition-all duration-200 {{ $lesson->cover_image_path === $vocab->image_path ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-blue-300 hover:shadow-md' }}" 
                                     data-image-path="{{ $vocab->image_path }}"
                                     onclick="selectVocabImage('{{ $vocab->image_path }}', this)">
                                    <img src="{{ $vocab->image_url }}" 
                                         alt="{{ $vocab->english_word }}" 
                                         class="w-full h-24 object-cover rounded-lg mb-2">
                                    <div class="text-xs text-center text-gray-600 font-medium">{{ $vocab->english_word }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Option 2: Upload New Image -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">Upload New Image</label>
                    <form id="cover-image-form" action="{{ route('admin.lessons.update-cover-image', $lesson) }}" method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row gap-4 items-end" onsubmit="return handleCoverImageSubmit(event);">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="cover_image_source" id="cover_image_source" value="">
                        <div class="flex-1 w-full">
                            <input type="file" 
                                   id="cover_image_file" 
                                   name="cover_image" 
                                   accept="image/jpeg,image/png,image/jpg,image/gif,image/svg"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
                            <p class="mt-2 text-sm text-gray-600">JPEG, PNG, GIF, or SVG (max 2MB)</p>
                        </div>
                        <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">Upload</button>
                    </form>
                </div>

                <!-- Remove Cover Image -->
                @if($lesson->cover_image_path)
                    <div class="pt-4 border-t border-gray-200">
                        <form action="{{ route('admin.lessons.remove-cover-image', $lesson) }}" method="POST" onsubmit="return confirm('Remove cover image?');">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="px-4 py-2 bg-red-100 text-red-700 font-semibold rounded-xl hover:bg-red-200 transition-all duration-200">Remove Cover Image</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Vocabulary Section -->
    <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-6 mb-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Vocabulary ({{ $lesson->vocabulary->count() }})</h2>
                @if($lesson->is_review && $lesson->reviewSources->count() > 0)
                    <p class="text-sm text-purple-600 mt-1">
                        <i class="fas fa-info-circle mr-1"></i>
                        Vocabulary from: {{ $lesson->reviewSources->pluck('title')->join(', ') }}
                    </p>
                @endif
            </div>
            @if(!$lesson->is_review)
                <div class="flex gap-3">
                    <a href="{{ route('admin.lessons.vocabulary.index', $lesson) }}" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200 shadow-sm hover:shadow-md">{{ $lesson->vocabulary->count() > 0 ? 'Edit Vocabulary' : 'Add Vocabulary' }}</a>
                    <a href="{{ route('admin.lessons.vocabulary.csv.upload', $lesson) }}" class="px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-all duration-200">Upload CSV</a>
                </div>
            @else
                <div class="flex gap-3">
                    <a href="{{ route('admin.lessons.vocabulary.index', $lesson) }}" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200 shadow-sm hover:shadow-md">View Vocabulary</a>
                </div>
            @endif
        </div>

        @if($lesson->vocabulary->count() > 0)
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @foreach($lesson->vocabulary as $vocab)
                    <div class="bg-gray-50 rounded-xl border border-gray-200 p-3 hover:shadow-md transition-all duration-200">
                        @if($vocab->image_path)
                            <img src="{{ $vocab->image_url }}" alt="{{ $vocab->english_word }}" class="w-full h-24 object-cover rounded-lg mb-2">
                        @endif
                        <div class="text-center">
                            <h4 class="font-semibold text-gray-800 text-sm">{{ $vocab->english_word }}</h4>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <p class="text-gray-600 mb-2">No vocabulary items yet.</p>
                <p class="text-sm text-gray-500 mb-6">Paste your word list — one word per line — to get started quickly.</p>
                <div class="flex flex-wrap gap-3 justify-center">
                    <a href="{{ route('admin.lessons.vocabulary.index', $lesson) }}" class="inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200 shadow-sm hover:shadow-md">Paste Words</a>
                    <a href="{{ route('admin.lessons.vocabulary.csv.upload', $lesson) }}" class="inline-block px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-all duration-200">Upload CSV</a>
                    <a href="{{ route('admin.lessons.vocabulary.create', $lesson) }}" class="inline-block px-6 py-3 text-gray-600 font-semibold rounded-xl hover:bg-gray-100 transition-all duration-200 underline underline-offset-2">Add single word</a>
                </div>
            </div>
        @endif
    </div>

    <!-- Clause Exercises Section -->
    <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-6 mb-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Clause Exercises ({{ $lesson->clauseExercises->count() }})</h2>
            <a href="{{ route('admin.lessons.clause-exercises.create', $lesson) }}" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200 shadow-sm hover:shadow-md">+ Create Clause Exercise</a>
        </div>

        @if($lesson->clauseExercises->count() > 0)
            <div class="space-y-4">
                @foreach($lesson->clauseExercises as $exercise)
                    <div class="bg-white border border-gray-200 rounded-xl p-6 hover:shadow-md transition-all duration-200">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-bold text-blue-600 mb-2">{{ $exercise->title }}</h3>
                                <div class="text-sm text-gray-600">
                                    Blanks: {{ count($exercise->correct_answers ?? []) }}
                                    @if($exercise->grammarSet)
                                        | Grammar Set: {{ $exercise->grammarSet->title }}
                                    @endif
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('clause-exercises.play', [$lesson, $exercise]) }}" class="px-3 py-1.5 text-sm bg-green-100 text-green-700 font-medium rounded-lg hover:bg-green-200 transition-all duration-200" target="_blank">Play</a>
                                <a href="{{ route('admin.lessons.clause-exercises.edit', [$lesson, $exercise]) }}" class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-all duration-200">Edit</a>
                                <form action="{{ route('admin.lessons.clause-exercises.destroy', [$lesson, $exercise]) }}" method="POST" class="inline" onsubmit="return confirm('Delete this clause exercise?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 text-sm bg-red-100 text-red-700 font-medium rounded-lg hover:bg-red-200 transition-all duration-200">Delete</button>
                                </form>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg font-mono text-sm whitespace-pre-wrap">{{ \Illuminate\Support\Str::limit($exercise->paragraph_text, 200) }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <p class="text-gray-600 mb-4">No clause exercises yet.</p>
                <a href="{{ route('admin.lessons.clause-exercises.create', $lesson) }}" class="inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200 shadow-sm hover:shadow-md">Create your first clause exercise</a>
            </div>
        @endif
    </div>

    <!-- Activities Section -->
    <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-6 mb-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Activities</h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.lessons.prompts.create', $lesson) }}" class="px-3 py-2 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200 shadow-sm hover:shadow-md text-sm">+ Prompts</a>
                <a href="{{ route('admin.lessons.matching-games.create', $lesson) }}" class="px-3 py-2 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200 shadow-sm hover:shadow-md text-sm">+ Matching</a>
                <a href="{{ route('admin.lessons.flashcard-games.create', $lesson) }}" class="px-3 py-2 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200 shadow-sm hover:shadow-md text-sm">+ Flashcard</a>
                <a href="{{ route('admin.lessons.spelling-games.create', $lesson) }}" class="px-3 py-2 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200 shadow-sm hover:shadow-md text-sm">+ Spelling</a>
                <a href="{{ route('admin.lessons.true-false-games.index', $lesson) }}" class="px-3 py-2 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200 shadow-sm hover:shadow-md text-sm">True/False</a>
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
    
    // Cover image section toggle
    const editCoverImageBtn = document.getElementById('edit-cover-image-btn');
    const closeCoverImageBtn = document.getElementById('close-cover-image-btn');
    const coverImageSection = document.getElementById('cover-image-section');
    
    if (editCoverImageBtn && coverImageSection) {
        editCoverImageBtn.addEventListener('click', function() {
            coverImageSection.classList.remove('hidden');
            coverImageSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }
    
    if (closeCoverImageBtn && coverImageSection) {
        closeCoverImageBtn.addEventListener('click', function() {
            coverImageSection.classList.add('hidden');
        });
    }
    
    // Review lesson checkbox toggle
    const isReviewCheckbox = document.getElementById('is_review_checkbox');
    const reviewSourcesSection = document.getElementById('review_sources_section');
    const reviewSourceSelect = document.getElementById('review_source_lessons');
    
    if (isReviewCheckbox && reviewSourcesSection) {
        isReviewCheckbox.addEventListener('change', function() {
            if (this.checked) {
                reviewSourcesSection.style.display = 'block';
                if (reviewSourceSelect) {
                    reviewSourceSelect.setAttribute('required', 'required');
                }
            } else {
                reviewSourcesSection.style.display = 'none';
                if (reviewSourceSelect) {
                    reviewSourceSelect.removeAttribute('required');
                }
            }
        });
    }
    
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

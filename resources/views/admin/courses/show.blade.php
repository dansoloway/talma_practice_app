@extends('layouts.admin')

@section('title', $course->title)

@php
    $params = $courseRouteParams ?? [];
    $route = fn($name, $course = null) => isset($params['organization'])
        ? route('org.admin.courses.' . $name, array_merge($params, $course ? ['course' => $course] : []))
        : ($course ? route('admin.courses.' . $name, $course) : route('admin.courses.' . $name));
@endphp

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">{{ $course->title }}</h1>
            @if($course->isArchived())
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-700 mt-2">
                    <i class="fas fa-archive mr-1"></i> Archived
                </span>
            @endif
        </div>
        <div class="flex gap-3">
            @if($course->isArchived())
                <form action="{{ $route('unarchive', $course) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 transition-all duration-200">
                        Unarchive Course
                    </button>
                </form>
            @else
                <form action="{{ $route('archive', $course) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-yellow-600 text-white font-semibold rounded-xl hover:bg-yellow-700 transition-all duration-200" 
                            onclick="return confirm('Are you sure you want to archive this course? Students will no longer be able to access it, but it can be restored later.')">
                        Archive Course
                    </button>
                </form>
            @endif
            <a href="{{ $route('edit', $course) }}" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200">
                Edit Course
            </a>
            <a href="{{ $route('index') }}" class="px-4 py-2 text-gray-700 font-medium rounded-xl hover:bg-gray-100 transition-all duration-200">
                Back to Courses
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Course Info -->
        <div class="lg:col-span-1">
            <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-6 mb-6">
                @if($course->cover_image_path)
                    <div class="mb-4">
                        <img src="{{ $course->cover_image_url }}" alt="{{ $course->title }}" class="w-full rounded-lg">
                    </div>
                @endif
                
                <div class="space-y-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 mb-1">Description</h3>
                        <p class="text-gray-800">{{ $course->description ?: 'No description' }}</p>
                    </div>
                    
                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 mb-1">Slug</h3>
                        <p class="text-gray-800 font-mono text-sm">{{ $course->slug }}</p>
                    </div>
                    
                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 mb-1">Status</h3>
                        <div class="flex flex-wrap gap-2">
                            @if($course->is_active)
                                <span class="px-3 py-1 text-sm font-semibold bg-green-100 text-green-700 rounded-full">Active</span>
                            @else
                                <span class="px-3 py-1 text-sm font-semibold bg-gray-100 text-gray-700 rounded-full">Inactive</span>
                            @endif
                            @if($course->isArchived())
                                <span class="px-3 py-1 text-sm font-semibold bg-yellow-100 text-yellow-700 rounded-full">
                                    <i class="fas fa-archive mr-1"></i> Archived
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 mb-1">Lessons</h3>
                        <p class="text-gray-800 font-bold text-lg">{{ $course->lessons->where('is_active', true)->whereNull('archived_at')->count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lessons List -->
        <div class="lg:col-span-2">
            <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Lessons</h2>
                    <div class="flex gap-3">
                        <button onclick="openCreateReviewModal()" class="px-4 py-2 bg-purple-600 text-white font-semibold rounded-xl hover:bg-purple-700 transition-all duration-200">
                            Create Review Lesson
                        </button>
                        <a href="{{ route('admin.lessons.create', ['course_id' => $course->id]) }}" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200">
                            Add Lesson
                        </a>
                    </div>
                </div>

                @php
                    $activeLessons = $course->lessons->where('is_active', true)->whereNull('archived_at');
                @endphp
                
                @if($activeLessons->isEmpty())
                    <div class="text-center py-12">
                        <div class="text-6xl mb-4">📚</div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">No active lessons yet</h3>
                        <p class="text-gray-600 mb-6">This course doesn't have any active lessons yet.</p>
                        <a href="{{ route('admin.lessons.create', ['course_id' => $course->id]) }}" class="inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200">
                            Add First Lesson
                        </a>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($activeLessons->sortBy('session_number') as $lesson)
                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors duration-200">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-800">{{ $lesson->title }}</h3>
                                    <div class="flex gap-3 mt-2 text-sm text-gray-600">
                                        @if($lesson->session_number)
                                            <span>Session {{ $lesson->session_number }}</span>
                                        @endif
                                        @if($lesson->grade_level)
                                            <span>Grade {{ $lesson->grade_level }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <a href="{{ route('admin.lessons.show', $lesson) }}" class="px-3 py-1 bg-blue-100 text-blue-700 font-medium rounded-lg hover:bg-blue-200 transition-all duration-200">
                                        View
                                    </a>
                                    <a href="{{ route('admin.lessons.edit', $lesson) }}" class="px-3 py-1 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-all duration-200">
                                        Edit
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Create Review Lesson Modal -->
<div id="createReviewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-bold text-gray-800">Create Review Lesson</h2>
                <button onclick="closeCreateReviewModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        
        <form action="{{ route('admin.lessons.store') }}" method="POST" id="createReviewForm" class="p-6">
            @csrf
            <div class="space-y-6">
                <div>
                    <label for="review_title" class="block text-sm font-semibold text-gray-700 mb-2">Review Title <span class="text-red-500">*</span></label>
                    <input type="text" id="review_title" name="title" required 
                           placeholder="e.g., Review: Lessons 1-2"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
                    <p class="mt-2 text-sm text-gray-600">Slug will be auto-generated from the title</p>
                </div>
                
                <div>
                    <label for="review_course_id" class="block text-sm font-semibold text-gray-700 mb-2">Course <span class="text-red-500">*</span></label>
                    <select id="review_course_id" name="course_id" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
                        <option value="{{ $course->id }}" selected>{{ $course->title }}</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Select Lessons to Review <span class="text-red-500">*</span></label>
                    <div class="border border-gray-300 rounded-xl p-4 max-h-64 overflow-y-auto bg-gray-50">
                        @forelse($course->lessons->where('is_active', true)->whereNull('archived_at') as $lesson)
                            <label class="flex items-center gap-3 p-2 hover:bg-white rounded-lg cursor-pointer transition-colors">
                                <input type="checkbox" name="review_source_lessons[]" value="{{ $lesson->id }}" 
                                       class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-400 review-source-checkbox"
                                       onchange="loadVocabularyForReview()">
                                <div class="flex-1">
                                    <span class="font-medium text-gray-800">{{ $lesson->title }}</span>
                                    @if($lesson->session_number)
                                        <span class="text-sm text-gray-500 ml-2">(Session {{ $lesson->session_number }})</span>
                                    @endif
                                </div>
                            </label>
                        @empty
                            <p class="text-gray-500 text-center py-4">No active lessons available in this course</p>
                        @endforelse
                    </div>
                    <p class="mt-2 text-sm text-gray-600">Select at least one lesson to review</p>
                </div>
                
                <!-- Vocabulary Selection Section -->
                <div id="vocabulary-selection-section" class="hidden">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Select Vocabulary Words <span class="text-red-500">*</span>
                        <span class="text-sm font-normal text-gray-500">(Maximum 30 words)</span>
                    </label>
                    <div id="vocabulary-loading" class="hidden text-center py-4 text-gray-500">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Loading vocabulary...
                    </div>
                    <div id="vocabulary-list" class="border border-gray-300 rounded-xl p-4 max-h-96 overflow-y-auto bg-gray-50 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                        <!-- Vocabulary items will be loaded here -->
                    </div>
                    <div class="mt-2 flex items-center justify-between">
                        <p class="text-sm text-gray-600">
                            <span id="vocab-selection-count">0</span> words selected (minimum 2, maximum 30)
                        </p>
                        <button type="button" onclick="selectAllVocabulary()" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                            Select All
                        </button>
                    </div>
                    <p id="vocab-error" class="mt-2 text-sm text-red-600 hidden"></p>
                </div>
                
                <input type="hidden" name="is_review" value="1">
                <input type="hidden" name="is_active" value="1">
                <input type="hidden" name="review_vocabulary_ids" id="review_vocabulary_ids" value="">
                
                <div class="flex gap-4 pt-4">
                    <button type="submit" class="flex-1 px-6 py-3 bg-purple-600 text-white font-semibold rounded-xl hover:bg-purple-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">
                        Create Review Lesson
                    </button>
                    <button type="button" onclick="closeCreateReviewModal()" class="px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 active:scale-95 transition-all duration-200">
                        Cancel
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let allVocabulary = [];
let selectedVocabIds = new Set();

function openCreateReviewModal() {
    document.getElementById('createReviewModal').classList.remove('hidden');
}

function closeCreateReviewModal() {
    document.getElementById('createReviewModal').classList.add('hidden');
    // Reset form
    document.getElementById('createReviewForm').reset();
    document.querySelectorAll('.review-source-checkbox').forEach(cb => cb.checked = false);
    // Reset course selection
    document.getElementById('review_course_id').value = '{{ $course->id }}';
    // Reset vocabulary section
    document.getElementById('vocabulary-selection-section').classList.add('hidden');
    document.getElementById('vocabulary-list').innerHTML = '';
    allVocabulary = [];
    selectedVocabIds.clear();
    updateVocabSelectionCount();
}

// Close modal when clicking outside
document.getElementById('createReviewModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCreateReviewModal();
    }
});

// Load vocabulary when lessons are selected
async function loadVocabularyForReview() {
    const checkedBoxes = document.querySelectorAll('.review-source-checkbox:checked');
    const lessonIds = Array.from(checkedBoxes).map(cb => cb.value);
    
    if (lessonIds.length === 0) {
        document.getElementById('vocabulary-selection-section').classList.add('hidden');
        return;
    }
    
    document.getElementById('vocabulary-selection-section').classList.remove('hidden');
    document.getElementById('vocabulary-loading').classList.remove('hidden');
    document.getElementById('vocabulary-list').innerHTML = '';
    selectedVocabIds.clear();
    
    try {
        const response = await fetch('{{ route("admin.lessons.get-vocabulary-for-review") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ lesson_ids: lessonIds })
        });
        
        const data = await response.json();
        allVocabulary = data.vocabulary;
        
        renderVocabularyList();
        document.getElementById('vocabulary-loading').classList.add('hidden');
    } catch (error) {
        console.error('Error loading vocabulary:', error);
        document.getElementById('vocabulary-loading').classList.add('hidden');
        document.getElementById('vocab-error').textContent = 'Failed to load vocabulary. Please try again.';
        document.getElementById('vocab-error').classList.remove('hidden');
    }
}

function renderVocabularyList() {
    const container = document.getElementById('vocabulary-list');
    container.innerHTML = '';
    
    allVocabulary.forEach(vocab => {
        const isSelected = selectedVocabIds.has(vocab.id);
        const vocabCard = document.createElement('label');
        vocabCard.className = `flex flex-col items-center gap-2 p-3 border-2 rounded-lg cursor-pointer transition-all ${
            isSelected ? 'border-blue-500 bg-blue-50' : 'border-gray-300 bg-white hover:border-gray-400'
        }`;
        
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'vocab-checkbox hidden';
        checkbox.value = vocab.id;
        checkbox.checked = isSelected;
        checkbox.addEventListener('change', function() {
            toggleVocabulary(vocab.id, this.checked);
        });
        
        vocabCard.appendChild(checkbox);
        
        if (vocab.image_url) {
            const img = document.createElement('img');
            img.src = vocab.image_url;
            img.alt = vocab.english_word;
            img.className = 'w-full h-20 object-cover rounded';
            vocabCard.appendChild(img);
        } else {
            const placeholder = document.createElement('div');
            placeholder.className = 'w-full h-20 bg-gray-200 rounded flex items-center justify-center text-gray-400';
            placeholder.innerHTML = '<i class="fas fa-image text-2xl"></i>';
            vocabCard.appendChild(placeholder);
        }
        
        const wordDiv = document.createElement('div');
        wordDiv.className = 'text-sm font-medium text-gray-800 text-center';
        wordDiv.textContent = vocab.english_word;
        vocabCard.appendChild(wordDiv);
        
        if (vocab.has_audio) {
            const audioIcon = document.createElement('i');
            audioIcon.className = 'fas fa-volume-up text-blue-500 text-xs';
            vocabCard.appendChild(audioIcon);
        }
        
        container.appendChild(vocabCard);
    });
    
    updateVocabSelectionCount();
}

function toggleVocabulary(vocabId, checked) {
    if (checked) {
        if (selectedVocabIds.size >= 30) {
            alert('Maximum 30 words allowed. Please deselect some words first.');
            // Find and uncheck the checkbox
            const checkbox = document.querySelector(`input[value="${vocabId}"].vocab-checkbox`);
            if (checkbox) checkbox.checked = false;
            return;
        }
        selectedVocabIds.add(vocabId);
    } else {
        selectedVocabIds.delete(vocabId);
    }
    
    renderVocabularyList();
    updateVocabSelectionCount();
}

function selectAllVocabulary() {
    const maxSelect = Math.min(30, allVocabulary.length);
    selectedVocabIds.clear();
    
    allVocabulary.slice(0, maxSelect).forEach(vocab => {
        selectedVocabIds.add(vocab.id);
    });
    
    if (allVocabulary.length > 30) {
        alert(`Only the first 30 words have been selected (maximum limit).`);
    }
    
    renderVocabularyList();
    updateVocabSelectionCount();
}

function updateVocabSelectionCount() {
    const count = selectedVocabIds.size;
    document.getElementById('vocab-selection-count').textContent = count;
    document.getElementById('review_vocabulary_ids').value = Array.from(selectedVocabIds).join(',');
    
    const errorEl = document.getElementById('vocab-error');
    if (count > 30) {
        errorEl.textContent = 'Maximum 30 words allowed.';
        errorEl.classList.remove('hidden');
    } else if (count < 2 && count > 0) {
        errorEl.textContent = 'Please select at least 2 words.';
        errorEl.classList.remove('hidden');
    } else {
        errorEl.classList.add('hidden');
    }
}

// Validate form before submit
document.getElementById('createReviewForm').addEventListener('submit', function(e) {
    const checkedBoxes = document.querySelectorAll('.review-source-checkbox:checked');
    if (checkedBoxes.length === 0) {
        e.preventDefault();
        alert('Please select at least one lesson to review.');
        return false;
    }
    
    if (selectedVocabIds.size < 2) {
        e.preventDefault();
        alert('Please select at least 2 vocabulary words for the review lesson.');
        return false;
    }
    
    if (selectedVocabIds.size > 30) {
        e.preventDefault();
        alert('Maximum 30 words allowed. Please select fewer words.');
        return false;
    }
});
</script>
@endsection

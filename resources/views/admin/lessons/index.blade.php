@extends('layouts.admin')

@section('title', 'Lessons')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Lessons</h1>
        <div class="flex gap-3">
            <button onclick="openCombineModal()" class="px-6 py-3 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">
                Combine Lessons
            </button>
            <a href="{{ route('admin.lessons.create') }}" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">
                Create Lesson
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-6 mb-6">
        <form method="GET" action="{{ route('admin.lessons.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            <div>
                <label for="grade_level" class="block text-sm font-semibold text-gray-700 mb-2">Grade Level</label>
                <select name="grade_level" id="grade_level" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-white text-gray-800 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
                    <option value="">All Grades</option>
                    @foreach($gradeLevels as $grade)
                        <option value="{{ $grade }}" {{ request('grade_level') == $grade ? 'selected' : '' }}>
                            Grade {{ $grade }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label for="session_number" class="block text-sm font-semibold text-gray-700 mb-2">Session Number</label>
                <select name="session_number" id="session_number" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-white text-gray-800 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
                    <option value="">All Sessions</option>
                    @foreach($sessionNumbers as $session)
                        <option value="{{ $session }}" {{ request('session_number') == $session ? 'selected' : '' }}>
                            Session {{ $session }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label for="search" class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                <input type="text" name="search" id="search" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200" 
                       placeholder="Search by title, session title, or slug..." value="{{ request('search') }}">
            </div>
            
            <div class="flex flex-col gap-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="view_archived" value="1" id="view_archived" 
                           {{ $showArchived ?? false ? 'checked' : '' }} class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-400">
                    <span class="text-sm font-medium text-gray-700">View Archived Lessons</span>
                </label>
                
                <!-- Preserve sort parameters -->
                @if(request('sort_by'))
                    <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                @endif
                @if(request('sort_dir'))
                    <input type="hidden" name="sort_dir" value="{{ request('sort_dir') }}">
                @endif
                
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 px-4 py-2.5 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">Filter</button>
                    <a href="{{ route('admin.lessons.index') }}" class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 active:scale-95 transition-all duration-200 text-center">Clear</a>
                </div>
            </div>
        </form>
    </div>

    @if($lessons->isEmpty())
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-12 text-center">
            @if(request()->hasAny(['grade_level', 'session_number', 'search', 'view_archived']))
                <div class="text-6xl mb-4">🔍</div>
                <h3 class="text-2xl font-bold text-gray-800 mb-3">No lessons found</h3>
                <p class="text-gray-600 mb-6">No lessons match your current filters. Try adjusting your search criteria or <a href="{{ route('admin.lessons.index') }}" class="text-blue-600 hover:text-blue-700 font-medium">clear all filters</a>.</p>
            @else
                <div class="text-6xl mb-4">📚</div>
                <h3 class="text-2xl font-bold text-gray-800 mb-3">No lessons yet</h3>
                <p class="text-gray-600 mb-6">You haven't created any lessons yet. <a href="{{ route('admin.lessons.create') }}" class="text-blue-600 hover:text-blue-700 font-medium">Create your first lesson</a> to get started.</p>
            @endif
        </div>
    @else
        <div class="mb-4 text-gray-600 font-medium">
            Showing {{ $lessons->count() }} lesson{{ $lessons->count() !== 1 ? 's' : '' }}
            @if(request()->hasAny(['grade_level', 'session_number', 'search', 'view_archived']))
                matching your filters
            @endif
        </div>
        
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50/80">
                        <tr>
                            <th class="px-6 py-4 text-left">
                                @php
                                    $newSortDir = ($sortBy == 'title' && $sortDir == 'asc') ? 'desc' : 'asc';
                                    $sortUrl = request()->fullUrlWithQuery(array_merge(request()->except(['sort_by', 'sort_dir']), ['sort_by' => 'title', 'sort_dir' => $newSortDir]));
                                @endphp
                                <a href="{{ $sortUrl }}" 
                                   class="inline-flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-blue-50 text-gray-700 hover:text-blue-600 font-semibold transition-all duration-200" title="Click to sort">
                                    <span>Title</span>
                                    @if($sortBy == 'title')
                                        <span class="text-blue-600">{{ $sortDir == 'asc' ? '↑' : '↓' }}</span>
                                    @else
                                        <span class="text-gray-400">↕</span>
                                    @endif
                                </a>
                            </th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Course</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Grade</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Session</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Part</th>
                            <th class="px-6 py-4 text-left">
                                @php
                                    $newSortDir = ($sortBy == 'slug' && $sortDir == 'asc') ? 'desc' : 'asc';
                                    $sortUrl = request()->fullUrlWithQuery(array_merge(request()->except(['sort_by', 'sort_dir']), ['sort_by' => 'slug', 'sort_dir' => $newSortDir]));
                                @endphp
                                <a href="{{ $sortUrl }}" 
                                   class="inline-flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-blue-50 text-gray-700 hover:text-blue-600 font-semibold transition-all duration-200" title="Click to sort">
                                    <span>Slug</span>
                                    @if($sortBy == 'slug')
                                        <span class="text-blue-600">{{ $sortDir == 'asc' ? '↑' : '↓' }}</span>
                                    @else
                                        <span class="text-gray-400">↕</span>
                                    @endif
                                </a>
                            </th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Activities</th>
                            <th class="px-6 py-4 text-left">
                                @php
                                    $newSortDir = ($sortBy == 'updated_at' && $sortDir == 'asc') ? 'desc' : 'asc';
                                    $sortUrl = request()->fullUrlWithQuery(array_merge(request()->except(['sort_by', 'sort_dir']), ['sort_by' => 'updated_at', 'sort_dir' => $newSortDir]));
                                @endphp
                                <a href="{{ $sortUrl }}" 
                                   class="inline-flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-blue-50 text-gray-700 hover:text-blue-600 font-semibold transition-all duration-200" title="Click to sort">
                                    <span>Last Modified</span>
                                    @if($sortBy == 'updated_at')
                                        <span class="text-blue-600">{{ $sortDir == 'asc' ? '↑' : '↓' }}</span>
                                    @else
                                        <span class="text-gray-400">↕</span>
                                    @endif
                                </a>
                            </th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Active</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200/60">
                        @foreach($lessons as $lesson)
                            <tr class="hover:bg-gray-50/50 transition-colors duration-150">
                                <td class="px-6 py-4">
                                    <strong class="text-gray-800">{{ $lesson->title }}</strong>
                                    @if($lesson->is_review)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-purple-100 text-purple-700">
                                            <i class="fas fa-redo mr-1"></i> Review
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($lesson->course)
                                        <a href="{{ route('admin.courses.show', $lesson->course) }}" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors">
                                            <i class="fas fa-book mr-1"></i> {{ $lesson->course->title }}
                                        </a>
                                    @else
                                        <span class="text-gray-400 text-sm">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ $lesson->grade_level ? 'Grade ' . $lesson->grade_level : '-' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $lesson->session_number ? 'Session ' . $lesson->session_number : '-' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $lesson->part_number ? 'Part ' . $lesson->part_number : '-' }}</td>
                                <td class="px-6 py-4"><code class="px-2 py-1 bg-gray-100 rounded text-sm text-gray-700">{{ $lesson->slug }}</code></td>
                                <td class="px-6 py-4 text-gray-600">
                                    @php
                                        $activityCount = ($lesson->prompts->count() > 0 ? 1 : 0) + $lesson->matchingGames->count() + $lesson->flashcardGames->count();
                                        $vocabCount = $lesson->vocabulary->count();
                                    @endphp
                                    <span class="font-medium">{{ $activityCount }}</span> activities
                                    @if($vocabCount > 0)
                                        <br><span class="text-sm text-gray-500">{{ $vocabCount }} vocab words</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    <div class="text-sm">{{ $lesson->updated_at->diffForHumans() }}</div>
                                    <div class="text-xs text-gray-500">{{ $lesson->updated_at->format('M d, Y') }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($lesson->is_active)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">✓ Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">✗ Inactive</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <a href="{{ route('admin.lessons.show', $lesson) }}" class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-all duration-200">View</a>
                                        <a href="{{ route('admin.lessons.manage', $lesson) }}" class="px-3 py-1.5 text-sm bg-blue-100 text-blue-700 font-medium rounded-lg hover:bg-blue-200 transition-all duration-200">Edit</a>
                                        <form action="{{ route('admin.lessons.archive', $lesson) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 text-sm bg-yellow-100 text-yellow-700 font-medium rounded-lg hover:bg-yellow-200 transition-all duration-200" onclick="return confirm('Are you sure you want to archive this lesson? Students will no longer be able to access it, but it can be restored from the archived lessons page.')">Archive</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<!-- Combine Lessons Modal -->
<div id="combineModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 items-center justify-center" style="display: none;">
    <div class="bg-white rounded-2xl shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-bold text-gray-800">Combine Lessons</h2>
                <button onclick="closeCombineModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
        </div>
        <form id="combineForm" class="p-6">
            @csrf
            <div class="mb-6">
                <label for="target_lesson_id" class="block text-sm font-semibold text-gray-700 mb-2">Select Target Lesson (to combine into)</label>
                <p class="text-sm text-gray-500 mb-3">All content from source lessons will be merged into this lesson</p>
                <select name="target_lesson_id" id="target_lesson_id" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-white text-gray-800 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
                    <option value="">-- Select Target Lesson --</option>
                    @foreach($lessons as $lesson)
                        <option value="{{ $lesson->id }}">
                            {{ $lesson->title }}
                            @if($lesson->course)
                                | {{ $lesson->course->title }}
                            @endif
                            @if($lesson->session_number)
                                | Session {{ $lesson->session_number }}
                            @endif
                            @if($lesson->part_number)
                                | Part {{ $lesson->part_number }}
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="mb-6">
                <div class="flex justify-between items-center mb-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Select Source Lessons (to combine from)</label>
                        <p class="text-sm text-gray-500">Select multiple lessons that will be merged into the target lesson</p>
                    </div>
                    <div class="ml-4">
                        <label for="course-filter" class="block text-xs font-semibold text-gray-700 mb-1">Filter by Course:</label>
                        <select id="course-filter" 
                                class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-white text-gray-800 focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                            <option value="">All Courses</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}">{{ $course->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="border border-gray-300 rounded-xl p-4 max-h-64 overflow-y-auto" id="lessons-list">
                    @foreach($lessons as $lesson)
                        <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded cursor-pointer lesson-item"
                               data-course-id="{{ $lesson->course_id ?? '' }}">
                            <input type="checkbox" name="source_lesson_ids[]" value="{{ $lesson->id }}" 
                                   class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-400 source-lesson-checkbox"
                                   data-title="{{ $lesson->title }}"
                                   data-session="{{ $lesson->session_number }}"
                                   data-part="{{ $lesson->part_number }}">
                            <div class="flex-1">
                                <div class="font-medium text-gray-800">{{ $lesson->title }}</div>
                                <div class="text-sm text-gray-500">
                                    @if($lesson->course) {{ $lesson->course->title }} @endif
                                    @if($lesson->session_number) | Session {{ $lesson->session_number }} @endif
                                    @if($lesson->part_number) | Part {{ $lesson->part_number }} @endif
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
                <div class="mt-2 text-sm text-gray-600">
                    <span id="selected-count">0</span> lesson(s) selected
                </div>
            </div>
            
            <div id="preview-section" class="mb-6 p-4 bg-blue-50 rounded-xl hidden">
                <h3 class="font-semibold text-gray-800 mb-2">Preview:</h3>
                <div id="preview-content" class="text-sm text-gray-600"></div>
            </div>
            
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeCombineModal()" 
                        class="px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-all duration-200">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-6 py-2.5 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        id="combine-submit-btn">
                    Combine Lessons
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openCombineModal() {
    document.getElementById('combineModal').style.display = 'flex';
    updatePreview();
}

function closeCombineModal() {
    document.getElementById('combineModal').style.display = 'none';
    document.getElementById('combineForm').reset();
    document.getElementById('preview-section').classList.add('hidden');
    document.getElementById('selected-count').textContent = '0';
}

function updatePreview() {
    const checkboxes = document.querySelectorAll('.source-lesson-checkbox:checked');
    const targetSelect = document.getElementById('target_lesson_id');
    const previewSection = document.getElementById('preview-section');
    const previewContent = document.getElementById('preview-content');
    const submitBtn = document.getElementById('combine-submit-btn');
    
    const selectedCount = checkboxes.length;
    document.getElementById('selected-count').textContent = selectedCount;
    
    if (selectedCount > 0 && targetSelect.value) {
        const targetOption = targetSelect.options[targetSelect.selectedIndex];
        const targetTitle = targetOption.text;
        
        const sourceTitles = Array.from(checkboxes).map(cb => {
            return cb.dataset.title + 
                   (cb.dataset.session ? ' (Session ' + cb.dataset.session + (cb.dataset.part ? ', Part ' + cb.dataset.part : '') + ')' : '');
        }).join('<br>');
        
        previewContent.innerHTML = `
            <strong>Target:</strong> ${targetTitle}<br>
            <strong>Sources (${selectedCount}):</strong><br>
            ${sourceTitles}<br><br>
            <em>Source lessons will be archived after combination.</em>
        `;
        previewSection.classList.remove('hidden');
        submitBtn.disabled = false;
    } else {
        previewSection.classList.add('hidden');
        submitBtn.disabled = true;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.source-lesson-checkbox').forEach(cb => {
        cb.addEventListener('change', updatePreview);
    });
    
    document.getElementById('target_lesson_id').addEventListener('change', updatePreview);
    
    // Course filter functionality
    document.getElementById('course-filter').addEventListener('change', function() {
        const selectedCourseId = this.value;
        const lessonItems = document.querySelectorAll('.lesson-item');
        
        lessonItems.forEach(item => {
            const courseId = item.dataset.courseId || '';
            if (selectedCourseId === '' || courseId === selectedCourseId) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
                // Uncheck hidden items
                const checkbox = item.querySelector('.source-lesson-checkbox');
                if (checkbox) {
                    checkbox.checked = false;
                }
            }
        });
        
        updatePreview();
    });
    
    document.getElementById('combineForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const sourceIds = Array.from(document.querySelectorAll('.source-lesson-checkbox:checked')).map(cb => cb.value);
        
        if (sourceIds.length === 0) {
            alert('Please select at least one source lesson.');
            return;
        }
        
        if (!formData.get('target_lesson_id')) {
            alert('Please select a target lesson.');
            return;
        }
        
        if (sourceIds.includes(formData.get('target_lesson_id'))) {
            alert('Target lesson cannot be one of the source lessons.');
            return;
        }
        
        if (!confirm('Are you sure you want to combine these lessons? Source lessons will be archived. This action can be undone.')) {
            return;
        }
        
        // Add source IDs to form data
        sourceIds.forEach(id => {
            formData.append('source_lesson_ids[]', id);
        });
        
        const submitBtn = document.getElementById('combine-submit-btn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Combining...';
        
        fetch('{{ route("admin.lessons.combine") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Lessons combined successfully!');
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to combine lessons'));
                submitBtn.disabled = false;
                submitBtn.textContent = 'Combine Lessons';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while combining lessons.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Combine Lessons';
        });
    });
});
</script>
@endpush
@endsection


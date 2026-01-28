@extends('layouts.admin')

@section('title', 'Lessons')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Lessons</h1>
        <div class="flex gap-3">
            <a href="{{ route('admin.lessons.create') }}" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">
                Create Lesson
            </a>
            <button onclick="openCreateReviewModal()" class="px-6 py-3 bg-purple-600 text-white font-semibold rounded-xl hover:bg-purple-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">
                Create Review Lesson
            </button>
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
        
        <form action="{{ route('admin.lessons.create') }}" method="GET" id="createReviewForm" class="p-6">
            <div class="space-y-6">
                <div>
                    <label for="review_title" class="block text-sm font-semibold text-gray-700 mb-2">Review Title <span class="text-red-500">*</span></label>
                    <input type="text" id="review_title" name="title" required 
                           placeholder="e.g., Review: Lessons 1-2"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
                </div>
                
                <div>
                    <label for="review_course_id" class="block text-sm font-semibold text-gray-700 mb-2">Course <span class="text-red-500">*</span></label>
                    <select id="review_course_id" name="course_id" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
                        <option value="">Select Course</option>
                        @foreach(\App\Models\Course::active()->ordered()->get() as $course)
                            <option value="{{ $course->id }}">{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Select Lessons to Review <span class="text-red-500">*</span></label>
                    <div class="border border-gray-300 rounded-xl p-4 max-h-64 overflow-y-auto bg-gray-50">
                        @foreach($lessons->where('is_active', true)->whereNull('archived_at') as $lesson)
                            <label class="flex items-center gap-3 p-2 hover:bg-white rounded-lg cursor-pointer transition-colors">
                                <input type="checkbox" name="review_source_lessons[]" value="{{ $lesson->id }}" 
                                       class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-400 review-source-checkbox">
                                <div class="flex-1">
                                    <span class="font-medium text-gray-800">{{ $lesson->title }}</span>
                                    @if($lesson->session_number)
                                        <span class="text-sm text-gray-500 ml-2">(Session {{ $lesson->session_number }})</span>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-2 text-sm text-gray-600">Select at least one lesson to review</p>
                </div>
                
                <input type="hidden" name="is_review" value="1">
                
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
function openCreateReviewModal() {
    document.getElementById('createReviewModal').classList.remove('hidden');
}

function closeCreateReviewModal() {
    document.getElementById('createReviewModal').classList.add('hidden');
    // Reset form
    document.getElementById('createReviewForm').reset();
    document.querySelectorAll('.review-source-checkbox').forEach(cb => cb.checked = false);
}

// Close modal when clicking outside
document.getElementById('createReviewModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCreateReviewModal();
    }
});

// Validate form before submit
document.getElementById('createReviewForm').addEventListener('submit', function(e) {
    const checkedBoxes = document.querySelectorAll('.review-source-checkbox:checked');
    if (checkedBoxes.length === 0) {
        e.preventDefault();
        alert('Please select at least one lesson to review.');
        return false;
    }
});
</script>
@endsection


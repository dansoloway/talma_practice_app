@extends('layouts.admin')

@section('title', 'Create Lesson')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-3xl">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Create Lesson</h1>
        <a href="{{ route('admin.lessons.index') }}" class="px-4 py-2 text-gray-700 font-medium rounded-xl hover:bg-gray-100 transition-all duration-200">Cancel</a>
    </div>

    <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-8">
        <form action="{{ route('admin.lessons.store') }}" method="POST" class="space-y-6">
            @csrf

            <div>
                <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">Title <span class="text-red-500">*</span></label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" required 
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
            </div>

            <div>
                <label for="slug" class="block text-sm font-semibold text-gray-700 mb-2">Slug</label>
                <input type="text" id="slug" name="slug" value="{{ old('slug') }}" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
                <p class="mt-2 text-sm text-gray-600">Leave blank to auto-generate. URL-friendly identifier (e.g., "colors" for /lessons/colors)</p>
            </div>

            <div>
                <label for="course_id" class="block text-sm font-semibold text-gray-700 mb-2">Course <span class="text-red-500">*</span></label>
                <select id="course_id" name="course_id" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
                    <option value="">Select Course</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ old('course_id', $selectedCourseId ?? '') == $course->id ? 'selected' : '' }}>
                            {{ $course->title }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-2 text-sm text-gray-600">The course this lesson belongs to</p>
            </div>

            <div>
                <label for="grade_level" class="block text-sm font-semibold text-gray-700 mb-2">Grade Level</label>
                <select id="grade_level" name="grade_level" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
                    <option value="">Select Grade Level</option>
                    <option value="K" {{ old('grade_level') == 'K' ? 'selected' : '' }}>Kindergarten (K)</option>
                    <option value="1" {{ old('grade_level') == '1' ? 'selected' : '' }}>1st Grade</option>
                    <option value="2" {{ old('grade_level') == '2' ? 'selected' : '' }}>2nd Grade</option>
                    <option value="3" {{ old('grade_level') == '3' ? 'selected' : '' }}>3rd Grade</option>
                    <option value="4" {{ old('grade_level') == '4' ? 'selected' : '' }}>4th Grade</option>
                    <option value="5" {{ old('grade_level') == '5' ? 'selected' : '' }}>5th Grade</option>
                    <option value="6" {{ old('grade_level') == '6' ? 'selected' : '' }}>6th Grade</option>
                    <option value="7" {{ old('grade_level') == '7' ? 'selected' : '' }}>7th Grade</option>
                    <option value="8" {{ old('grade_level') == '8' ? 'selected' : '' }}>8th Grade</option>
                    <option value="9" {{ old('grade_level') == '9' ? 'selected' : '' }}>9th Grade</option>
                    <option value="10" {{ old('grade_level') == '10' ? 'selected' : '' }}>10th Grade</option>
                    <option value="11" {{ old('grade_level') == '11' ? 'selected' : '' }}>11th Grade</option>
                    <option value="12" {{ old('grade_level') == '12' ? 'selected' : '' }}>12th Grade</option>
                </select>
            </div>

            <div>
                <label for="session_number" class="block text-sm font-semibold text-gray-700 mb-2">Session Number</label>
                <input type="number" id="session_number" name="session_number" value="{{ old('session_number') }}" min="1" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200" 
                       placeholder="e.g., 1, 2, 3...">
                <p class="mt-2 text-sm text-gray-600">Optional session number for this lesson</p>
            </div>

            <div>
                <label for="session_title" class="block text-sm font-semibold text-gray-700 mb-2">Session Title</label>
                <input type="text" id="session_title" name="session_title" value="{{ old('session_title') }}" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200" 
                       placeholder="e.g., Introduction to Colors, Environmental Vocabulary...">
                <p class="mt-2 text-sm text-gray-600">Optional descriptive title for this session</p>
            </div>

            <div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} 
                           class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-400">
                    <span class="text-gray-700 font-medium">Active</span>
                </label>
                <p class="mt-2 text-sm text-gray-600">Active lessons are visible to students</p>
            </div>

            <div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_review" value="1" {{ old('is_review') ? 'checked' : '' }} 
                           id="is_review_checkbox"
                           class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-400">
                    <span class="text-gray-700 font-medium">Review Lesson</span>
                </label>
                <p class="mt-2 text-sm text-gray-600">Review lessons combine vocabulary from other lessons</p>
            </div>

            <div id="review_sources_section" style="display: {{ old('is_review') ? 'block' : 'none' }};">
                <label for="review_source_lessons" class="block text-sm font-semibold text-gray-700 mb-2">Source Lessons <span class="text-red-500">*</span></label>
                <select id="review_source_lessons" name="review_source_lessons[]" multiple
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200"
                        style="min-height: 150px;">
                    @foreach($allLessons as $sourceLesson)
                        <option value="{{ $sourceLesson->id }}" {{ in_array($sourceLesson->id, old('review_source_lessons', [])) ? 'selected' : '' }}>
                            {{ $sourceLesson->title }} @if($sourceLesson->session_number)(Session {{ $sourceLesson->session_number }})@endif
                        </option>
                    @endforeach
                </select>
                <p class="mt-2 text-sm text-gray-600">Select the lessons this review lesson will combine vocabulary from</p>
            </div>

            <div class="flex gap-4 pt-4">
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">
                    Create Lesson
                </button>
                <a href="{{ route('admin.lessons.index') }}" class="px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 active:scale-95 transition-all duration-200">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isReviewCheckbox = document.getElementById('is_review_checkbox');
    const reviewSourcesSection = document.getElementById('review_sources_section');
    const reviewSourceSelect = document.getElementById('review_source_lessons');
    
    if (isReviewCheckbox && reviewSourcesSection) {
        isReviewCheckbox.addEventListener('change', function() {
            if (this.checked) {
                reviewSourcesSection.style.display = 'block';
                reviewSourceSelect.setAttribute('required', 'required');
            } else {
                reviewSourcesSection.style.display = 'none';
                reviewSourceSelect.removeAttribute('required');
            }
        });
    }
});
</script>
@endsection


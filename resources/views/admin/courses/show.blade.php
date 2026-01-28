@extends('layouts.admin')

@section('title', $course->title)

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">{{ $course->title }}</h1>
        <div class="flex gap-3">
            <a href="{{ route('admin.courses.edit', $course) }}" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200">
                Edit Course
            </a>
            <a href="{{ route('admin.courses.index') }}" class="px-4 py-2 text-gray-700 font-medium rounded-xl hover:bg-gray-100 transition-all duration-200">
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
                        @if($course->is_active)
                            <span class="px-3 py-1 text-sm font-semibold bg-green-100 text-green-700 rounded-full">Active</span>
                        @else
                            <span class="px-3 py-1 text-sm font-semibold bg-gray-100 text-gray-700 rounded-full">Inactive</span>
                        @endif
                    </div>
                    
                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 mb-1">Sort Order</h3>
                        <p class="text-gray-800">{{ $course->sort_order }}</p>
                    </div>
                    
                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 mb-1">Lessons</h3>
                        <p class="text-gray-800 font-bold text-lg">{{ $course->lessons->count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lessons List -->
        <div class="lg:col-span-2">
            <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Lessons</h2>
                    <a href="{{ route('admin.lessons.create', ['course_id' => $course->id]) }}" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200">
                        Add Lesson
                    </a>
                </div>

                @if($course->lessons->isEmpty())
                    <div class="text-center py-12">
                        <div class="text-6xl mb-4">📚</div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">No lessons yet</h3>
                        <p class="text-gray-600 mb-6">This course doesn't have any lessons yet.</p>
                        <a href="{{ route('admin.lessons.create', ['course_id' => $course->id]) }}" class="inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200">
                            Add First Lesson
                        </a>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($course->lessons->sortBy('session_number') as $lesson)
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
@endsection

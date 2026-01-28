@extends('layouts.app')

@section('title', 'TALMA Practice Pal - Choose Your Course')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-8">
    <div class="container mx-auto px-4 max-w-5xl">
        <!-- Welcome Section -->
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4 tracking-tight">
                Welcome to TALMA Practice Pal!
            </h1>
            <p class="text-lg md:text-xl text-gray-600 font-medium">
                Practice English with fun activities
            </p>
        </div>

        <!-- Course Selection -->
        <div class="mb-8">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800 text-center mb-8">
                Choose Your Course
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($courses as $course)
                    <a href="{{ route('student.course', $course->slug) }}" 
                       class="group relative bg-white rounded-2xl border-2 border-gray-200 shadow-sm hover:shadow-xl hover:border-blue-300 hover:-translate-y-1 transition-all duration-300 cursor-pointer block overflow-hidden">
                        @if($course->cover_image_path)
                            <div class="h-48 overflow-hidden">
                                <img src="{{ $course->cover_image_url }}" 
                                     alt="{{ $course->title }}" 
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            </div>
                        @endif
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-blue-700 transition-colors duration-200">
                                {{ $course->title }}
                            </h3>
                            @if($course->description)
                                <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                                    {{ $course->description }}
                                </p>
                            @endif
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">
                                    {{ $course->lessons_count }} {{ $course->lessons_count === 1 ? 'lesson' : 'lessons' }}
                                </span>
                                <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                    <i class="fas fa-chevron-right text-blue-500"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full">
                        <div class="bg-white rounded-2xl border-2 border-gray-200 p-12 text-center shadow-sm">
                            <div class="text-6xl mb-4">📚</div>
                            <h3 class="text-2xl font-bold text-gray-800 mb-3">No courses available yet</h3>
                            <p class="text-gray-600">Please check back later for new courses!</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', 'Courses')

@php
    $params = $courseRouteParams ?? [];
    $route = fn($name, $course = null) => isset($params['organization'])
        ? route('org.admin.courses.' . $name, array_merge($params, $course ? ['course' => $course] : []))
        : ($course ? route('admin.courses.' . $name, $course) : route('admin.courses.' . $name));
@endphp

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Courses</h1>
        <a href="{{ $route('create') }}" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">
            Create Course
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-6 mb-6">
        <form method="GET" action="{{ $route('index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label for="search" class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                <input type="text" name="search" id="search" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200" 
                       placeholder="Search by title or slug..." value="{{ request('search') }}">
            </div>
            
            <div class="flex flex-col gap-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="view_archived" value="1" id="view_archived" 
                           {{ $showArchived ?? false ? 'checked' : '' }} class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-400">
                    <span class="text-sm font-medium text-gray-700">View Archived Courses</span>
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
                    <a href="{{ $route('index') }}" class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 active:scale-95 transition-all duration-200 text-center">Clear</a>
                </div>
            </div>
        </form>
    </div>

    @if($courses->isEmpty())
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-12 text-center">
            @if(request()->has('search'))
                <div class="text-6xl mb-4">🔍</div>
                <h3 class="text-2xl font-bold text-gray-800 mb-3">No courses found</h3>
                <p class="text-gray-600 mb-6">No courses match your search. Try adjusting your search criteria or <a href="{{ $route('index') }}" class="text-blue-600 hover:text-blue-700 font-medium">clear filters</a>.</p>
            @else
                <div class="text-6xl mb-4">📚</div>
                <h3 class="text-2xl font-bold text-gray-800 mb-3">No courses yet</h3>
                <p class="text-gray-600 mb-6">You haven't created any courses yet. <a href="{{ $route('create') }}" class="text-blue-600 hover:text-blue-700 font-medium">Create your first course</a> to get started.</p>
            @endif
        </div>
    @else
        <div class="mb-4 text-gray-600 font-medium">
            Showing {{ $courses->count() }} course{{ $courses->count() !== 1 ? 's' : '' }}
            @if(request()->has('search'))
                matching your search
            @endif
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($courses as $course)
                <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm overflow-hidden hover:shadow-lg transition-shadow duration-200">
                    @if($course->cover_image_path)
                        <div class="h-48 overflow-hidden">
                            <img src="{{ $course->cover_image_url }}" alt="{{ $course->title }}" class="w-full h-full object-cover">
                        </div>
                    @endif
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-2">{{ $course->title }}</h3>
                        @if($course->description)
                            <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ $course->description }}</p>
                        @endif
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm text-gray-500">
                                {{ $course->lessons_count }} {{ $course->lessons_count === 1 ? 'lesson' : 'lessons' }}
                            </span>
                            <div class="flex flex-wrap gap-2 justify-end">
                                @if(isset($courseRouteParams['organization']))
                                    @php $isOrgWide = (bool) ($course->pivot->is_org_wide ?? false); @endphp
                                    <form method="POST" action="{{ route('org.admin.courses.toggle-org-wide', array_merge($courseRouteParams, ['course' => $course])) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2 py-1 text-xs font-semibold rounded-full transition-colors {{ $isOrgWide ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}" title="{{ $isOrgWide ? 'Org-wide (visible to all org members)' : 'Class-only' }}">
                                            {{ $isOrgWide ? 'Org-wide' : 'Class-only' }}
                                        </button>
                                    </form>
                                @endif
                                @if($course->isArchived())
                                    <span class="px-2 py-1 text-xs font-semibold bg-yellow-100 text-yellow-700 rounded-full">
                                        <i class="fas fa-archive mr-1"></i> Archived
                                    </span>
                                @endif
                                @if($course->is_active)
                                    <span class="px-2 py-1 text-xs font-semibold bg-green-100 text-green-700 rounded-full">Active</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold bg-gray-100 text-gray-700 rounded-full">Inactive</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ $route('show', $course) }}" class="flex-1 px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-all duration-200 text-center">
                                View
                            </a>
                            <a href="{{ $route('edit', $course) }}" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-all duration-200 text-center">
                                Edit
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

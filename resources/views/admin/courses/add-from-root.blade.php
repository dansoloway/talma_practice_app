@extends('layouts.admin')

@section('title', 'Add from Root')

@php
    $params = $courseRouteParams ?? [];
@endphp

@section('content')
<div class="container mx-auto px-4 py-6 max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('org.admin.courses.index', ['organization' => $organization->slug]) }}" class="text-blue-600 hover:text-blue-700 font-medium">
            <i class="fas fa-arrow-left mr-2"></i>Back to Courses
        </a>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-2">Add from Root</h1>
    <p class="text-gray-600 mb-6">Attach a Root (canonical) course to {{ $organization->name }}. The course stays synced; changes in Root propagate to all orgs.</p>

    @if($rootCourses->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-200 p-8 text-center">
            <p class="text-gray-600">No Root courses available to add. All Root courses are already attached to this organization, or Root has no courses yet.</p>
            <a href="{{ route('org.admin.courses.index', ['organization' => $organization->slug]) }}" class="inline-block mt-4 text-blue-600 hover:text-blue-700 font-medium">Back to Courses</a>
        </div>
    @else
        <div class="space-y-3">
            @foreach($rootCourses as $course)
                <form method="POST" action="{{ route('org.admin.courses.attach-from-root', ['organization' => $organization->slug]) }}" class="block">
                    @csrf
                    <input type="hidden" name="course_id" value="{{ $course->id }}">
                    <input type="hidden" name="is_org_wide" value="1">
                    <button type="submit" class="w-full text-left px-6 py-4 border border-gray-200 rounded-xl hover:border-blue-400 hover:bg-blue-50/50 transition-all duration-200 flex items-center justify-between">
                        <span class="font-semibold text-gray-800">{{ $course->title }}</span>
                        <span class="text-sm text-gray-500">{{ $course->lessons_count }} {{ $course->lessons_count === 1 ? 'lesson' : 'lessons' }}</span>
                    </button>
                </form>
            @endforeach
        </div>
    @endif
</div>
@endsection

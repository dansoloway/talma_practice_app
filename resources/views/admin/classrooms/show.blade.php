@extends('layouts.admin')

@section('title', $classroom->name)

@section('content')
<div class="container mx-auto px-4 py-6 max-w-5xl">
    <div class="mb-6 flex justify-between items-center">
        <a href="{{ route('org.admin.classrooms.index', ['organization' => $organization->slug]) }}" class="text-blue-600 hover:text-blue-700 font-medium">
            <i class="fas fa-arrow-left mr-2"></i>Back to Classes
        </a>
        <div class="flex gap-2">
            <a href="{{ route('org.admin.classrooms.edit', ['organization' => $organization->slug, 'classroom' => $classroom->slug]) }}" class="px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200">Edit</a>
            <form method="POST" action="{{ route('org.admin.classrooms.destroy', ['organization' => $organization->slug, 'classroom' => $classroom->slug]) }}" onsubmit="return confirm('Delete this class?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-100 text-red-700 font-semibold rounded-lg hover:bg-red-200">Delete</button>
            </form>
        </div>
    </div>
    <h1 class="text-3xl font-bold text-gray-800 mb-8">{{ $classroom->name }}</h1>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Students</h2>
            <form method="POST" action="{{ route('org.admin.classrooms.sync-students', ['organization' => $organization->slug, 'classroom' => $classroom->slug]) }}">
                @csrf
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @foreach($orgMembers as $user)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" {{ $classroom->students->contains($user) ? 'checked' : '' }}>
                            <span>{{ $user->name }} ({{ $user->email }})</span>
                        </label>
                    @endforeach
                </div>
                @if($orgMembers->isEmpty())
                    <p class="text-gray-500 text-sm">No org members. Add users to the organization first.</p>
                @else
                    <button type="submit" class="mt-4 px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700">Update Students</button>
                @endif
            </form>
        </div>
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Teachers</h2>
            <form method="POST" action="{{ route('org.admin.classrooms.sync-teachers', ['organization' => $organization->slug, 'classroom' => $classroom->slug]) }}">
                @csrf
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @foreach($orgMembers as $user)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" {{ $classroom->teachers->contains($user) ? 'checked' : '' }}>
                            <span>{{ $user->name }} ({{ $user->email }})</span>
                        </label>
                    @endforeach
                </div>
                @if($orgMembers->isEmpty())
                    <p class="text-gray-500 text-sm">No org members.</p>
                @else
                    <button type="submit" class="mt-4 px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700">Update Teachers</button>
                @endif
            </form>
        </div>
    </div>
    <div class="mt-6 bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Courses</h2>
        <p class="text-gray-600 text-sm mb-4">Assign courses to this class. Students in the class can access class-only courses.</p>
        <form method="POST" action="{{ route('org.admin.classrooms.sync-courses', ['organization' => $organization->slug, 'classroom' => $classroom->slug]) }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-48 overflow-y-auto">
                @foreach($orgCourses as $course)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="course_ids[]" value="{{ $course->id }}" {{ $classroom->courses->contains($course) ? 'checked' : '' }}>
                        <span>{{ $course->title }}</span>
                    </label>
                @endforeach
            </div>
            @if($orgCourses->isEmpty())
                <p class="text-gray-500 text-sm">No courses in this org. Add courses first.</p>
            @else
                <button type="submit" class="mt-4 px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700">Update Courses</button>
            @endif
        </form>
    </div>
</div>
@endsection

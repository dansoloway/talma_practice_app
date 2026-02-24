@extends('layouts.admin')

@section('title', 'Classes')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Classes</h1>
        <a href="{{ route('org.admin.classrooms.create', ['organization' => $org->slug]) }}" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">
            Create Class
        </a>
    </div>

    @if($classes->isEmpty())
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-12 text-center">
            <div class="text-6xl mb-4">📚</div>
            <h3 class="text-2xl font-bold text-gray-800 mb-3">No classes yet</h3>
            <p class="text-gray-600 mb-6">Create your first class to assign students, teachers, and courses.</p>
            <a href="{{ route('org.admin.classrooms.create', ['organization' => $org->slug]) }}" class="inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200">
                Create Class
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($classes as $classroom)
                <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm overflow-hidden hover:shadow-lg transition-shadow duration-200">
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-2">{{ $classroom->name }}</h3>
                        <div class="flex flex-wrap gap-2 mb-4 text-sm text-gray-500">
                            <span><i class="fas fa-user-graduate mr-1"></i>{{ $classroom->students_count }} students</span>
                            <span><i class="fas fa-chalkboard-teacher mr-1"></i>{{ $classroom->teachers_count }} teachers</span>
                            <span><i class="fas fa-book mr-1"></i>{{ $classroom->courses_count }} courses</span>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('org.admin.classrooms.show', ['organization' => $org->slug, 'classroom' => $classroom->slug]) }}" class="flex-1 px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-all duration-200 text-center">View</a>
                            <a href="{{ route('org.admin.classrooms.edit', ['organization' => $org->slug, 'classroom' => $classroom->slug]) }}" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-all duration-200 text-center">Edit</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

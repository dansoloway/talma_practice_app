@extends('layouts.admin')

@section('title', 'Edit Course')

@php
    $params = $courseRouteParams ?? [];
    $route = fn($name, $course = null) => isset($params['organization'])
        ? route('org.admin.courses.' . $name, array_merge($params, $course ? ['course' => $course] : []))
        : ($course ? route('admin.courses.' . $name, $course) : route('admin.courses.' . $name));
@endphp

@section('content')
<div class="container mx-auto px-4 py-6 max-w-3xl">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Edit Course</h1>
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
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white font-medium rounded-xl hover:bg-green-700 transition-all duration-200">
                        Unarchive Course
                    </button>
                </form>
            @else
                <form action="{{ $route('archive', $course) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-yellow-600 text-white font-medium rounded-xl hover:bg-yellow-700 transition-all duration-200" 
                            onclick="return confirm('Are you sure you want to archive this course? Students will no longer be able to access it, but it can be restored later.')">
                        Archive Course
                    </button>
                </form>
            @endif
            <a href="{{ $route('index') }}" class="px-4 py-2 text-gray-700 font-medium rounded-xl hover:bg-gray-100 transition-all duration-200">Cancel</a>
        </div>
    </div>

    <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-8">
        <form action="{{ $route('update', $course) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">Title <span class="text-red-500">*</span></label>
                <input type="text" id="title" name="title" value="{{ old('title', $course->title) }}" required 
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
            </div>

            <div>
                <label for="slug" class="block text-sm font-semibold text-gray-700 mb-2">Slug</label>
                <input type="text" id="slug" name="slug" value="{{ old('slug', $course->slug) }}" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
                <p class="mt-2 text-sm text-gray-600">Leave blank to auto-generate. URL-friendly identifier</p>
            </div>

            <div>
                <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                <textarea id="description" name="description" rows="4" 
                          class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">{{ old('description', $course->description) }}</textarea>
            </div>

            <div>
                <label for="cover_image" class="block text-sm font-semibold text-gray-700 mb-2">Cover Image</label>
                @if($course->cover_image_path)
                    <div class="mb-4">
                        <img src="{{ $course->cover_image_url }}" alt="{{ $course->title }}" class="max-w-xs rounded-lg border border-gray-200">
                        <p class="mt-2 text-sm text-gray-600">Current cover image</p>
                    </div>
                @endif
                <input type="file" id="cover_image" name="cover_image" accept="image/*" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
                <p class="mt-2 text-sm text-gray-600">Upload a new image to replace the current one</p>
            </div>

            <div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $course->is_active) ? 'checked' : '' }} 
                           class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-400">
                    <span class="text-gray-700 font-medium">Active</span>
                </label>
                <p class="mt-2 text-sm text-gray-600">Active courses are visible to students</p>
            </div>

            @php
                $flowLabels = \App\Models\Course::guidedFlowTypeLabels();
                $currentFlow = old('guided_flow', $course->guidedFlowOrDefault());
            @endphp
            <div class="border-t border-gray-200 pt-6">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="guided_mode_enabled" value="1" id="guided_mode_enabled"
                           {{ old('guided_mode_enabled', $course->guided_mode_enabled) ? 'checked' : '' }}
                           class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-400">
                    <span class="text-gray-700 font-medium">Enable guided lesson mode</span>
                </label>
                <p class="mt-2 text-sm text-gray-600">Students follow a recommended activity sequence instead of choosing freely.</p>

                <div id="guided-flow-editor" class="mt-4 {{ old('guided_mode_enabled', $course->guided_mode_enabled) ? '' : 'hidden' }}">
                    <p class="text-sm font-semibold text-gray-700 mb-2">Activity order</p>
                    <ul id="guided-flow-list" class="space-y-2">
                        @foreach($currentFlow as $type)
                            <li class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2" data-type="{{ $type }}">
                                <input type="hidden" name="guided_flow[]" value="{{ $type }}">
                                <span class="flex-1 text-sm text-gray-800">{{ $flowLabels[$type] ?? $type }}</span>
                                <button type="button" class="guided-flow-up px-2 py-1 text-xs text-gray-600 hover:bg-gray-200 rounded" aria-label="Move up">↑</button>
                                <button type="button" class="guided-flow-down px-2 py-1 text-xs text-gray-600 hover:bg-gray-200 rounded" aria-label="Move down">↓</button>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-2 text-xs text-gray-500">Steps missing from a lesson are skipped automatically.</p>
                </div>
            </div>

            <div class="flex gap-4 pt-4">
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">
                    Update Course
                </button>
                <a href="{{ $route('index') }}" class="px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 active:scale-95 transition-all duration-200">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('guided_mode_enabled');
    const editor = document.getElementById('guided-flow-editor');
    const list = document.getElementById('guided-flow-list');

    toggle?.addEventListener('change', () => {
        editor.classList.toggle('hidden', !toggle.checked);
    });

    list?.addEventListener('click', (event) => {
        const item = event.target.closest('li');
        if (!item) return;

        if (event.target.classList.contains('guided-flow-up')) {
            const prev = item.previousElementSibling;
            if (prev) list.insertBefore(item, prev);
        }

        if (event.target.classList.contains('guided-flow-down')) {
            const next = item.nextElementSibling;
            if (next) list.insertBefore(next, item);
        }
    });
});
</script>
@endpush
@endsection

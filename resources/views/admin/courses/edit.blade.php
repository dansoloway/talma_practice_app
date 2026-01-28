@extends('layouts.admin')

@section('title', 'Edit Course')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-3xl">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Edit Course</h1>
        <a href="{{ route('admin.courses.index') }}" class="px-4 py-2 text-gray-700 font-medium rounded-xl hover:bg-gray-100 transition-all duration-200">Cancel</a>
    </div>

    <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-8">
        <form action="{{ route('admin.courses.update', $course) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
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
                <label for="sort_order" class="block text-sm font-semibold text-gray-700 mb-2">Sort Order</label>
                <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $course->sort_order) }}" min="0" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
                <p class="mt-2 text-sm text-gray-600">Lower numbers appear first</p>
            </div>

            <div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $course->is_active) ? 'checked' : '' }} 
                           class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-400">
                    <span class="text-gray-700 font-medium">Active</span>
                </label>
                <p class="mt-2 text-sm text-gray-600">Active courses are visible to students</p>
            </div>

            <div class="flex gap-4 pt-4">
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">
                    Update Course
                </button>
                <a href="{{ route('admin.courses.index') }}" class="px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 active:scale-95 transition-all duration-200">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', 'Create Class')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('org.admin.classrooms.index', ['organization' => $org->slug]) }}" class="text-blue-600 hover:text-blue-700 font-medium">
            <i class="fas fa-arrow-left mr-2"></i>Back to Classes
        </a>
    </div>
    <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Create Class</h1>
        <form method="POST" action="{{ route('org.admin.classrooms.store', ['organization' => $org->slug]) }}">
            @csrf
            <div class="mb-4">
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="mb-4">
                <label for="slug" class="block text-sm font-semibold text-gray-700 mb-2">Slug (optional)</label>
                <input type="text" name="slug" id="slug" value="{{ old('slug') }}"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-400 focus:border-blue-400" placeholder="auto-generated from name">
                @error('slug')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700">Create</button>
                <a href="{{ route('org.admin.classrooms.index', ['organization' => $org->slug]) }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

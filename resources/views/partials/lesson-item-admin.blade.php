<div class="flex items-center justify-between p-4 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors duration-200 {{ $lesson->is_review ? 'border-purple-200 bg-purple-50/30' : '' }}">
    <div class="flex-1 min-w-0">
        <div class="flex flex-wrap items-center gap-2 mb-1">
            @if($lesson->is_review)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">
                    Review
                </span>
            @endif
            @if($lesson->partLabel())
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                    {{ $lesson->partLabel() }}
                </span>
            @endif
        </div>
        <h3 class="font-semibold text-gray-800 truncate">{{ $lesson->title }}</h3>
        @if($lesson->grade_level)
            <p class="text-sm text-gray-500 mt-1">Grade {{ $lesson->grade_level }}</p>
        @endif
    </div>
    <div class="flex gap-2 flex-shrink-0 ml-4">
        <a href="{{ route('admin.lessons.show', $lesson) }}" class="px-3 py-1 bg-blue-100 text-blue-700 font-medium rounded-lg hover:bg-blue-200 transition-all duration-200">
            View
        </a>
        <a href="{{ route('admin.lessons.edit', $lesson) }}" class="px-3 py-1 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-all duration-200">
            Edit
        </a>
    </div>
</div>

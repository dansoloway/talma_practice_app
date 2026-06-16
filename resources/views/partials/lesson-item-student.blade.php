@php
    $lessonUrl = isset($org) && $org
        ? route('org.student.lesson', [$org, $lesson->slug])
        : route('lessons.show', $lesson->slug);
@endphp

<a href="{{ $lessonUrl }}"
   class="group relative {{ $lesson->is_review ? 'bg-purple-50 border-purple-300 hover:border-purple-400' : 'bg-white border-gray-200 hover:border-blue-300' }} rounded-2xl border-2 p-6 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 cursor-pointer block">

    <div class="flex flex-col h-full">
        @if($lesson->cover_image_path)
            <div class="mb-4 -mx-6 -mt-6 rounded-t-2xl overflow-hidden">
                <img src="{{ $lesson->cover_image_url }}"
                     alt="{{ $lesson->title }}"
                     class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300">
            </div>
        @endif

        <div class="flex justify-between items-start mb-4">
            <div class="flex flex-wrap gap-2">
                @if($lesson->is_review)
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-purple-200 text-purple-800 border border-purple-300">
                        <i class="fas fa-redo mr-1"></i> Review
                    </span>
                @endif
                @if($lesson->partLabel())
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700 border border-purple-200">
                        {{ $lesson->partLabel() }}
                    </span>
                @endif
            </div>

            <div class="flex-shrink-0 w-8 h-8 rounded-full {{ $lesson->is_review ? 'bg-purple-50 group-hover:bg-purple-100' : 'bg-blue-50 group-hover:bg-blue-100' }} flex items-center justify-center transition-all duration-300 group-hover:translate-x-1">
                <i class="fas fa-chevron-right {{ $lesson->is_review ? 'text-purple-600' : 'text-blue-600' }} text-sm"></i>
            </div>
        </div>

        <h3 class="text-xl font-bold text-gray-800 mb-4 group-hover:text-blue-700 transition-colors duration-200 leading-tight">
            {{ $lesson->title }}
        </h3>

        <div class="flex flex-wrap gap-4 mt-auto pt-4 border-t border-gray-100">
            @if($lesson->relationLoaded('vocabulary') && $lesson->vocabulary && $lesson->vocabulary->count() > 0)
                <span class="inline-flex items-center text-sm text-gray-600">
                    <i class="fas fa-book mr-2 text-blue-500"></i>
                    <span class="font-medium">{{ $lesson->vocabulary->count() }}</span>
                    <span class="ml-1">words</span>
                </span>
            @endif

            @php
                $activityCount = ($lesson->relationLoaded('prompts') ? $lesson->prompts->count() : 0)
                    + ($lesson->relationLoaded('matchingGames') ? $lesson->matchingGames->count() : 0)
                    + ($lesson->relationLoaded('flashcardGames') ? $lesson->flashcardGames->count() : 0);
            @endphp

            @if($activityCount > 0)
                <span class="inline-flex items-center text-sm text-gray-600">
                    <i class="fas fa-gamepad mr-2 text-purple-500"></i>
                    <span class="font-medium">{{ $activityCount }}</span>
                    <span class="ml-1">activities</span>
                </span>
            @endif
        </div>
    </div>
</a>

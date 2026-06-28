@php
    $lessonProgress = $lessonProgress ?? [];
    $lessonUrl = isset($org) && $org
        ? route('org.student.lesson', [$org, $lesson->slug])
        : route('lessons.show', $lesson->slug);
    $wordCount = $lesson->relationLoaded('vocabulary') && $lesson->vocabulary
        ? $lesson->vocabulary->count()
        : 0;
    $activityCount = $lesson->studentActivityCount();
    $completionPercent = ($lessonProgress ?? [])[$lesson->id] ?? 0;
    $isComplete = $completionPercent >= 100;
    $cardLabel = $lesson->studentCardLabel();
@endphp

<a href="{{ $lessonUrl }}"
   class="lesson-card-compact group block bg-white border rounded-lg overflow-hidden transition-colors duration-200 {{ $isComplete ? 'border-green-200 hover:border-green-400' : 'border-gray-200 hover:border-blue-400' }} {{ $lesson->is_review && ! $isComplete ? 'border-purple-200 hover:border-purple-400' : '' }}">

    <div class="px-[14px] pt-[14px] pb-[12px]">
        @if($cardLabel)
            <div class="text-[11px] font-semibold uppercase tracking-[0.04em] text-gray-400 mb-1.5">
                {{ $cardLabel }}
            </div>
        @endif

        <h3 class="text-[14px] font-medium text-gray-800 leading-snug mb-3 group-hover:text-blue-700 transition-colors duration-200 line-clamp-3">
            {{ $lesson->studentCardTitle() }}
        </h3>

        <div class="flex items-center gap-3 text-gray-500">
            @if($wordCount > 0)
                <span class="inline-flex items-center gap-1 text-xs font-medium" title="Words">
                    <i class="fas fa-book text-[11px] text-blue-500" aria-hidden="true"></i>
                    <span>{{ $wordCount }}</span>
                </span>
            @endif

            @if($activityCount > 0)
                <span class="inline-flex items-center gap-1 text-xs font-medium" title="Activities">
                    <i class="fas fa-puzzle-piece text-[11px] text-violet-500" aria-hidden="true"></i>
                    <span>{{ $activityCount }}</span>
                </span>
            @endif

            @if($isComplete)
                <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-600" title="Lesson complete">
                    <i class="fas fa-circle-check text-[11px]" aria-hidden="true"></i>
                    <span>Complete</span>
                </span>
            @endif
        </div>
    </div>

    <div class="h-[3px] bg-gray-100" role="progressbar" aria-valuenow="{{ $completionPercent }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $isComplete ? 'Lesson complete' : 'Lesson progress' }}">
        <div class="h-full transition-all duration-300 {{ $isComplete ? 'bg-green-500' : ($lesson->is_review ? 'bg-purple-500' : 'bg-blue-500') }}"
             style="width: {{ $completionPercent }}%"></div>
    </div>
</a>

@php
    $lessonGroups = $lessonGroups ?? ['sessions' => [], 'review' => collect(), 'ungrouped' => collect()];
    $mode = $mode ?? 'student';
    $lessonProgress = $lessonProgress ?? [];
    $filteredSession = request('session_number');
    $hasContent = count($lessonGroups['sessions']) > 0
        || $lessonGroups['review']->isNotEmpty()
        || $lessonGroups['ungrouped']->isNotEmpty();

    if ($mode === 'student' && $hasContent) {
        $studentLessons = collect($lessonGroups['sessions'])
            ->flatMap(fn (array $sessionGroup) => $sessionGroup['lessons'])
            ->merge($lessonGroups['ungrouped'])
            ->merge($lessonGroups['review'])
            ->values();
    }
@endphp

@if(!$hasContent)
    <div class="{{ $mode === 'student' ? 'col-span-full' : '' }}">
        <div class="bg-white rounded-xl border border-gray-200 p-10 text-center">
            @if(request()->hasAny(['session_number', 'part_number', 'search']))
                <div class="text-5xl mb-3">🔍</div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">No lessons found</h3>
                <p class="text-gray-600 mb-5">No lessons match your current filters. Try adjusting your search.</p>
                @if(isset($clearFiltersUrl))
                    <a href="{{ $clearFiltersUrl }}" class="inline-block px-5 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-all duration-200">
                        Clear filters
                    </a>
                @endif
            @else
                <div class="text-5xl mb-3">📚</div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">No lessons available</h3>
                <p class="text-gray-600 mb-5">Please check back later for new lessons!</p>
            @endif
        </div>
    </div>
@elseif($mode === 'student')
    <div class="lesson-grid">
        @foreach($studentLessons as $lesson)
            @include('partials.lesson-item-student', [
                'lesson' => $lesson,
                'org' => $org ?? null,
                'course' => $course ?? null,
                'lessonProgress' => $lessonProgress,
            ])
        @endforeach
    </div>
@else
    @foreach($lessonGroups['sessions'] as $index => $sessionGroup)
        @include('partials.lesson-session-accordion', [
            'mode' => $mode,
            'accordionId' => 'session-' . $sessionGroup['session_number'],
            'sessionNumber' => $sessionGroup['session_number'],
            'label' => $sessionGroup['label'],
            'lessons' => $sessionGroup['lessons'],
            'multiLesson' => $sessionGroup['lessons']->count() > 1,
            'expanded' => $filteredSession
                ? (int) $filteredSession === $sessionGroup['session_number']
                : $index === 0,
            'org' => $org ?? null,
            'course' => $course ?? null,
        ])
    @endforeach

    @if($lessonGroups['ungrouped']->isNotEmpty())
        @include('partials.lesson-session-accordion', [
            'mode' => $mode,
            'accordionId' => 'other-lessons',
            'sessionNumber' => null,
            'label' => 'Other Lessons',
            'lessons' => $lessonGroups['ungrouped'],
            'multiLesson' => $lessonGroups['ungrouped']->count() > 1,
            'expanded' => empty($lessonGroups['sessions']) && $lessonGroups['review']->isEmpty(),
            'org' => $org ?? null,
            'course' => $course ?? null,
        ])
    @endif

    @if($lessonGroups['review']->isNotEmpty())
        @include('partials.lesson-session-accordion', [
            'mode' => $mode,
            'accordionId' => 'review-lessons',
            'sessionNumber' => null,
            'label' => 'Review Lessons',
            'lessons' => $lessonGroups['review'],
            'multiLesson' => $lessonGroups['review']->count() > 1,
            'expanded' => false,
            'org' => $org ?? null,
            'course' => $course ?? null,
        ])
    @endif
@endif

@once
    @push('scripts')
    <script>
        function toggleSessionAccordion(accordionId) {
            const content = document.getElementById(accordionId + '-content');
            const chevron = document.getElementById(accordionId + '-chevron');
            const button = content?.previousElementSibling;

            if (!content) return;

            const isHidden = content.classList.contains('hidden');
            content.classList.toggle('hidden', !isHidden);
            chevron?.classList.toggle('rotate-180', isHidden);
            button?.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
        }
    </script>
    @endpush
@endonce

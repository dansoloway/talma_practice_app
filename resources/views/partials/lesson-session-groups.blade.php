@php
    $lessonGroups = $lessonGroups ?? ['sessions' => [], 'review' => collect(), 'ungrouped' => collect()];
    $mode = $mode ?? 'student';
    $filteredSession = request('session_number');
    $hasContent = count($lessonGroups['sessions']) > 0
        || $lessonGroups['review']->isNotEmpty()
        || $lessonGroups['ungrouped']->isNotEmpty();
@endphp

@if(!$hasContent)
    <div class="{{ $mode === 'student' ? 'col-span-full' : '' }}">
        <div class="bg-white rounded-2xl border-2 border-gray-200 p-12 text-center">
            @if(request()->hasAny(['session_number', 'part_number', 'search']))
                <div class="text-6xl mb-4">🔍</div>
                <h3 class="text-2xl font-bold text-gray-800 mb-3">No lessons found</h3>
                <p class="text-gray-600 mb-6">No lessons match your current filters. Try adjusting your search criteria.</p>
                @if(isset($clearFiltersUrl))
                    <a href="{{ $clearFiltersUrl }}" class="inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200 shadow-sm hover:shadow-md">
                        Clear All Filters
                    </a>
                @endif
            @else
                <div class="text-6xl mb-4">📚</div>
                <h3 class="text-2xl font-bold text-gray-800 mb-3">No lessons available</h3>
                <p class="text-gray-600 mb-6">Please check back later for new lessons!</p>
            @endif
        </div>
    </div>
@else
    @foreach($lessonGroups['sessions'] as $index => $sessionGroup)
        @if($mode === 'student' && $sessionGroup['lessons']->count() === 1)
            <div class="mb-4">
                @include('partials.lesson-item-student', [
                    'lesson' => $sessionGroup['lessons']->first(),
                    'org' => $org ?? null,
                    'course' => $course ?? null,
                ])
            </div>
        @else
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
        @endif
    @endforeach

    @if($lessonGroups['ungrouped']->isNotEmpty())
        @if($mode === 'student' && $lessonGroups['ungrouped']->count() === 1)
            <div class="mb-4">
                @include('partials.lesson-item-student', [
                    'lesson' => $lessonGroups['ungrouped']->first(),
                    'org' => $org ?? null,
                    'course' => $course ?? null,
                ])
            </div>
        @else
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
    @endif

    @if($lessonGroups['review']->isNotEmpty())
        @if($mode === 'student' && $lessonGroups['review']->count() === 1)
            <div class="mb-4">
                @include('partials.lesson-item-student', [
                    'lesson' => $lessonGroups['review']->first(),
                    'org' => $org ?? null,
                    'course' => $course ?? null,
                ])
            </div>
        @else
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

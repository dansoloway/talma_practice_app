@php
    $mode = $mode ?? 'student';
    $accordionId = $accordionId ?? 'session-' . ($sessionNumber ?? 'group');
    $expanded = $expanded ?? false;
    $partCount = $lessons->count();
@endphp

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-4" data-session-accordion="{{ $accordionId }}">
    <button type="button"
            onclick="toggleSessionAccordion('{{ $accordionId }}')"
            class="w-full flex items-center justify-between gap-4 p-5 text-left hover:bg-gray-50 transition-colors duration-200"
            aria-expanded="{{ $expanded ? 'true' : 'false' }}"
            aria-controls="{{ $accordionId }}-content">
        <div class="flex-1 min-w-0">
            <h3 class="text-lg font-bold text-gray-800 truncate">{{ $label }}</h3>
            <p class="text-sm text-gray-500 mt-1">
                {{ $partCount }} {{ Str::plural('part', $partCount) }}
            </p>
        </div>
        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center">
            <i id="{{ $accordionId }}-chevron" class="fas fa-chevron-down text-blue-600 text-sm transition-transform duration-200 {{ $expanded ? 'rotate-180' : '' }}"></i>
        </div>
    </button>

    <div id="{{ $accordionId }}-content" class="{{ $expanded ? '' : 'hidden' }} border-t border-gray-100 p-5">
        @if($mode === 'admin')
            <div class="space-y-3">
                @foreach($lessons as $lesson)
                    @include('partials.lesson-item-admin', ['lesson' => $lesson])
                @endforeach
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @foreach($lessons as $lesson)
                    @include('partials.lesson-item-student', [
                        'lesson' => $lesson,
                        'org' => $org ?? null,
                        'course' => $course ?? null,
                    ])
                @endforeach
            </div>
        @endif
    </div>
</div>

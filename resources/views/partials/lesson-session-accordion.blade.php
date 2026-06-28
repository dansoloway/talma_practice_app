@php
    $mode = $mode ?? 'student';
    $accordionId = $accordionId ?? 'session-' . ($sessionNumber ?? 'group');
    $expanded = $expanded ?? false;
    $partCount = $lessons->count();
    $multiLesson = $multiLesson ?? ($partCount > 1);
    $isStudentMulti = $mode === 'student' && $multiLesson;
    $containerClass = $isStudentMulti
        ? 'bg-violet-50/90 border-violet-200'
        : 'bg-white border-gray-200';
    $headerHoverClass = $isStudentMulti ? 'hover:bg-violet-100/60' : 'hover:bg-gray-50';
    $chevronBgClass = $isStudentMulti ? 'bg-violet-100' : 'bg-blue-50';
    $chevronIconClass = $isStudentMulti ? 'text-violet-700' : 'text-blue-600';
@endphp

<div class="rounded-2xl border shadow-sm overflow-hidden mb-4 {{ $containerClass }}" data-session-accordion="{{ $accordionId }}">
    <button type="button"
            onclick="toggleSessionAccordion('{{ $accordionId }}')"
            class="w-full flex items-center justify-between gap-4 p-5 text-left {{ $headerHoverClass }} transition-colors duration-200"
            aria-expanded="{{ $expanded ? 'true' : 'false' }}"
            aria-controls="{{ $accordionId }}-content">
        <div class="flex-1 min-w-0">
            <h3 class="text-lg font-bold text-gray-800 truncate">{{ $label }}</h3>
            <p class="text-sm {{ $isStudentMulti ? 'text-violet-700/80' : 'text-gray-500' }} mt-1">
                {{ $partCount }} {{ Str::plural('lesson', $partCount) }}
                @if($isStudentMulti)
                    <span class="mx-1">·</span> Choose a part below
                @endif
            </p>
        </div>
        <div class="flex-shrink-0 w-8 h-8 rounded-full {{ $chevronBgClass }} flex items-center justify-center">
            <i id="{{ $accordionId }}-chevron" class="fas fa-chevron-down {{ $chevronIconClass }} text-sm transition-transform duration-200 {{ $expanded ? 'rotate-180' : '' }}"></i>
        </div>
    </button>

    <div id="{{ $accordionId }}-content" class="{{ $expanded ? '' : 'hidden' }} border-t {{ $isStudentMulti ? 'border-violet-100' : 'border-gray-100' }} p-5">
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

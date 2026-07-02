@if(!empty($guidedFlow))
    <div class="guided-flow-nav flex flex-col sm:flex-row justify-center gap-3 items-center">
        @if(!empty($guidedFlow['nextUrl']))
            <a href="{{ $guidedFlow['nextUrl'] }}"
               class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">
                {{ __('student-portal.games.next_label', ['label' => $guidedFlow['nextLabel']]) }}
            </a>
        @elseif(!empty($guidedFlow['isLastStep']))
            <a href="{{ $guidedFlow['courseUrl'] }}"
               class="px-6 py-3 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">
                {{ __('student-portal.games.lesson_complete') }}
            </a>
        @endif
        <a href="{{ $guidedFlow['lessonUrl'] }}"
           class="text-sm text-gray-500 hover:text-gray-700 underline underline-offset-2">
            {{ __('student-portal.games.back_to_lesson_overview') }}
        </a>
    </div>
@else
    @php
        $lessonBackUrl = isset($org) && $org
            ? route('org.student.lesson', [$org, $lesson->slug])
            : route('lessons.show', $lesson->slug);
    @endphp
    <a href="{{ $lessonBackUrl }}"
       class="px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-300 active:scale-95 transition-all duration-200 shadow-sm">
        {{ $fallbackLabel ?? __('student-portal.games.back_to_lesson') }}
    </a>
@endif

@if(!empty($guidedFlow))
    <div class="guided-flow-nav flex flex-col sm:flex-row justify-center gap-3 items-center">
        @if(!empty($guidedFlow['nextUrl']))
            <a href="{{ $guidedFlow['nextUrl'] }}"
               class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">
                Next: {{ $guidedFlow['nextLabel'] }}
            </a>
        @elseif(!empty($guidedFlow['isLastStep']))
            <a href="{{ $guidedFlow['courseUrl'] }}"
               class="px-6 py-3 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">
                Lesson complete
            </a>
        @endif
        <a href="{{ $guidedFlow['lessonUrl'] }}"
           class="text-sm text-gray-500 hover:text-gray-700 underline underline-offset-2">
            Back to lesson overview
        </a>
    </div>
@else
    <a href="{{ route('lessons.show', $lesson->slug) }}"
       class="px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-300 active:scale-95 transition-all duration-200 shadow-sm">
        {{ $fallbackLabel ?? 'Back to Lesson' }}
    </a>
@endif

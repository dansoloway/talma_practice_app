@php
    $lessonBackUrl = isset($org) && $org
        ? route('org.student.lesson', [$org, $lesson->slug])
        : route('lessons.show', $lesson->slug);
    $linkClass = $linkClass ?? 'inline-flex items-center text-blue-600 hover:text-blue-700 font-medium transition-colors duration-200 group';
@endphp
<a href="{{ $lessonBackUrl }}" class="{{ $linkClass }}">
    <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform duration-200" aria-hidden="true"></i>
    <span>{{ __('student-portal.games.back_to_lesson') }}</span>
</a>

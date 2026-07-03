@php
    use App\Support\SignupLocale;
    $lessonBackUrl = isset($org) && $org
        ? route('org.student.lesson', [$org, $lesson->slug])
        : route('lessons.show', $lesson->slug);
    $linkClass = $linkClass ?? 'inline-flex items-center text-blue-600 hover:text-blue-700 font-medium transition-colors duration-200 group';
    $isRtl = SignupLocale::isRtl();
@endphp
<a href="{{ $lessonBackUrl }}" class="{{ $linkClass }}">
    <i @class([
        'fas text-xs transition-transform duration-200 me-2',
        'fa-arrow-right group-hover:translate-x-1' => $isRtl,
        'fa-arrow-left group-hover:-translate-x-1' => ! $isRtl,
    ]) aria-hidden="true"></i>
    <span>{{ __('student-portal.games.back_to_lesson') }}</span>
</a>

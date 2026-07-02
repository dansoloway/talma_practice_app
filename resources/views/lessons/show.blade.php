@extends('layouts.app')

@php
    use App\Support\SignupLocale;
@endphp

@section('title', $lesson->title)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-8">
    <div class="container mx-auto px-4 max-w-5xl">
        <!-- Back Link + admin toolbar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
            <a href="{{ isset($org) && $org && $lesson->course ? route('org.student.course', [$org, $lesson->course]) : ($lesson->course ? route('student.course', $lesson->course->slug) : route('lessons.index')) }}"
               class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium transition-colors duration-200 group">
                <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform duration-200"></i>
                <span>{{ $lesson->course ? __('student-portal.lesson.back_to_course', ['course' => $lesson->course->title]) : __('student-portal.lesson.back_to_lessons') }}</span>
            </a>
            @include('partials.admin-edit-lesson', ['lesson' => $lesson])
        </div>

        <!-- Lesson header (no card wrapper) -->
        <header class="mb-8 text-left">
            @if($lesson->cover_image_path)
                <div class="mb-4">
                    <img src="{{ $lesson->cover_image_url }}"
                         alt="{{ $lesson->title }}"
                         class="max-w-full max-h-56 md:max-h-72 rounded-xl object-cover">
                </div>
            @endif

            @if($lesson->session_number && ! $lesson->is_review)
                <p class="text-[13px] text-[var(--color-secondary)] mb-1">{{ __('student-portal.lesson.day', ['number' => $lesson->session_number]) }}</p>
            @elseif($lesson->is_review)
                <p class="text-[13px] text-[var(--color-secondary)] mb-1">{{ __('student-portal.lesson.review') }}</p>
            @endif

            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-3 tracking-tight">
                {{ $lesson->studentCardTitle() }}
            </h1>

            @if($lesson->grade_level || $lesson->session_number || $lesson->course)
                <div class="flex flex-wrap gap-2">
                    @if($lesson->grade_level)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs text-[var(--color-secondary)] border border-gray-300">
                            {{ __('student-portal.lesson.grade', ['level' => $lesson->grade_level]) }}
                        </span>
                    @endif
                    @if($lesson->session_number)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs text-[var(--color-secondary)] border border-gray-300">
                            {{ __('student-portal.lesson.session', ['number' => $lesson->session_number]) }}
                        </span>
                    @endif
                    @if($lesson->course)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs text-[var(--color-secondary)] border border-gray-300">
                            {{ $lesson->course->title }}
                        </span>
                    @endif
                </div>
            @endif
        </header>

        @if(!empty($isLessonComplete))
            @php
                $courseBackUrl = $lesson->course
                    ? (isset($org) && $org
                        ? route('org.student.course', [$org, $lesson->course])
                        : route('student.course', $lesson->course->slug))
                    : null;
            @endphp
            <div class="mb-8 rounded-xl border border-green-200 bg-green-50/60 px-5 py-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <p class="text-green-800 font-semibold flex items-center gap-2">
                        <i class="fas fa-circle-check text-green-600" aria-hidden="true"></i>
                        {{ __('student-portal.lesson.finished') }}
                    </p>
                    @if($courseBackUrl)
                        <a href="{{ $courseBackUrl }}"
                           class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition-colors duration-200">
                            {{ __('student-portal.lesson.back_to_course_btn') }}
                            <i class="fas fa-arrow-right ml-2 text-xs" aria-hidden="true"></i>
                        </a>
                    @endif
                </div>
            </div>
        @endif

        @php
            $vocabGuidedUrl = isset($org) && $org
                ? route('org.student.guided.vocabulary', ['organization' => $org, 'lesson' => $lesson])
                : route('guided.vocabulary', ['lesson' => $lesson]);
        @endphp

        @include('partials.lesson-vocabulary-preview', ['lesson' => $lesson, 'vocabularyProgress' => $vocabularyProgress ?? null])

        @if(!empty($isGuided) && ($guidedStartUrl || !empty($isLessonComplete)))
            <section class="mb-8">
                @if(!empty($guidedProgress) && $guidedProgress['total'] > 0)
                    <div class="mb-3">
                        <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                            @if(!empty($isLessonComplete))
                                <span class="font-medium text-green-700">{{ __('student-portal.lesson.complete_percent') }}</span>
                            @elseif($guidedProgress['completed'] < $guidedProgress['total'])
                                <span>{{ __('student-portal.lesson.step_of', ['current' => $guidedProgress['completed'] + 1, 'total' => $guidedProgress['total']]) }}</span>
                            @endif
                            @if(empty($isLessonComplete))
                                <span>{{ $guidedProgress['percent'] }}%</span>
                            @endif
                        </div>
                        <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-300 {{ !empty($isLessonComplete) ? 'bg-green-500' : 'bg-blue-500' }}" style="width: {{ $guidedProgress['percent'] }}%"></div>
                        </div>
                    </div>
                @endif

                @if($guidedStartUrl)
                    <a href="{{ $guidedStartUrl }}"
                       class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">
                        @if(!empty($guidedProgress['completed']))
                            {{ __('student-portal.lesson.continue') }}: {{ $resumeStep?->label ?? $startStep?->label }}
                        @else
                            {{ __('student-portal.lesson.start') }}: {{ $startStep?->label ?? __('student-portal.lesson.lesson_fallback') }}
                        @endif
                        <i class="fas fa-arrow-right ml-2 text-sm" aria-hidden="true"></i>
                    </a>
                @elseif(!empty($isLessonComplete))
                    <button type="button" id="review-activities"
                            class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">
                        {{ __('student-portal.lesson.review_activities') }}
                        <i class="fas fa-list ml-2 text-sm" aria-hidden="true"></i>
                    </button>
                @endif
            </section>
        @elseif(!empty($lessonProgress) && $lessonProgress['total'] > 0 && empty($isGuided))
            <section class="mb-8">
                <div class="mb-3">
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                        @if(!empty($isLessonComplete))
                            <span class="font-medium text-green-700">{{ __('student-portal.lesson.complete_percent') }}</span>
                        @else
                            <span>{{ __('student-portal.lesson.activities_of', ['completed' => $lessonProgress['completed'], 'total' => $lessonProgress['total']]) }}</span>
                            <span>{{ $lessonProgress['percent'] }}%</span>
                        @endif
                    </div>
                    <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-300 {{ !empty($isLessonComplete) ? 'bg-green-500' : 'bg-blue-500' }}" style="width: {{ $lessonProgress['percent'] }}%"></div>
                    </div>
                </div>
            </section>
        @endif

        @php
            $allActivities = collect();

            if (! empty($isGuided) && $lesson->vocabulary->where('is_active', true)->isNotEmpty()) {
                $wordCount = $lesson->vocabulary->where('is_active', true)->count();
                $allActivities->push((object) [
                    'id' => 'vocabulary',
                    'type' => 'vocabulary',
                    'title' => SignupLocale::activityLabel('vocabulary'),
                    'subdetail' => SignupLocale::countLabel('word', $wordCount),
                    'sort_order' => -1,
                    'is_active' => true,
                    'model' => null,
                ]);
            }

            if($lesson->prompts->count() > 0) {
                $activePrompts = $lesson->prompts->where('is_active', true);
                $minSortOrder = $lesson->prompts->min('sort_order') ?? 999;

                $allActivities->push((object)[
                    'id' => 'prompts',
                    'type' => 'prompts',
                    'title' => SignupLocale::activityLabel('prompts'),
                    'subdetail' => SignupLocale::countLabel('question', $activePrompts->count()),
                    'sort_order' => $minSortOrder,
                    'is_active' => $activePrompts->count() > 0,
                    'model' => $lesson->prompts,
                ]);
            }

            foreach($lesson->matchingGames as $game) {
                $allActivities->push((object)[
                    'id' => $game->id,
                    'type' => 'matching',
                    'title' => $game->title,
                    'subdetail' => null,
                    'sort_order' => $game->sort_order ?? 999,
                    'is_active' => $game->is_active ?? true,
                    'model' => $game,
                ]);
            }

            foreach($lesson->flashcardGames as $game) {
                $allActivities->push((object)[
                    'id' => $game->id,
                    'type' => 'flashcard',
                    'title' => $game->title,
                    'subdetail' => null,
                    'sort_order' => $game->sort_order ?? 999,
                    'is_active' => $game->is_active ?? true,
                    'model' => $game,
                ]);
            }

            foreach($lesson->spellingGames as $game) {
                $allActivities->push((object)[
                    'id' => $game->id,
                    'type' => 'spelling',
                    'title' => $game->title,
                    'subdetail' => null,
                    'sort_order' => $game->sort_order ?? 999,
                    'is_active' => $game->is_active ?? true,
                    'model' => $game,
                ]);
            }

            foreach($lesson->clauseExercises as $exercise) {
                $allActivities->push((object)[
                    'id' => $exercise->id,
                    'type' => 'clause_exercise',
                    'title' => $exercise->title,
                    'subdetail' => null,
                    'sort_order' => $exercise->sort_order ?? 999,
                    'is_active' => $exercise->is_active ?? true,
                    'model' => $exercise,
                ]);
            }

            try {
                foreach($lesson->trueFalseGames as $game) {
                    if(!$game->is_active) continue;

                    $approvedCount = $game->questions()
                        ->where('is_approved', true)
                        ->where('is_active', true)
                        ->count();

                    if($approvedCount > 0) {
                        $allActivities->push((object)[
                            'id' => $game->id,
                            'type' => 'true_false',
                            'title' => $game->title,
                            'subdetail' => SignupLocale::countLabel('question', $approvedCount),
                            'sort_order' => $game->sort_order ?? 999,
                            'is_active' => $game->is_active ?? true,
                            'model' => $game,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                // Table doesn't exist yet - skip
            }

            $allActivities = $allActivities->where('is_active', true)->sortBy('sort_order');

            $activityIcons = [
                'vocabulary' => 'fa-volume-up',
                'prompts' => 'fa-pencil',
                'matching' => 'fa-link',
                'flashcard' => 'fa-clone',
                'spelling' => 'fa-font',
                'clause_exercise' => 'fa-file-lines',
                'true_false' => 'fa-circle-check',
            ];
        @endphp

        @if($allActivities->count() > 0)
            @if(!empty($isGuided) && empty($isLessonComplete))
                <p class="mb-3">
                    <button type="button" id="show-all-activities"
                            class="text-sm text-gray-500 hover:text-gray-700 underline underline-offset-2">
                        {{ __('student-portal.lesson.all_activities') }}
                    </button>
                </p>
            @endif

            <section class="mb-8 {{ !empty($isGuided) && empty($isLessonComplete) ? 'hidden' : '' }}" id="activities-section">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3">
                    {{ __('student-portal.lesson.activities_heading', ['count' => $allActivities->count()]) }}
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach($allActivities as $activity)
                        @php
                            $displayTitle = in_array($activity->type, ['vocabulary', 'prompts'], true)
                                ? SignupLocale::activityLabel($activity->type)
                                : SignupLocale::normalizeActivityTitle($activity->type, $activity->title, $lesson->title);
                            $subdetail = $activity->subdetail ?? null;
                            $activityKey = match ($activity->type) {
                                'prompts' => 'prompts:0',
                                'vocabulary' => 'vocabulary:0',
                                default => $activity->type . ':' . $activity->id,
                            };
                            $isActivityDone = in_array($activityKey, $completedStepKeys ?? [], true);
                        @endphp
                        <button type="button"
                                class="group w-full text-left flex items-center gap-3 rounded-lg border py-[10px] px-3 transition-colors duration-200 {{ $isActivityDone ? 'border-green-200 bg-green-50/40 hover:border-green-300' : 'border-gray-200 bg-white hover:border-blue-300 hover:bg-blue-50/40' }}"
                                onclick="startActivity('{{ $activity->type }}', '{{ $activity->id }}')">
                            <span class="lesson-activity-icon flex-shrink-0 flex items-center justify-center transition-colors duration-200 {{ $isActivityDone ? 'text-green-600' : 'text-gray-600 group-hover:text-blue-600' }}">
                                <i class="fas {{ $activityIcons[$activity->type] ?? 'fa-play' }} text-sm" aria-hidden="true"></i>
                            </span>
                            <span class="flex-1 min-w-0">
                                <span class="block text-sm font-semibold truncate {{ $isActivityDone ? 'text-green-800' : 'text-gray-800 group-hover:text-blue-700' }}">
                                    {{ $displayTitle }}
                                </span>
                                @if($subdetail || $isActivityDone)
                                    <span class="block text-xs mt-0.5 {{ $isActivityDone ? 'text-green-600' : 'text-[var(--color-secondary)]' }}">
                                        @if($isActivityDone)
                                            {{ __('student-portal.lesson.done') }}
                                        @elseif($subdetail)
                                            {{ $subdetail }}
                                        @endif
                                    </span>
                                @endif
                            </span>
                            @if($isActivityDone)
                                <i class="fas fa-circle-check text-sm text-green-500 flex-shrink-0" aria-hidden="true"></i>
                            @else
                                <i class="fas fa-chevron-right text-[10px] text-gray-300 group-hover:text-blue-500 flex-shrink-0" aria-hidden="true"></i>
                            @endif
                        </button>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>

<script>
const lessonData = @json($lesson);

const activityRoutes = {
    vocabulary: @json($vocabGuidedUrl),
    prompts: @json(route('prompts.play', ['lesson' => $lesson->id])),
    matching: (id) => @json(url('/lessons/' . $lesson->id . '/matching-games')) + `/${id}/play`,
    flashcard: (id) => @json(url('/lessons/' . $lesson->id . '/flashcard-games')) + `/${id}/play`,
    spelling: (id) => @json(url('/lessons/' . $lesson->id . '/spelling-games')) + `/${id}/play`,
    clause_exercise: (id) => @json(url('/lessons/' . $lesson->id . '/clause-exercises')) + `/${id}/play`,
    true_false: (id) => @json(url('/lessons/' . $lesson->id . '/true-false-games')) + `/${id}/play`,
};

function startActivity(type, id) {
    switch(type) {
        case 'vocabulary':
            window.location.href = activityRoutes.vocabulary;
            break;
        case 'prompts':
            window.location.href = activityRoutes.prompts;
            break;
        case 'matching':
            window.location.href = activityRoutes.matching(id);
            break;
        case 'flashcard':
            window.location.href = activityRoutes.flashcard(id);
            break;
        case 'spelling':
            window.location.href = activityRoutes.spelling(id);
            break;
        case 'clause_exercise':
            window.location.href = activityRoutes.clause_exercise(id);
            break;
        case 'true_false':
            window.location.href = activityRoutes.true_false(id);
            break;
    }
}

document.getElementById('show-all-activities')?.addEventListener('click', () => {
    document.getElementById('activities-section')?.classList.remove('hidden');
    document.getElementById('show-all-activities')?.classList.add('hidden');
});

document.getElementById('review-activities')?.addEventListener('click', () => {
    document.getElementById('activities-section')?.classList.remove('hidden');
    document.getElementById('review-activities')?.classList.add('hidden');
});

document.querySelectorAll('.lesson-vocab-lang-btn').forEach((button) => {
    button.addEventListener('click', () => {
        const lang = button.dataset.lang;
        const isActive = button.classList.toggle('is-active');

        if (lang === 'hebrew') {
            button.classList.toggle('border-blue-400', isActive);
            button.classList.toggle('bg-blue-50', isActive);
        } else if (lang === 'arabic') {
            button.classList.toggle('border-green-400', isActive);
            button.classList.toggle('bg-green-50', isActive);
        }

        document.querySelectorAll(`.lesson-vocab-translation-${lang}`).forEach((translation) => {
            translation.classList.toggle('hidden', ! isActive);
        });
    });
});
</script>
@endsection

@push('styles')
<style>
    .lesson-vocab-card {
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .lesson-vocab-card:hover {
        border-color: rgb(147 197 253);
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.08);
    }

    .lesson-vocab-lang-btn.is-active {
        box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.15);
    }

    .lesson-activity-icon {
        width: 36px;
        height: 36px;
        background: var(--color-bg-secondary);
        border-radius: var(--radius-md);
    }
</style>
@endpush

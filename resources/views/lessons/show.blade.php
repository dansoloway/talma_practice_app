@extends('layouts.app')

@section('title', $lesson->title)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-8">
    <div class="container mx-auto px-4 max-w-5xl">
        <!-- Back Link + admin toolbar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
            <a href="{{ isset($org) && $org && $lesson->course ? route('org.student.course', [$org, $lesson->course]) : ($lesson->course ? route('student.course', $lesson->course->slug) : route('lessons.index')) }}"
               class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium transition-colors duration-200 group">
                <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform duration-200"></i>
                <span>{{ $lesson->course ? 'Back to ' . $lesson->course->title : 'Back to Lessons' }}</span>
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
                <p class="text-[13px] text-[var(--color-secondary)] mb-1">Day {{ $lesson->session_number }}</p>
            @elseif($lesson->is_review)
                <p class="text-[13px] text-[var(--color-secondary)] mb-1">Review</p>
            @endif

            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-3 tracking-tight">
                {{ $lesson->studentCardTitle() }}
            </h1>

            @if($lesson->grade_level || $lesson->session_number || $lesson->course)
                <div class="flex flex-wrap gap-2">
                    @if($lesson->grade_level)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs text-[var(--color-secondary)] border border-gray-300">
                            Grade {{ $lesson->grade_level }}
                        </span>
                    @endif
                    @if($lesson->session_number)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs text-[var(--color-secondary)] border border-gray-300">
                            Session {{ $lesson->session_number }}
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

        @if($lesson->vocabulary && $lesson->vocabulary->count() > 0)
            <section class="mb-8">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3">
                    Vocabulary · {{ $lesson->vocabulary->count() }} {{ Str::plural('word', $lesson->vocabulary->count()) }}
                </p>
                <div class="flex flex-wrap gap-2">
                    @foreach($lesson->vocabulary as $vocab)
                        <span class="lesson-vocab-chip inline-flex items-center text-[13px] text-gray-800">
                            {{ $vocab->english_word }}
                        </span>
                    @endforeach
                </div>
            </section>
        @endif

        @php
            $allActivities = collect();

            if($lesson->prompts->count() > 0) {
                $activePrompts = $lesson->prompts->where('is_active', true);
                $minSortOrder = $lesson->prompts->min('sort_order') ?? 999;

                $allActivities->push((object)[
                    'id' => 'prompts',
                    'type' => 'prompts',
                    'title' => 'Sentence Completion',
                    'subdetail' => $activePrompts->count() . ' ' . Str::plural('question', $activePrompts->count()),
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
                            'subdetail' => $approvedCount . ' ' . Str::plural('question', $approvedCount),
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
                'prompts' => 'fa-pencil',
                'matching' => 'fa-link',
                'flashcard' => 'fa-clone',
                'spelling' => 'fa-font',
                'clause_exercise' => 'fa-file-lines',
                'true_false' => 'fa-circle-check',
            ];
        @endphp

        @if($allActivities->count() > 0)
            <section class="mb-8">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3">
                    Activities · {{ $allActivities->count() }}
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach($allActivities as $activity)
                        @php
                            $displayTitle = $activity->title;
                            $subdetail = $activity->subdetail ?? null;
                            $lessonTitleEscaped = preg_quote(trim($lesson->title), '/');

                            if ($activity->type === 'matching') {
                                $pattern = '/^' . $lessonTitleEscaped . '\s+Matching\s+Game\s+\d+$/i';
                                if (preg_match($pattern, trim($activity->title))) {
                                    $displayTitle = 'Matching Game';
                                }
                            } elseif ($activity->type === 'flashcard') {
                                $pattern = '/^' . $lessonTitleEscaped . '\s+Flashcards\s+\d+$/i';
                                if (preg_match($pattern, trim($activity->title))) {
                                    $displayTitle = 'Flashcards';
                                }
                            } elseif ($activity->type === 'spelling') {
                                $pattern = '/^' . $lessonTitleEscaped . '\s+Spelling\s+Practice\s+\d+$/i';
                                if (preg_match($pattern, trim($activity->title))) {
                                    $displayTitle = 'Spelling Practice';
                                }
                            } elseif ($activity->type === 'prompts') {
                                $displayTitle = 'Sentence Completion';
                            }
                        @endphp
                        <button type="button"
                                class="group w-full text-left flex items-center gap-3 rounded-lg border border-gray-200 bg-white py-[10px] px-3 hover:border-blue-300 hover:bg-blue-50/40 transition-colors duration-200"
                                onclick="startActivity('{{ $activity->type }}', '{{ $activity->id }}')">
                            <span class="lesson-activity-icon flex-shrink-0 flex items-center justify-center text-gray-600 group-hover:text-blue-600 transition-colors duration-200">
                                <i class="fas {{ $activityIcons[$activity->type] ?? 'fa-play' }} text-sm" aria-hidden="true"></i>
                            </span>
                            <span class="flex-1 min-w-0">
                                <span class="block text-sm font-semibold text-gray-800 group-hover:text-blue-700 truncate">
                                    {{ $displayTitle }}
                                </span>
                                @if($subdetail)
                                    <span class="block text-xs text-[var(--color-secondary)] mt-0.5">
                                        {{ $subdetail }}
                                    </span>
                                @endif
                            </span>
                            <i class="fas fa-chevron-right text-[10px] text-gray-300 group-hover:text-blue-500 flex-shrink-0" aria-hidden="true"></i>
                        </button>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>

<script>
const lessonData = @json($lesson);

function startActivity(type, id) {
    switch(type) {
        case 'prompts':
            window.location.href = `/lessons/{{ $lesson->id }}/prompts/play`;
            break;
        case 'matching':
            window.location.href = `/lessons/{{ $lesson->id }}/matching-games/${id}/play`;
            break;
        case 'flashcard':
            window.location.href = `/lessons/{{ $lesson->id }}/flashcard-games/${id}/play`;
            break;
        case 'spelling':
            window.location.href = `/lessons/{{ $lesson->id }}/spelling-games/${id}/play`;
            break;
        case 'clause_exercise':
            window.location.href = `/lessons/{{ $lesson->id }}/clause-exercises/${id}/play`;
            break;
        case 'true_false':
            window.location.href = `/lessons/{{ $lesson->id }}/true-false-games/${id}/play`;
            break;
    }
}
</script>
@endsection

@push('styles')
<style>
    .lesson-vocab-chip {
        padding: 4px 10px;
        background: var(--color-bg-secondary);
        border-radius: var(--radius-md);
    }

    .lesson-activity-icon {
        width: 36px;
        height: 36px;
        background: var(--color-bg-secondary);
        border-radius: var(--radius-md);
    }
</style>
@endpush

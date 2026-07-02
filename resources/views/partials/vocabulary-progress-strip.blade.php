@php
    $progress = $vocabularyProgress ?? null;
    $words = $words ?? collect();
    $currentWordId = $currentWordId ?? null;
@endphp

@if($progress && ($progress['total'] ?? 0) > 0)
    <div class="mb-4">
        <div class="mb-3">
            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-2">
                <p class="text-sm font-medium text-gray-800" id="vocab-progress-summary">{{ __('student-portal.vocabulary_preview.progress_heading') }}</p>
                <p class="text-sm text-gray-600">
                    {{ __('student-portal.vocabulary_preview.progress_count', [
                        'learned' => $progress['learned'],
                        'total' => $progress['total'],
                    ]) }}
                </p>
            </div>

            <div class="vocab-progress-bar flex gap-1 h-3 rounded-full bg-gray-100 p-0.5"
                 role="progressbar"
                 aria-valuemin="0"
                 aria-valuemax="{{ $progress['total'] }}"
                 aria-valuenow="{{ $progress['learned'] }}"
                 aria-label="{{ __('student-portal.vocabulary_preview.progress_aria', [
                     'learned' => $progress['learned'],
                     'total' => $progress['total'],
                 ]) }}">
                @foreach($words as $word)
                    @php
                        $status = $progress['statuses'][$word->id] ?? 'not_started';
                        $isCurrent = $currentWordId && $word->id === $currentWordId;
                        $segmentClass = match (true) {
                            $status === 'learned' => 'bg-green-500',
                            $status === 'needs_practice' => 'bg-amber-400',
                            $status === 'skipped' => 'bg-gray-300',
                            $isCurrent => 'bg-blue-400',
                            default => 'bg-gray-200',
                        };
                    @endphp
                    <div @class([
                        'vocab-progress-segment flex-1 min-w-[0.35rem] rounded-full transition-colors duration-300',
                        $segmentClass,
                        'ring-2 ring-blue-500 ring-offset-1' => $isCurrent && $status !== 'learned',
                    ])
                         data-vocab-progress-segment="{{ $word->id }}"
                         title="{{ $word->english_word }}"></div>
                @endforeach
            </div>

            @if(($progress['visited'] ?? 0) > 0 && ($progress['learned'] ?? 0) < ($progress['total'] ?? 0))
                <p class="text-xs text-gray-500 mt-1.5 vocab-visited-summary">{{ __('student-portal.vocabulary_preview.words_practiced', [
                    'visited' => $progress['visited'],
                    'total' => $progress['total'],
                ]) }}</p>
            @else
                <p class="text-xs text-gray-500 mt-1.5 vocab-visited-summary hidden"></p>
            @endif
        </div>

        @if($words->isNotEmpty())
            <div class="flex flex-wrap gap-1.5 student-learning-ltr" dir="ltr" lang="en">
                @foreach($words as $word)
                    @php
                        $status = $progress['statuses'][$word->id] ?? 'not_started';
                        $isCurrent = $currentWordId && $word->id === $currentWordId;
                    @endphp
                    <span @class([
                        'inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium border',
                        'border-green-300 bg-green-50 text-green-800' => $status === 'learned',
                        'border-amber-200 bg-amber-50 text-amber-800' => $status === 'needs_practice',
                        'border-gray-200 bg-gray-50 text-gray-600' => $status === 'skipped',
                        'border-blue-300 bg-blue-50 text-blue-800 ring-1 ring-blue-200' => $isCurrent,
                        'border-gray-200 bg-white text-gray-500' => $status === 'not_started' && ! $isCurrent,
                    ]) data-vocab-word-id="{{ $word->id }}" data-word-label="{{ $word->english_word }}">
                        @if($status === 'learned')
                            <i class="fas fa-check text-[10px]" aria-hidden="true"></i>
                        @elseif($status === 'needs_practice')
                            <i class="fas fa-redo text-[10px]" aria-hidden="true"></i>
                        @endif
                        {{ $word->english_word }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>
@endif

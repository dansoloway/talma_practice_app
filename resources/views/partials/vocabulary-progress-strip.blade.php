@php
    $progress = $vocabularyProgress ?? null;
    $words = $words ?? collect();
    $currentWordId = $currentWordId ?? null;
@endphp

@if($progress && ($progress['total'] ?? 0) > 0)
    <div class="mb-4">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
            <p class="text-sm font-medium text-gray-700" id="vocab-progress-summary">
                Your words:
                <span class="text-green-700" id="vocab-learned-count">{{ $progress['learned'] }}</span>
                <span class="text-gray-500">of {{ $progress['total'] }} mastered</span>
            </p>
            @if(($progress['visited'] ?? 0) > 0 && ($progress['learned'] ?? 0) < ($progress['total'] ?? 0))
                <p class="text-xs text-gray-500">{{ $progress['visited'] }} practiced so far</p>
            @endif
        </div>

        @if($words->isNotEmpty())
            <div class="flex flex-wrap gap-1.5">
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

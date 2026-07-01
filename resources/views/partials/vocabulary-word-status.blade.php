@php
    $status = $status ?? 'not_started';
@endphp

@if($status === 'learned')
    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold text-green-700 bg-green-100 border border-green-200" title="You got this word">
        <i class="fas fa-check text-[9px]" aria-hidden="true"></i>
        Got it
    </span>
@elseif($status === 'needs_practice')
    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold text-amber-700 bg-amber-50 border border-amber-200" title="Keep practicing this word">
        <i class="fas fa-redo text-[9px]" aria-hidden="true"></i>
        Try again
    </span>
@elseif($status === 'skipped')
    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold text-gray-600 bg-gray-100 border border-gray-200" title="You skipped this word">
        Skipped
    </span>
@endif

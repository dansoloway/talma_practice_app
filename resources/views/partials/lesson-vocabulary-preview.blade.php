@php
    $vocabItems = $lesson->vocabulary->where('is_active', true);
    $hasHebrew = $vocabItems->contains(fn ($v) => ! empty($v->hebrew_translation));
    $hasArabic = $vocabItems->contains(fn ($v) => ! empty($v->arabic_translation));
@endphp

@if($vocabItems->isNotEmpty())
    <section class="mb-8" id="lesson-vocabulary-preview">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Vocabulary · {{ $vocabItems->count() }} {{ Str::plural('word', $vocabItems->count()) }}
            </p>
            @if($hasHebrew || $hasArabic)
                <div class="flex flex-wrap gap-2">
                    @if($hasHebrew)
                        <button type="button"
                                class="lesson-vocab-lang-btn px-3 py-1 rounded-lg border border-gray-200 bg-white text-xs font-semibold text-gray-700 hover:border-blue-300 hover:bg-blue-50 transition-colors"
                                data-lang="hebrew">
                            עברית
                        </button>
                    @endif
                    @if($hasArabic)
                        <button type="button"
                                class="lesson-vocab-lang-btn px-3 py-1 rounded-lg border border-gray-200 bg-white text-xs font-semibold text-gray-700 hover:border-green-300 hover:bg-green-50 transition-colors"
                                data-lang="arabic">
                            عربي
                        </button>
                    @endif
                </div>
            @endif
        </div>

        <p class="text-sm text-gray-600 mb-4">Listen to each word and review the pictures before you start the activities.</p>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            @foreach($vocabItems as $vocab)
                <div class="lesson-vocab-card rounded-xl border border-gray-200 bg-white p-3 text-center shadow-sm">
                    @if($vocab->image_url)
                        <img src="{{ $vocab->image_url }}"
                             alt="{{ $vocab->english_word }}"
                             class="w-full h-24 sm:h-28 object-cover rounded-lg mb-2">
                    @else
                        <div class="w-full h-24 sm:h-28 rounded-lg mb-2 bg-gray-100 flex items-center justify-center text-gray-400">
                            <i class="fas fa-image text-lg" aria-hidden="true"></i>
                        </div>
                    @endif

                    <div class="flex flex-col items-center gap-1.5">
                        @if($vocab->word_audio_url)
                            <button type="button"
                                    class="lesson-vocab-audio-btn w-9 h-9 rounded-full bg-blue-600 text-white hover:bg-blue-700 active:scale-95 transition-all flex items-center justify-center shadow-sm"
                                    data-audio-url="{{ $vocab->word_audio_url }}"
                                    title="Listen to {{ $vocab->english_word }}">
                                <i class="fas fa-volume-up text-xs" aria-hidden="true"></i>
                            </button>
                        @endif

                        <div class="text-sm font-semibold text-gray-900 leading-tight">{{ $vocab->english_word }}</div>

                        @if($vocab->hebrew_translation)
                            <div class="lesson-vocab-translation lesson-vocab-translation-hebrew hidden text-xs font-medium px-2 py-0.5 rounded-md bg-blue-50 text-blue-800 border border-blue-100">
                                {{ $vocab->hebrew_translation }}
                            </div>
                        @endif

                        @if($vocab->arabic_translation)
                            <div class="lesson-vocab-translation lesson-vocab-translation-arabic hidden text-xs font-medium px-2 py-0.5 rounded-md bg-green-50 text-green-800 border border-green-100">
                                {{ $vocab->arabic_translation }}
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif

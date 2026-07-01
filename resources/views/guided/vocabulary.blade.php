@extends('layouts.app')

@section('title', 'Vocabulary - ' . $lesson->title)

@section('content')
@php
    $micPracticeEnabled = ($speechFeedbackEnabled ?? false) || ($voiceUploadEnabled ?? false);
@endphp
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-4 md:py-6">
    <div class="container mx-auto px-4 max-w-5xl">
        <div class="mb-4">
            <a href="{{ isset($org) && $org ? route('org.student.lesson', [$org, $lesson->slug]) : route('lessons.show', $lesson->slug) }}"
               class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium transition-colors duration-200 group">
                <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform duration-200"></i>
                <span>Back to lesson</span>
            </a>
        </div>

        @if(!empty($guidedFlow))
            <p class="text-xs text-gray-500 mb-3 lg:hidden">Step {{ $guidedFlow['currentIndex'] }} of {{ $guidedFlow['totalSteps'] }}</p>
        @endif

        <div class="mb-3 lg:hidden">
            @include('partials.vocabulary-progress-strip', [
                'vocabularyProgress' => $vocabularyProgress ?? null,
                'words' => $words ?? collect(),
                'currentWordId' => $currentWord->id ?? null,
            ])
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 md:p-5 lg:p-6" id="vocab-step">
            <div class="flex flex-col lg:grid lg:grid-cols-2 lg:gap-8 lg:items-start">
                {{-- Left column: word + progress --}}
                <div class="text-center lg:text-left">
                    @if(!empty($guidedFlow))
                        <p class="hidden lg:block text-xs text-gray-500 mb-2">Step {{ $guidedFlow['currentIndex'] }} of {{ $guidedFlow['totalSteps'] }}</p>
                    @endif

                    <div class="hidden lg:block mb-3">
                        @include('partials.vocabulary-progress-strip', [
                            'vocabularyProgress' => $vocabularyProgress ?? null,
                            'words' => $words ?? collect(),
                            'currentWordId' => $currentWord->id ?? null,
                        ])
                    </div>

                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">
                        Vocabulary · Word {{ $wordIndex + 1 }} of {{ $wordsCount }}
                    </p>

                    @if($currentWord->image_url)
                        <img src="{{ $currentWord->image_url }}"
                             alt="{{ $currentWord->english_word }}"
                             class="mx-auto lg:mx-0 max-h-40 lg:max-h-44 rounded-xl object-cover mb-3">
                    @endif

                    <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $currentWord->english_word }}</h1>

                    @if($currentWord->hebrew_translation || $currentWord->arabic_translation)
                        <div class="flex flex-wrap justify-center lg:justify-start gap-2 mb-3">
                            @if($currentWord->hebrew_translation)
                                <span class="text-sm font-medium px-3 py-1 rounded-lg bg-blue-50 text-blue-800 border border-blue-100">
                                    {{ $currentWord->hebrew_translation }}
                                </span>
                            @endif
                            @if($currentWord->arabic_translation)
                                <span class="text-sm font-medium px-3 py-1 rounded-lg bg-green-50 text-green-800 border border-green-100">
                                    {{ $currentWord->arabic_translation }}
                                </span>
                            @endif
                        </div>
                    @endif

                    @if($currentWord->word_audio_url)
                        <button type="button" id="play-model-btn"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 bg-gray-50 text-gray-700 hover:bg-blue-50 hover:border-blue-200 transition-colors">
                            <i class="fas fa-volume-up" aria-hidden="true"></i>
                            Listen
                        </button>
                    @endif
                </div>

                {{-- Right column: practice --}}
                <div class="mt-4 pt-4 border-t border-gray-100 lg:mt-0 lg:pt-0 lg:border-t-0">
                    @if($micPracticeEnabled)
                    <div class="text-center lg:text-left" id="speech-feedback-section">
                        <p class="text-sm text-gray-600 mb-3">Say the word aloud and get instant feedback</p>
                        <div class="flex items-center justify-center lg:justify-start gap-2">
                            @if($speechFeedbackEnabled ?? false)
                            <button type="button" id="speech-check-btn"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-purple-600 text-white font-semibold rounded-xl hover:bg-purple-700 transition-colors disabled:opacity-50"
                                    aria-label="Tap to say the word">
                                <i class="fas fa-microphone" aria-hidden="true"></i>
                                Tap to say the word
                            </button>
                            @else
                            <button type="button" id="record-btn"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-purple-600 text-white font-semibold rounded-xl hover:bg-purple-700 transition-colors disabled:opacity-50"
                                    aria-label="Tap to say the word">
                                <i class="fas fa-microphone" aria-hidden="true"></i>
                                Tap to say the word
                            </button>
                            @endif
                            <button type="button" id="play-recording-btn" disabled
                                    class="inline-flex items-center justify-center w-11 h-11 rounded-xl border border-gray-200 bg-gray-50 text-gray-700 hover:bg-gray-100 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                    aria-label="Play back your recording">
                                <i class="fas fa-play text-sm" aria-hidden="true"></i>
                            </button>
                        </div>
                        <p id="speech-check-status" class="text-sm text-gray-500 min-h-[1.25rem] mt-2" role="status"></p>
                        <div id="speech-check-feedback" class="hidden" aria-hidden="true"></div>
                        <p id="speech-unsupported-note" class="hidden text-xs text-gray-500 mt-1">
                            Pronunciation check works best in Chrome or Edge on desktop.
                        </p>
                    </div>
                    @elseif(!empty($voiceRecordingOffered))
                    <div class="text-center lg:text-left">
                        <p class="text-sm text-gray-600 mb-2">Say the word aloud to practice</p>
                        @if(($voiceProfileBlockedReason ?? null) === 'select_child' && isset($org) && $org)
                            <p class="text-sm text-amber-700">
                                Choose who is practicing to save recordings.
                                <a href="{{ route('org.student.select-child', $org) }}" class="font-semibold underline underline-offset-2">Select a child</a>
                            </p>
                        @elseif(($voiceProfileBlockedReason ?? null) === 'profile_incomplete' && isset($org) && $org)
                            <p class="text-sm text-amber-700">
                                Complete your learner profile to save recordings.
                                <a href="{{ route('org.student.complete-voice-profile', $org) }}" class="font-semibold underline underline-offset-2">Complete profile</a>
                            </p>
                        @else
                            <p class="text-sm text-gray-500">Recording will be available once your learner profile is complete.</p>
                        @endif
                    </div>
                    @else
                    <p class="text-sm text-gray-500 text-center lg:text-left">Tap listen, then continue when you are ready.</p>
                    @endif
                </div>
            </div>

            <div class="mt-5 pt-4 flex flex-col sm:flex-row justify-center lg:justify-start gap-3 border-t border-gray-100">
                <button type="button" id="next-word-btn"
                        class="px-6 py-3 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 transition-colors">
                    @if($isLastWord)
                        Continue
                    @else
                        Next word
                    @endif
                </button>
                <button type="button" id="skip-word-btn"
                        class="text-sm text-gray-500 hover:text-gray-700 underline underline-offset-2 sm:self-center">
                    Skip word
                </button>
            </div>
        </div>

        <div id="vocab-complete" class="hidden bg-white rounded-2xl shadow-sm border border-gray-200 p-8 text-center">
            <h2 class="text-2xl font-bold text-gray-900 mb-3">Vocabulary complete!</h2>
            @if(!empty($vocabularyProgress))
                <p class="text-gray-700 mb-2">
                    You mastered
                    <span class="font-bold text-green-700" id="vocab-complete-learned-count">{{ $vocabularyProgress['learned'] }}</span>
                    of {{ $vocabularyProgress['total'] }} words.
                </p>
            @else
                <p class="text-gray-600 mb-6">Great job practicing all the words.</p>
            @endif

            @include('partials.vocabulary-progress-strip', [
                'vocabularyProgress' => $vocabularyProgress ?? null,
                'words' => $words ?? collect(),
            ])

            <div class="mt-6">
            @include('partials.guided-flow-nav', ['guidedFlow' => $guidedFlow ?? null, 'lesson' => $lesson, 'fallbackLabel' => 'Back to Lesson'])
            </div>
        </div>
    </div>
</div>

<audio id="model-audio" preload="auto"></audio>
<audio id="playback-audio"></audio>

@php
    $voiceUploadConfigData = [
        'enabled' => $voiceUploadEnabled ?? false,
        'endpoint' => route('voice-samples.store'),
        'organizationId' => $voiceOrganization?->id,
        'lessonId' => $lesson->id,
        'vocabularyId' => $currentWord->id,
        'targetText' => $currentWord->english_word,
        'maxSeconds' => (int) config('app.recording_max_seconds', 20),
    ];
    $speechFeedbackConfigData = [
        'enabled' => (bool) ($speechFeedbackEnabled ?? false),
        'targetText' => $currentWord->english_word,
        'maxSeconds' => (int) config('app.recording_max_seconds', 20),
        'lang' => 'en-US',
    ];
    $lessonShowUrl = isset($org) && $org
        ? route('org.student.lesson', [$org, $lesson->slug])
        : route('lessons.show', $lesson->slug);
@endphp
@endsection

@push('scripts')
<script>
const voiceUploadConfig = @json($voiceUploadConfigData);
const speechFeedbackConfig = @json($speechFeedbackConfigData);
const activityEventEndpoint = @json(route('activity-events.store'));
const continueUrl = @json($continueUrl);
const isLastWord = @json($isLastWord);
const lessonShowUrl = @json($lessonShowUrl);

window.talmaVocabPronunciation = {
    lastAttempt: null,
    isActive: false,
    recordingUploaded: false,
};

let pendingPronunciationResult = null;

function pickPronunciationRecorderMimeType() {
    if (typeof MediaRecorder === 'undefined') {
        return '';
    }

    const candidates = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4'];
    return candidates.find((type) => MediaRecorder.isTypeSupported(type)) || '';
}

function finalizePronunciationAttempt() {
    if (!pendingPronunciationResult) {
        return;
    }

    window.talmaVocabPronunciation.lastAttempt = {
        pass: pendingPronunciationResult.pass === true,
        heard: pendingPronunciationResult.heard || '',
        ratio: typeof pendingPronunciationResult.ratio === 'number' ? pendingPronunciationResult.ratio : null,
        audioBlob: pendingPronunciationResult.audioBlob || null,
        durationMs: pendingPronunciationResult.durationMs || null,
    };
    pendingPronunciationResult = null;
}

let mediaRecorder = null;
let mediaRecorderMimeType = 'audio/webm';
let recordedChunks = [];
let recordedAudioBlob = null;
let playbackObjectUrl = null;
let isRecording = false;
let recordingStartedAt = null;
let recordingMaxTimeout = null;
let hasRecordedThisWord = false;

const recordBtn = document.getElementById('record-btn');
const playRecordingBtn = document.getElementById('play-recording-btn');
const pronunciationStatus = document.getElementById('speech-check-status');
const nextWordBtn = document.getElementById('next-word-btn');
const playbackAudio = document.getElementById('playback-audio');
const modelAudio = document.getElementById('model-audio');

const MIC_IDLE_LABEL = '<i class="fas fa-microphone" aria-hidden="true"></i> Tap to say the word';

function setPronunciationStatus(message, tone = 'neutral') {
    if (!pronunciationStatus) {
        return;
    }

    pronunciationStatus.textContent = message;
    pronunciationStatus.classList.remove('text-gray-500', 'text-green-600', 'text-amber-600', 'text-red-600');
    const toneClasses = {
        neutral: 'text-gray-500',
        success: 'text-green-600',
        warning: 'text-amber-600',
        error: 'text-red-600',
    };
    pronunciationStatus.classList.add(toneClasses[tone] || toneClasses.neutral);
}

function setRecordingStatus(message, tone = 'neutral') {
    setPronunciationStatus(message, tone);
}

document.getElementById('play-model-btn')?.addEventListener('click', () => {
    @if($currentWord->word_audio_url)
    modelAudio.src = @json($currentWord->word_audio_url);
    modelAudio.play();
    @endif
});

async function initializeRecording() {
    if (!navigator.mediaDevices?.getUserMedia) {
        setRecordingStatus('Recording is not supported in this browser. Try Chrome or Edge.', 'error');
        return false;
    }

    if (typeof TalmaSpeech !== 'undefined' && typeof TalmaSpeech.releaseMicrophoneAccess === 'function') {
        TalmaSpeech.releaseMicrophoneAccess();
    }

    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        const mimeType = pickPronunciationRecorderMimeType();
        mediaRecorderMimeType = mimeType || 'audio/webm';
        mediaRecorder = mimeType
            ? new MediaRecorder(stream, { mimeType })
            : new MediaRecorder(stream);

        mediaRecorder.ondataavailable = (event) => {
            if (event.data.size > 0) recordedChunks.push(event.data);
        };

        mediaRecorder.onstop = async () => {
            const blobType = mediaRecorder?.mimeType || mediaRecorderMimeType;
            recordedAudioBlob = recordedChunks.length > 0
                ? new Blob(recordedChunks, { type: blobType })
                : null;
            recordedChunks = [];
            releaseRecordingStream();

            const durationMs = recordingStartedAt ? Date.now() - recordingStartedAt : null;
            const tooShort = !recordedAudioBlob
                || recordedAudioBlob.size < 2000
                || (durationMs !== null && durationMs < 400);

            if (tooShort) {
                recordedAudioBlob = null;
                if (playbackObjectUrl) {
                    URL.revokeObjectURL(playbackObjectUrl);
                    playbackObjectUrl = null;
                }
                playRecordingBtn.disabled = true;
                setRecordingStatus('No speech detected. Hold the button a little longer and try again.', 'error');
                return;
            }

            playRecordingBtn.disabled = false;
            hasRecordedThisWord = true;
            nextWordBtn.disabled = false;
            rememberManualRecordingAttempt(recordedAudioBlob, durationMs);

            if (voiceUploadConfig.enabled) {
                setRecordingStatus('Uploading recording...', 'neutral');
                try {
                    await persistMicRecording(recordedAudioBlob, {
                        recordingSource: 'manual_record',
                        durationMs,
                    });
                    setRecordingStatus('Recording saved!', 'success');
                } catch (error) {
                    setRecordingStatus(error?.message
                        ? `${error.message} You can skip this word.`
                        : 'Recording saved locally (upload failed).', 'warning');
                }
            } else {
                setRecordingStatus('Recording saved!', 'success');
            }
        };

        return true;
    } catch (error) {
        console.error('Error accessing microphone:', error);
        const denied = error?.name === 'NotAllowedError' || error?.name === 'PermissionDeniedError';
        setRecordingStatus(denied
            ? 'Microphone permission denied. Allow the microphone in your browser settings, then try again.'
            : 'Microphone is blocked. Check browser site permissions, then try again. You can skip this word.', 'error');
        return false;
    }
}

function releaseRecordingStream() {
    if (mediaRecorder?.stream) {
        mediaRecorder.stream.getTracks().forEach((track) => track.stop());
    }
    mediaRecorder = null;
}

async function startManualRecording() {
    if (window.talmaVocabPronunciation.isActive || isRecording) {
        return;
    }

    setRecordingStatus('Requesting microphone access…', 'neutral');
    if (!mediaRecorder) {
        const ok = await initializeRecording();
        if (!ok) return;
    }

    recordedChunks = [];
    try {
        mediaRecorder.start(250);
    } catch (error) {
        console.error('Could not start recording:', error);
        setRecordingStatus('Could not start recording. Try again or refresh the page.', 'error');
        releaseRecordingStream();
        return;
    }

    isRecording = true;
    recordingStartedAt = Date.now();
    recordBtn.disabled = true;
    recordBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i> Listening...';
    recordBtn.classList.add('ring-2', 'ring-red-400', 'ring-offset-2');
    setRecordingStatus('Listening… say the word now.', 'neutral');
    playRecordingBtn.disabled = true;

    const speechBtn = document.getElementById('speech-check-btn');
    if (speechBtn) {
        speechBtn.disabled = true;
    }

    recordingMaxTimeout = setTimeout(() => {
        if (isRecording) {
            finishManualRecording();
        }
    }, voiceUploadConfig.maxSeconds * 1000);
}

function finishManualRecording() {
    if (!isRecording || !mediaRecorder) {
        return;
    }

    if (typeof mediaRecorder.requestData === 'function') {
        mediaRecorder.requestData();
    }
    mediaRecorder.stop();
    isRecording = false;
    clearTimeout(recordingMaxTimeout);
    recordBtn.disabled = false;
    recordBtn.innerHTML = MIC_IDLE_LABEL;
    recordBtn.classList.remove('ring-2', 'ring-red-400', 'ring-offset-2');
    setRecordingStatus('Processing…', 'neutral');

    const speechBtn = document.getElementById('speech-check-btn');
    if (speechBtn && !window.talmaVocabPronunciation.isActive) {
        speechBtn.disabled = false;
    }
}

recordBtn?.addEventListener('click', startManualRecording);

playRecordingBtn?.addEventListener('click', () => {
    const blob = recordedAudioBlob || window.talmaVocabPronunciation.lastAttempt?.audioBlob;
    if (!blob) return;
    if (playbackObjectUrl) {
        URL.revokeObjectURL(playbackObjectUrl);
    }
    playbackObjectUrl = URL.createObjectURL(blob);
    playbackAudio.src = playbackObjectUrl;
    playbackAudio.play().catch((error) => {
        console.error('Playback failed:', error);
        setRecordingStatus('Could not play recording in this browser.', 'error');
    });
});

async function uploadVoiceSample(blob, options = {}) {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const formData = new FormData();
    formData.append('organization_id', voiceUploadConfig.organizationId);
    formData.append('lesson_id', voiceUploadConfig.lessonId);
    formData.append('vocabulary_id', voiceUploadConfig.vocabularyId);
    formData.append('generated_sentence', voiceUploadConfig.targetText);
    formData.append('recording', blob, 'recording.webm');
    formData.append('recording_source', options.recordingSource || 'manual_record');
    const durationMs = options.durationMs ?? (recordingStartedAt ? Date.now() - recordingStartedAt : null);
    if (durationMs) {
        formData.append('duration_ms', String(durationMs));
    }

    const response = await fetch(voiceUploadConfig.endpoint, {
        method: 'POST',
        headers: token ? { 'X-CSRF-TOKEN': token } : {},
        body: formData,
    });

    if (!response.ok) {
        const body = await response.json().catch(() => ({}));
        throw new Error(body.error || 'Upload failed');
    }

    return response.json();
}

async function persistMicRecording(blob, options = {}) {
    if (!voiceUploadConfig.enabled || !blob || blob.size < 2000) {
        return false;
    }

    if (window.talmaVocabPronunciation.recordingUploaded) {
        return true;
    }

    await uploadVoiceSample(blob, options);
    window.talmaVocabPronunciation.recordingUploaded = true;
    return true;
}

function rememberManualRecordingAttempt(blob, durationMs) {
    pendingPronunciationResult = {
        pass: true,
        heard: '',
        ratio: null,
        audioBlob: blob,
        durationMs: durationMs,
    };
    finalizePronunciationAttempt();
}

function buildPronunciationMeta({ skipped = false } = {}) {
    if (!speechFeedbackConfig.enabled) {
        return null;
    }

    if (skipped) {
        return {
            pronunciation_pass: false,
            skipped: true,
            source: 'pronunciation_check',
            audio_captured: false,
        };
    }

    const attempt = window.talmaVocabPronunciation.lastAttempt;

    return {
        pronunciation_pass: attempt?.pass === true,
        heard: attempt?.heard || null,
        ratio: typeof attempt?.ratio === 'number' ? attempt.ratio : null,
        source: 'pronunciation_check',
        skipped: false,
        audio_captured: !!(attempt?.audioBlob && attempt.audioBlob.size > 0),
        audio_uploaded: false,
    };
}

async function logVocabProgress({ skipped = false, metaOverride = null } = {}) {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const meta = metaOverride ?? buildPronunciationMeta({ skipped });
    const payload = {
        lesson_id: voiceUploadConfig.lessonId,
        activity_type: 'vocabulary',
        activity_id: voiceUploadConfig.vocabularyId,
        status: 'completed',
    };

    if (meta) {
        payload.meta = meta;
    }

    const response = await fetch(activityEventEndpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...(token ? { 'X-CSRF-TOKEN': token } : {}),
        },
        body: JSON.stringify(payload),
    });

    if (!response.ok) {
        throw new Error('Could not save vocabulary progress.');
    }
}

async function advanceVocabularyWord({ skipped = false } = {}) {
    const attempt = window.talmaVocabPronunciation.lastAttempt;

    if (!skipped && voiceUploadConfig.enabled && attempt?.audioBlob && attempt.audioBlob.size > 0) {
        try {
            await persistMicRecording(attempt.audioBlob, {
                recordingSource: speechFeedbackConfig.enabled ? 'pronunciation_check' : 'manual_record',
                durationMs: attempt.durationMs,
            });
        } catch (error) {
            throw new Error(error?.message || 'Could not upload pronunciation recording.');
        }
    }

    const meta = buildPronunciationMeta({ skipped });
    if (meta && !skipped && voiceUploadConfig.enabled && attempt?.audioBlob && attempt.audioBlob.size > 0) {
        meta.audio_uploaded = true;
    }

    await logVocabProgress({ skipped, metaOverride: meta });
}

function finishVocabularyStep() {
    document.getElementById('vocab-step').classList.add('hidden');
    document.getElementById('vocab-complete').classList.remove('hidden');
}

const VOCAB_SEGMENT_COLORS = {
    learned: 'bg-green-500',
    needs_practice: 'bg-amber-400',
    skipped: 'bg-gray-300',
    not_started: 'bg-gray-200',
};

function updateVocabularyProgressBarSegment(wordId, status) {
    document.querySelectorAll(`[data-vocab-progress-segment="${wordId}"]`).forEach((segment) => {
        segment.className = `vocab-progress-segment flex-1 min-w-[0.35rem] rounded-full transition-colors duration-300 ${VOCAB_SEGMENT_COLORS[status] || VOCAB_SEGMENT_COLORS.not_started}`;
    });
}

function setVocabularyLearnedCount(count) {
    document.querySelectorAll('.vocab-learned-count').forEach((el) => {
        el.textContent = String(count);
    });

    document.querySelectorAll('.vocab-progress-bar').forEach((bar) => {
        bar.setAttribute('aria-valuenow', String(count));
        const max = bar.getAttribute('aria-valuemax') || '0';
        bar.setAttribute('aria-label', `Vocabulary progress: ${count} of ${max} words mastered`);
    });

    const completeLearnedEl = document.getElementById('vocab-complete-learned-count');
    if (completeLearnedEl) {
        completeLearnedEl.textContent = String(count);
    }
}

function updateVocabularyVisitedSummary() {
    const total = document.querySelector('.vocab-progress-bar')?.getAttribute('aria-valuemax');
    if (!total) {
        return;
    }

    const visited = document.querySelectorAll('[data-vocab-progress-segment].bg-green-500, [data-vocab-progress-segment].bg-amber-400, [data-vocab-progress-segment].bg-gray-300').length;
    const learned = parseInt(document.querySelector('.vocab-learned-count')?.textContent || '0', 10) || 0;

    document.querySelectorAll('.vocab-visited-summary').forEach((el) => {
        if (visited > 0 && learned < parseInt(total, 10)) {
            el.textContent = `${visited} of ${total} words practiced`;
            el.classList.remove('hidden');
        } else {
            el.classList.add('hidden');
        }
    });
}

function updateVocabularyProgressChip({ skipped = false } = {}) {
    const chips = document.querySelectorAll(`[data-vocab-word-id="${voiceUploadConfig.vocabularyId}"]`);
    if (!chips.length) {
        return;
    }

    const attempt = window.talmaVocabPronunciation.lastAttempt;
    let status = 'needs_practice';

    if (skipped) {
        status = 'skipped';
    } else if (!speechFeedbackConfig.enabled || attempt?.pass === true) {
        status = 'learned';
    }

    const alreadyCountedLearned = Array.from(chips).some((chip) => chip.dataset.countedLearned === 'true');

    chips.forEach((chip) => {
        const wordLabel = chip.dataset.wordLabel || chip.textContent.trim();

        chip.className = 'inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium border';

        if (status === 'learned') {
            chip.classList.add('border-green-300', 'bg-green-50', 'text-green-800');
            chip.innerHTML = `<i class="fas fa-check text-[10px]" aria-hidden="true"></i> ${wordLabel}`;
            chip.dataset.countedLearned = 'true';
        } else if (status === 'needs_practice') {
            chip.classList.add('border-amber-200', 'bg-amber-50', 'text-amber-800');
            chip.innerHTML = `<i class="fas fa-redo text-[10px]" aria-hidden="true"></i> ${wordLabel}`;
        } else {
            chip.classList.add('border-gray-200', 'bg-gray-50', 'text-gray-600');
            chip.textContent = wordLabel;
        }
    });

    updateVocabularyProgressBarSegment(voiceUploadConfig.vocabularyId, status);

    if (status === 'learned' && !alreadyCountedLearned) {
        const learnedEl = document.querySelector('.vocab-learned-count');
        const nextCount = (parseInt(learnedEl?.textContent || '0', 10) || 0) + 1;
        setVocabularyLearnedCount(nextCount);
    }

    updateVocabularyVisitedSummary();
}

nextWordBtn.addEventListener('click', async () => {
    nextWordBtn.disabled = true;

    try {
        await advanceVocabularyWord();
        updateVocabularyProgressChip();
    } catch (error) {
        nextWordBtn.disabled = false;
        const message = error?.message || 'Could not save progress. Try again.';
        setPronunciationStatus(message, 'error');
        return;
    }

    if (isLastWord) {
        finishVocabularyStep();
    } else {
        window.location.reload();
    }
});

document.getElementById('skip-word-btn').addEventListener('click', async () => {
    if (voiceUploadConfig.enabled && ! confirm('Skip recording this word?')) {
        return;
    }

    const skipBtn = document.getElementById('skip-word-btn');
    skipBtn.disabled = true;

    try {
        await advanceVocabularyWord({ skipped: true });
        updateVocabularyProgressChip({ skipped: true });
    } catch (error) {
        skipBtn.disabled = false;
        setPronunciationStatus(error?.message || 'Could not save progress. Try again.', 'error');
        return;
    }

    if (isLastWord) {
        finishVocabularyStep();
    } else {
        window.location.reload();
    }
});

(function initSpeechFeedback() {
    if (!speechFeedbackConfig.enabled) {
        return;
    }

    const speechBtn = document.getElementById('speech-check-btn');
    const speechFeedback = document.getElementById('speech-check-feedback');
    const unsupportedNote = document.getElementById('speech-unsupported-note');
    const recordBtnEl = document.getElementById('record-btn');
    let activeCheck = null;

    if (!speechBtn) {
        return;
    }

    if (typeof TalmaSpeech === 'undefined') {
        speechBtn.disabled = true;
        setPronunciationStatus('Speech tools failed to load. Refresh the page and try again.', 'error');
        return;
    }

    if (!TalmaSpeech.isSupported()) {
        speechBtn.disabled = true;
        if (unsupportedNote) {
            unsupportedNote.classList.remove('hidden');
        }
        setPronunciationStatus('Pronunciation check needs Chrome or Edge on a computer with a microphone.', 'warning');
        return;
    }

    function setSpeechButtonListening(listening) {
        window.talmaVocabPronunciation.isActive = listening;
        speechBtn.disabled = listening;
        if (recordBtnEl) {
            recordBtnEl.disabled = listening;
        }
        speechBtn.innerHTML = listening
            ? '<i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i> Listening...'
            : MIC_IDLE_LABEL;
    }

    function storePronunciationAttempt(result) {
        pendingPronunciationResult = {
            pass: result.pass === true,
            heard: result.heard || '',
            ratio: typeof result.ratio === 'number' ? result.ratio : null,
            audioBlob: result.audioBlob || null,
            durationMs: typeof result.durationMs === 'number' ? result.durationMs : null,
        };
    }

    function finishPronunciationCheck() {
        finalizePronunciationAttempt();
        setSpeechButtonListening(false);
        activeCheck = null;
    }

    function speechErrorMessage(error) {
        if (error.code === 'no-speech') {
            return 'No speech detected. Speak clearly when you see Listening, then try again.';
        }
        if (error.code === 'audio-capture' || error.code === 'mic-unavailable') {
            return 'Microphone is busy. Refresh the page, then try again.';
        }
        if (error.code === 'not-allowed') {
            return 'Microphone permission denied. Allow the microphone in your browser settings, then try again.';
        }
        if (error.code === 'network') {
            return 'Speech check could not reach the browser service. Check your connection and try again.';
        }
        if (error.code === 'unsupported') {
            return 'Speech check is not supported in this browser. Try Chrome or Edge on desktop.';
        }
        return 'Could not check speech. Try again or use Chrome/Edge.';
    }

    function showInlineSpeechFeedback(result) {
        const heard = result.heard ? ` We heard “${result.heard}”.` : '';
        if (result.pass) {
            setPronunciationStatus(`Nice work!${heard}`, 'success');
        } else {
            setPronunciationStatus(`Almost — try again.${heard}`, 'warning');
        }

        if (playRecordingBtn && result.audioBlob && result.audioBlob.size > 0) {
            recordedAudioBlob = result.audioBlob;
            playRecordingBtn.disabled = false;
        }
    }

    async function uploadSpeechRecording(result) {
        if (!voiceUploadConfig.enabled || !result?.audioBlob || result.audioBlob.size < 2000) {
            return;
        }

        const heard = result.heard ? ` We heard “${result.heard}”.` : '';
        const baseMessage = result.pass
            ? `Nice work!${heard}`
            : `Almost — try again.${heard}`;

        try {
            setPronunciationStatus(`${baseMessage} Saving recording…`, result.pass ? 'success' : 'warning');
            await persistMicRecording(result.audioBlob, {
                recordingSource: 'pronunciation_check',
                durationMs: result.durationMs,
            });
            setPronunciationStatus(`${baseMessage} Recording saved!`, result.pass ? 'success' : 'warning');
        } catch (error) {
            setPronunciationStatus(`${baseMessage} ${error?.message || 'Recording upload failed.'} You can try again or skip.`, 'error');
        }
    }

    speechBtn.addEventListener('click', () => {
        setPronunciationStatus('', 'neutral');

        speechBtn.disabled = true;
        speechBtn.innerHTML = '<i class="fas fa-microphone" aria-hidden="true"></i> Starting...';

        if (activeCheck) {
            activeCheck.abort();
            pendingPronunciationResult = null;
            activeCheck = null;
        }

        if (speechFeedback) {
            speechFeedback.classList.add('hidden');
            speechFeedback.innerHTML = '';
        }

        activeCheck = TalmaSpeech.checkPronunciation({
            target: speechFeedbackConfig.targetText,
            lang: speechFeedbackConfig.lang,
            maxSeconds: speechFeedbackConfig.maxSeconds,
            recordAudio: voiceUploadConfig.enabled,
            onRequestingMic: () => {
                speechBtn.disabled = true;
                if (recordBtnEl) {
                    recordBtnEl.disabled = true;
                }
                speechBtn.innerHTML = '<i class="fas fa-microphone" aria-hidden="true"></i> Allow microphone...';
                setPronunciationStatus('Allow microphone access when your browser asks.', 'neutral');
            },
            onListening: () => {
                setSpeechButtonListening(true);
                setPronunciationStatus('Listening… say the word now.', 'neutral');
            },
            onFeedback: (result) => {
                storePronunciationAttempt(result);
                showInlineSpeechFeedback(result);
                uploadSpeechRecording(result);
            },
            onError: (error) => {
                storePronunciationAttempt({
                    pass: false,
                    heard: error.heard || '',
                    ratio: 0,
                    audioBlob: error.audioBlob || null,
                    durationMs: error.durationMs || null,
                });

                if (error.code === 'unsupported') {
                    speechBtn.disabled = true;
                    if (unsupportedNote) {
                        unsupportedNote.classList.remove('hidden');
                    }
                }

                if (playRecordingBtn && error.audioBlob && error.audioBlob.size > 0) {
                    recordedAudioBlob = error.audioBlob;
                    playRecordingBtn.disabled = false;
                }

                setPronunciationStatus(speechErrorMessage(error), 'error');
            },
            onEnd: () => {
                finishPronunciationCheck();
            },
        });
    });
})();
</script>
@endpush

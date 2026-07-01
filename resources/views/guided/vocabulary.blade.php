@extends('layouts.app')

@section('title', 'Vocabulary - ' . $lesson->title)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-6 md:py-8">
    <div class="container mx-auto px-4 max-w-2xl">
        <div class="mb-6">
            <a href="{{ isset($org) && $org ? route('org.student.lesson', [$org, $lesson->slug]) : route('lessons.show', $lesson->slug) }}"
               class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium transition-colors duration-200 group">
                <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform duration-200"></i>
                <span>Back to lesson</span>
            </a>
        </div>

        @if(!empty($guidedFlow))
            <p class="text-xs text-gray-500 mb-4">Step {{ $guidedFlow['currentIndex'] }} of {{ $guidedFlow['totalSteps'] }}</p>
        @endif

        @include('partials.vocabulary-progress-strip', [
            'vocabularyProgress' => $vocabularyProgress ?? null,
            'words' => $words ?? collect(),
            'currentWordId' => $currentWord->id ?? null,
        ])

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 md:p-8 text-center" id="vocab-step">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">
                Vocabulary · Word {{ $wordIndex + 1 }} of {{ $wordsCount }}
            </p>

            @if($currentWord->image_url)
                <img src="{{ $currentWord->image_url }}"
                     alt="{{ $currentWord->english_word }}"
                     class="mx-auto max-h-48 rounded-xl object-cover mb-4">
            @endif

            <h1 class="text-3xl font-bold text-gray-900 mb-3">{{ $currentWord->english_word }}</h1>

            @if($currentWord->hebrew_translation || $currentWord->arabic_translation)
                <div class="flex flex-wrap justify-center gap-2 mb-4">
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
                        class="mb-6 inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 bg-gray-50 text-gray-700 hover:bg-blue-50 hover:border-blue-200 transition-colors">
                    <i class="fas fa-volume-up" aria-hidden="true"></i>
                    Listen
                </button>
            @endif

            @if($speechFeedbackEnabled ?? false)
            <div class="border-t border-gray-100 pt-6" id="speech-feedback-section">
                <p class="text-sm text-gray-600 mb-4">Say the word aloud and get instant feedback</p>
                <button type="button" id="speech-check-btn"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-purple-600 text-white font-semibold rounded-xl hover:bg-purple-700 transition-colors disabled:opacity-50"
                        aria-label="Check my pronunciation">
                    <i class="fas fa-microphone-alt" aria-hidden="true"></i>
                    Check my pronunciation
                </button>
                <p id="speech-check-status" class="text-sm text-gray-500 min-h-[1.25rem] mt-3"></p>
                <div id="speech-check-feedback" class="hidden mt-3"></div>
                <p id="speech-unsupported-note" class="hidden text-xs text-gray-500 mt-2">
                    Pronunciation check works best in Chrome or Edge on desktop.
                </p>
            </div>
            @endif

            @if($voiceUploadEnabled ?? false)
            <div class="border-t border-gray-100 pt-6">
                <p class="text-sm text-gray-600 mb-4">Record yourself saying the word</p>
                <div class="flex flex-wrap justify-center gap-3 mb-3">
                    <button type="button" id="record-btn"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                        <i class="fas fa-microphone" aria-hidden="true"></i>
                        Record
                    </button>
                    <button type="button" id="play-recording-btn" disabled
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-colors disabled:opacity-50">
                        <i class="fas fa-play" aria-hidden="true"></i>
                        Play back
                    </button>
                </div>
                <p id="recording-status" class="text-sm text-gray-500 min-h-[1.25rem]"></p>
            </div>
            @elseif(!empty($voiceRecordingOffered))
            <div class="border-t border-gray-100 pt-6">
                <p class="text-sm text-gray-600 mb-3">Record yourself saying the word</p>
                @if(($voiceProfileBlockedReason ?? null) === 'select_child' && isset($org) && $org)
                    <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 mb-4">
                        Choose who is practicing to save recordings.
                        <a href="{{ route('org.student.select-child', $org) }}" class="font-semibold underline underline-offset-2">Select a child</a>
                    </p>
                @elseif(($voiceProfileBlockedReason ?? null) === 'profile_incomplete' && isset($org) && $org)
                    <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 mb-4">
                        Complete your learner profile to save recordings.
                        <a href="{{ route('org.student.complete-voice-profile', $org) }}" class="font-semibold underline underline-offset-2">Complete profile</a>
                    </p>
                @else
                    <p class="text-sm text-gray-500 mb-4">Recording will be available once your learner profile is complete.</p>
                @endif
            </div>
            @elseif(!($speechFeedbackEnabled ?? false))
            <p class="text-sm text-gray-500 mb-6">Tap listen, then continue when you are ready.</p>
            @endif

            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
                <button type="button" id="next-word-btn"
                        class="px-6 py-3 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 transition-colors">
                    @if($isLastWord)
                        Continue
                    @else
                        Next word
                    @endif
                </button>
                <button type="button" id="skip-word-btn"
                        class="text-sm text-gray-500 hover:text-gray-700 underline underline-offset-2">
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
const recordingStatus = document.getElementById('recording-status');
const nextWordBtn = document.getElementById('next-word-btn');
const playbackAudio = document.getElementById('playback-audio');
const modelAudio = document.getElementById('model-audio');

document.getElementById('play-model-btn')?.addEventListener('click', () => {
    @if($currentWord->word_audio_url)
    modelAudio.src = @json($currentWord->word_audio_url);
    modelAudio.play();
    @endif
});

function setRecordingStatus(message) {
    if (recordingStatus) {
        recordingStatus.textContent = message;
    }
}

async function initializeRecording() {
    if (!navigator.mediaDevices?.getUserMedia) {
        setRecordingStatus('Recording is not supported in this browser. Try Chrome or Edge.');
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
                setRecordingStatus('Recording was too short or empty. Hold Record a little longer and try again.');
                return;
            }

            playRecordingBtn.disabled = false;
            hasRecordedThisWord = true;
            nextWordBtn.disabled = false;

            if (voiceUploadConfig.enabled) {
                setRecordingStatus('Uploading recording...');
                try {
                    await uploadVoiceSample(recordedAudioBlob, {
                        recordingSource: 'manual_record',
                        durationMs,
                    });
                    setRecordingStatus('Recording saved!');
                } catch (error) {
                    setRecordingStatus(error?.message
                        ? `${error.message} You can skip this word.`
                        : 'Recording saved locally (upload failed).');
                }
            } else {
                setRecordingStatus('Recording saved!');
            }
        };

        return true;
    } catch (error) {
        console.error('Error accessing microphone:', error);
        const denied = error?.name === 'NotAllowedError' || error?.name === 'PermissionDeniedError';
        setRecordingStatus(denied
            ? 'Microphone access denied. Click the lock icon in the address bar, allow the microphone, then try again.'
            : 'Microphone is blocked. Check browser site permissions, then try again. You can skip this word.');
        return false;
    }
}

function releaseRecordingStream() {
    if (mediaRecorder?.stream) {
        mediaRecorder.stream.getTracks().forEach((track) => track.stop());
    }
    mediaRecorder = null;
}

async function toggleRecording() {
    if (window.talmaVocabPronunciation.isActive) {
        setRecordingStatus('Finish the pronunciation check first, then record.');
        return;
    }

    if (!isRecording) {
        setRecordingStatus('Requesting microphone access…');
        if (!mediaRecorder) {
            const ok = await initializeRecording();
            if (!ok) return;
        }
        recordedChunks = [];
        try {
            mediaRecorder.start(250);
        } catch (error) {
            console.error('Could not start recording:', error);
            setRecordingStatus('Could not start recording. Try again or refresh the page.');
            releaseRecordingStream();
            return;
        }
        isRecording = true;
        recordingStartedAt = Date.now();
        recordBtn.innerHTML = '<i class="fas fa-stop"></i> Stop';
        recordBtn.classList.add('ring-2', 'ring-red-400', 'ring-offset-2');
        setRecordingStatus('Recording… say the word now.');
        playRecordingBtn.disabled = true;
        const speechBtn = document.getElementById('speech-check-btn');
        if (speechBtn) {
            speechBtn.disabled = true;
        }
        recordingMaxTimeout = setTimeout(() => {
            if (isRecording) toggleRecording();
        }, voiceUploadConfig.maxSeconds * 1000);
    } else {
        if (typeof mediaRecorder.requestData === 'function') {
            mediaRecorder.requestData();
        }
        mediaRecorder.stop();
        isRecording = false;
        clearTimeout(recordingMaxTimeout);
        recordBtn.innerHTML = '<i class="fas fa-microphone"></i> Record';
        recordBtn.classList.remove('ring-2', 'ring-red-400', 'ring-offset-2');
        setRecordingStatus('Processing…');
        const speechBtn = document.getElementById('speech-check-btn');
        if (speechBtn && !window.talmaVocabPronunciation.isActive) {
            speechBtn.disabled = false;
        }
    }
}

recordBtn?.addEventListener('click', toggleRecording);

playRecordingBtn?.addEventListener('click', () => {
    if (!recordedAudioBlob) return;
    if (playbackObjectUrl) {
        URL.revokeObjectURL(playbackObjectUrl);
    }
    playbackObjectUrl = URL.createObjectURL(recordedAudioBlob);
    playbackAudio.src = playbackObjectUrl;
    playbackAudio.play().catch((error) => {
        console.error('Playback failed:', error);
        setRecordingStatus('Could not play recording in this browser.');
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
            await uploadVoiceSample(attempt.audioBlob, {
                recordingSource: 'pronunciation_check',
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

function updateVocabularyProgressChip({ skipped = false } = {}) {
    const chip = document.querySelector(`[data-vocab-word-id="${voiceUploadConfig.vocabularyId}"]`);
    if (!chip) {
        return;
    }

    const wordLabel = chip.dataset.wordLabel || chip.textContent.trim();
    const attempt = window.talmaVocabPronunciation.lastAttempt;
    let status = 'needs_practice';

    if (skipped) {
        status = 'skipped';
    } else if (!speechFeedbackConfig.enabled || attempt?.pass === true) {
        status = 'learned';
    }

    chip.className = 'inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium border';

    if (status === 'learned') {
        chip.classList.add('border-green-300', 'bg-green-50', 'text-green-800');
        chip.innerHTML = `<i class="fas fa-check text-[10px]" aria-hidden="true"></i> ${wordLabel}`;
    } else if (status === 'needs_practice') {
        chip.classList.add('border-amber-200', 'bg-amber-50', 'text-amber-800');
        chip.innerHTML = `<i class="fas fa-redo text-[10px]" aria-hidden="true"></i> ${wordLabel}`;
    } else {
        chip.classList.add('border-gray-200', 'bg-gray-50', 'text-gray-600');
        chip.textContent = wordLabel;
    }

    if (status === 'learned' && chip.dataset.countedLearned !== 'true') {
        chip.dataset.countedLearned = 'true';
        const learnedEl = document.getElementById('vocab-learned-count');
        if (learnedEl) {
            learnedEl.textContent = String((parseInt(learnedEl.textContent, 10) || 0) + 1);
        }
        const completeLearnedEl = document.getElementById('vocab-complete-learned-count');
        if (completeLearnedEl && learnedEl) {
            completeLearnedEl.textContent = learnedEl.textContent;
        }
    }
}

nextWordBtn.addEventListener('click', async () => {
    nextWordBtn.disabled = true;

    try {
        await advanceVocabularyWord();
        updateVocabularyProgressChip();
    } catch (error) {
        nextWordBtn.disabled = false;
        const message = error?.message || 'Could not save progress. Try again.';
        if (recordingStatus) {
            recordingStatus.textContent = message;
        }
        const speechStatus = document.getElementById('speech-check-status');
        if (speechStatus) {
            speechStatus.textContent = message;
        }
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
        if (recordingStatus) {
            recordingStatus.textContent = error?.message || 'Could not save progress. Try again.';
        }
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
    const speechStatus = document.getElementById('speech-check-status');
    const speechFeedback = document.getElementById('speech-check-feedback');
    const unsupportedNote = document.getElementById('speech-unsupported-note');
    const recordBtnEl = document.getElementById('record-btn');
    let activeCheck = null;

    if (!speechBtn) {
        return;
    }

    if (typeof TalmaSpeech === 'undefined') {
        speechBtn.disabled = true;
        if (speechStatus) {
            speechStatus.textContent = 'Speech tools failed to load. Refresh the page and try again.';
        }
        return;
    }

    if (!TalmaSpeech.isSupported()) {
        speechBtn.disabled = true;
        if (unsupportedNote) {
            unsupportedNote.classList.remove('hidden');
        }
        if (speechStatus) {
            speechStatus.textContent = 'Pronunciation check needs Chrome or Edge on a computer with a microphone.';
        }
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
            : '<i class="fas fa-microphone-alt" aria-hidden="true"></i> Check my pronunciation';
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
            return 'We did not hear anything. Try again.';
        }
        if (error.code === 'not-allowed') {
            return 'Microphone access denied. Allow the microphone in your browser settings, then try again.';
        }
        if (error.code === 'network') {
            return 'Speech check could not reach the browser service. Check your connection and try again.';
        }
        if (error.code === 'unsupported') {
            return 'Speech check is not supported in this browser. Try Chrome or Edge on desktop.';
        }
        return 'Could not check speech. Try again or use Chrome/Edge.';
    }

    speechBtn.addEventListener('click', () => {
        if (speechStatus) {
            speechStatus.textContent = '';
        }

        speechBtn.disabled = true;
        speechBtn.innerHTML = '<i class="fas fa-microphone-alt" aria-hidden="true"></i> Starting...';

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
            recordAudio: false,
            onRequestingMic: () => {
                speechBtn.disabled = true;
                if (recordBtnEl) {
                    recordBtnEl.disabled = true;
                }
                speechBtn.innerHTML = '<i class="fas fa-microphone-alt" aria-hidden="true"></i> Allow microphone...';
                if (speechStatus) {
                    speechStatus.textContent = 'Allow microphone access when your browser asks.';
                }
            },
            onListening: () => {
                setSpeechButtonListening(true);
                if (speechStatus) {
                    speechStatus.textContent = 'Listening... say the word now.';
                }
            },
            onFeedback: (result) => {
                storePronunciationAttempt(result);
                if (speechFeedback) {
                    TalmaSpeech.renderFeedback(speechFeedback, result, {
                        pass: 'Nice work!',
                        fail: 'Almost — try again.',
                    });
                    speechFeedback.classList.remove('hidden');
                }
                if (speechStatus) {
                    speechStatus.textContent = '';
                }
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
                if (speechStatus) {
                    speechStatus.textContent = speechErrorMessage(error);
                }
            },
            onEnd: () => {
                finishPronunciationCheck();
            },
        });
    });
})();
</script>
@endpush

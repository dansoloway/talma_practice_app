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
                    Instant speech check works best in Chrome or Edge on desktop.
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
            <p class="text-gray-600 mb-6">Great job practicing all the words.</p>
            @include('partials.guided-flow-nav', ['guidedFlow' => $guidedFlow ?? null, 'lesson' => $lesson, 'fallbackLabel' => 'Back to Lesson'])
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

<script>
const voiceUploadConfig = @json($voiceUploadConfigData);
const speechFeedbackConfig = @json($speechFeedbackConfigData);
const activityEventEndpoint = @json(route('activity-events.store'));
const continueUrl = @json($continueUrl);
const isLastWord = @json($isLastWord);
const lessonShowUrl = @json($lessonShowUrl);

let mediaRecorder = null;
let recordedChunks = [];
let recordedAudioBlob = null;
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

async function initializeRecording() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);

        mediaRecorder.ondataavailable = (event) => {
            if (event.data.size > 0) recordedChunks.push(event.data);
        };

        mediaRecorder.onstop = async () => {
            recordedAudioBlob = new Blob(recordedChunks, { type: 'audio/webm' });
            recordedChunks = [];
            playRecordingBtn.disabled = false;
            hasRecordedThisWord = true;
            nextWordBtn.disabled = false;

            if (voiceUploadConfig.enabled) {
                recordingStatus.textContent = 'Uploading recording...';
                try {
                    await uploadVoiceSample(recordedAudioBlob);
                    recordingStatus.textContent = 'Recording saved!';
                } catch (error) {
                    recordingStatus.textContent = error?.message
                        ? `${error.message} You can skip this word.`
                        : 'Recording saved locally (upload failed).';
                }
            } else {
                recordingStatus.textContent = 'Recording saved!';
            }

            await logVocabComplete();
        };

        return true;
    } catch (error) {
        console.error('Error accessing microphone:', error);
        recordingStatus.textContent = 'Microphone is blocked. Check browser site permissions, then try again. You can skip this word.';
        return false;
    }
}

async function toggleRecording() {
    if (!isRecording) {
        if (!mediaRecorder) {
            const ok = await initializeRecording();
            if (!ok) return;
        }
        recordedChunks = [];
        mediaRecorder.start();
        isRecording = true;
        recordingStartedAt = Date.now();
        recordBtn.innerHTML = '<i class="fas fa-stop"></i> Stop';
        recordingStatus.textContent = 'Recording...';
        playRecordingBtn.disabled = true;
        recordingMaxTimeout = setTimeout(() => {
            if (isRecording) toggleRecording();
        }, voiceUploadConfig.maxSeconds * 1000);
    } else {
        mediaRecorder.stop();
        isRecording = false;
        clearTimeout(recordingMaxTimeout);
        recordBtn.innerHTML = '<i class="fas fa-microphone"></i> Record';
        recordingStatus.textContent = 'Processing...';
    }
}

recordBtn?.addEventListener('click', toggleRecording);

playRecordingBtn?.addEventListener('click', () => {
    if (!recordedAudioBlob) return;
    playbackAudio.src = URL.createObjectURL(recordedAudioBlob);
    playbackAudio.play().catch((error) => {
        console.error('Playback failed:', error);
        recordingStatus.textContent = 'Could not play recording in this browser.';
    });
});

async function uploadVoiceSample(blob) {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const formData = new FormData();
    formData.append('organization_id', voiceUploadConfig.organizationId);
    formData.append('lesson_id', voiceUploadConfig.lessonId);
    formData.append('vocabulary_id', voiceUploadConfig.vocabularyId);
    formData.append('generated_sentence', voiceUploadConfig.targetText);
    formData.append('recording', blob, 'recording.webm');
    if (recordingStartedAt) {
        formData.append('duration_ms', String(Date.now() - recordingStartedAt));
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

async function logVocabComplete() {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    await fetch(activityEventEndpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...(token ? { 'X-CSRF-TOKEN': token } : {}),
        },
        body: JSON.stringify({
            lesson_id: voiceUploadConfig.lessonId,
            activity_type: 'vocabulary',
            activity_id: voiceUploadConfig.vocabularyId,
            status: 'completed',
        }),
    });
}

function finishVocabularyStep() {
    document.getElementById('vocab-step').classList.add('hidden');
    document.getElementById('vocab-complete').classList.remove('hidden');
}

nextWordBtn.addEventListener('click', async () => {
    if (! voiceUploadConfig.enabled) {
        await logVocabComplete();
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

    await logVocabComplete();
    if (isLastWord) {
        finishVocabularyStep();
    } else {
        window.location.reload();
    }
});

(function initSpeechFeedback() {
    if (!speechFeedbackConfig.enabled || typeof TalmaSpeech === 'undefined') {
        return;
    }

    const speechBtn = document.getElementById('speech-check-btn');
    const speechStatus = document.getElementById('speech-check-status');
    const speechFeedback = document.getElementById('speech-check-feedback');
    const unsupportedNote = document.getElementById('speech-unsupported-note');
    let activeCheck = null;

    if (!speechBtn) {
        return;
    }

    if (!TalmaSpeech.isSupported()) {
        speechBtn.disabled = true;
        if (unsupportedNote) {
            unsupportedNote.classList.remove('hidden');
        }
        return;
    }

    function setSpeechButtonListening(listening) {
        speechBtn.disabled = listening;
        speechBtn.innerHTML = listening
            ? '<i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i> Listening...'
            : '<i class="fas fa-microphone-alt" aria-hidden="true"></i> Check my pronunciation';
    }

    function speechErrorMessage(error) {
        if (error.code === 'no-speech') {
            return 'We did not hear anything. Try again.';
        }
        if (error.code === 'not-allowed') {
            return 'Microphone access denied. Allow the microphone in your browser settings, then try again.';
        }
        if (error.code === 'unsupported') {
            return 'Speech check is not supported in this browser. Try Chrome or Edge on desktop.';
        }
        return 'Could not check speech. Try again or use Chrome/Edge.';
    }

    speechBtn.addEventListener('click', () => {
        if (activeCheck) {
            activeCheck.abort();
            activeCheck = null;
        }

        speechFeedback.classList.add('hidden');
        speechBtn.disabled = true;
        speechBtn.innerHTML = '<i class="fas fa-microphone-alt" aria-hidden="true"></i> Allow microphone...';
        speechStatus.textContent = 'Allow microphone access when your browser asks.';

        activeCheck = TalmaSpeech.checkPronunciation({
            target: speechFeedbackConfig.targetText,
            lang: speechFeedbackConfig.lang,
            maxSeconds: speechFeedbackConfig.maxSeconds,
            onRequestingMic: () => {
                speechBtn.disabled = true;
                speechBtn.innerHTML = '<i class="fas fa-microphone-alt" aria-hidden="true"></i> Allow microphone...';
                speechStatus.textContent = 'Allow microphone access when your browser asks.';
            },
            onListening: () => {
                setSpeechButtonListening(true);
                speechStatus.textContent = 'Listening... say the word now.';
            },
            onFeedback: (result) => {
                TalmaSpeech.renderFeedback(speechFeedback, result, {
                    pass: 'Nice work!',
                    fail: 'Almost — try again.',
                });
                speechFeedback.classList.remove('hidden');
                speechStatus.textContent = '';
            },
            onError: (error) => {
                if (error.code === 'unsupported') {
                    speechBtn.disabled = true;
                    if (unsupportedNote) {
                        unsupportedNote.classList.remove('hidden');
                    }
                }
                speechStatus.textContent = speechErrorMessage(error);
            },
            onEnd: () => {
                setSpeechButtonListening(false);
                activeCheck = null;
            },
        });
    });
})();
</script>
@endsection

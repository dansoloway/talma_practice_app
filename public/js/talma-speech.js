/**
 * Browser speech recognition + lenient transcript scoring for ESL feedback.
 */
(function (global) {
    'use strict';

    var PASS_RATIO = 0.75;
    var SINGLE_WORD_LEVENSHTEIN_THRESHOLD = 0.75;
    var WORD_LEVENSHTEIN_THRESHOLD = 0.8;
    var cachedMicStream = null;

    function getRecognitionConstructor() {
        return global.SpeechRecognition || global.webkitSpeechRecognition || null;
    }

    function hasGetUserMedia() {
        return !!(global.navigator && navigator.mediaDevices && typeof navigator.mediaDevices.getUserMedia === 'function');
    }

    function releaseMicrophoneAccess() {
        if (!cachedMicStream) {
            return;
        }

        cachedMicStream.getTracks().forEach(function (track) {
            track.stop();
        });
        cachedMicStream = null;
    }

    function requestMicrophoneAccess() {
        if (!hasGetUserMedia()) {
            return Promise.reject({
                code: 'unsupported',
                message: 'Microphone is not available in this browser.',
            });
        }

        if (cachedMicStream && cachedMicStream.active) {
            return Promise.resolve(cachedMicStream);
        }

        releaseMicrophoneAccess();

        return navigator.mediaDevices.getUserMedia({ audio: true })
            .then(function (stream) {
                cachedMicStream = stream;
                return stream;
            })
            .catch(function (error) {
                var denied = error && (
                    error.name === 'NotAllowedError' ||
                    error.name === 'PermissionDeniedError' ||
                    error.name === 'SecurityError'
                );

                return Promise.reject({
                    code: denied ? 'not-allowed' : 'mic-unavailable',
                    message: denied
                        ? 'Microphone access denied.'
                        : (error && error.message) || 'Could not access the microphone.',
                });
            });
    }

    function normalizeText(text) {
        if (!text) {
            return '';
        }

        return String(text)
            .toLowerCase()
            .replace(/[^\p{L}\p{N}\s']/gu, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function tokenize(text) {
        var normalized = normalizeText(text);
        if (!normalized) {
            return [];
        }

        return normalized.split(' ').filter(function (word) {
            return word.length > 0;
        });
    }

    function levenshteinRatio(a, b) {
        if (a === b) {
            return 1;
        }

        var maxLen = Math.max(a.length, b.length);
        if (maxLen === 0) {
            return 1;
        }

        var matrix = [];
        var i;
        var j;

        for (i = 0; i <= b.length; i++) {
            matrix[i] = [i];
        }
        for (j = 0; j <= a.length; j++) {
            matrix[0][j] = j;
        }

        for (i = 1; i <= b.length; i++) {
            for (j = 1; j <= a.length; j++) {
                if (b.charAt(i - 1) === a.charAt(j - 1)) {
                    matrix[i][j] = matrix[i - 1][j - 1];
                } else {
                    matrix[i][j] = Math.min(
                        matrix[i - 1][j - 1] + 1,
                        matrix[i][j - 1] + 1,
                        matrix[i - 1][j] + 1
                    );
                }
            }
        }

        return 1 - (matrix[b.length][a.length] / maxLen);
    }

    function findMatchingWordIndex(words, targetWord) {
        for (var i = 0; i < words.length; i++) {
            if (words[i] === targetWord || levenshteinRatio(words[i], targetWord) >= WORD_LEVENSHTEIN_THRESHOLD) {
                return i;
            }
        }

        return -1;
    }

    function hasMediaRecorder() {
        return typeof global.MediaRecorder !== 'undefined';
    }

    function createAudioRecorder(stream) {
        var recorder = null;
        var chunks = [];
        var startedAt = null;
        var recordStream = stream;

        function pickRecorderMimeType() {
            if (!hasMediaRecorder()) {
                return '';
            }

            var candidates = [
                'audio/webm;codecs=opus',
                'audio/webm',
                'audio/mp4',
            ];

            for (var i = 0; i < candidates.length; i++) {
                if (MediaRecorder.isTypeSupported(candidates[i])) {
                    return candidates[i];
                }
            }

            return '';
        }

        function releaseRecordStream() {
            if (!recordStream || recordStream === cachedMicStream) {
                return;
            }

            recordStream.getTracks().forEach(function (track) {
                track.stop();
            });
            recordStream = null;
        }

        return {
            start: function () {
                if (!hasMediaRecorder() || !stream) {
                    return false;
                }

                try {
                    chunks = [];
                    startedAt = Date.now();

                    try {
                        var tracks = stream.getAudioTracks();
                        if (tracks.length > 0) {
                            recordStream = new MediaStream([tracks[0].clone()]);
                        } else {
                            recordStream = stream;
                        }
                    } catch (cloneError) {
                        recordStream = stream;
                    }

                    var mimeType = pickRecorderMimeType();
                    recorder = mimeType
                        ? new MediaRecorder(recordStream, { mimeType: mimeType })
                        : new MediaRecorder(recordStream);
                    recorder.ondataavailable = function (event) {
                        if (event.data && event.data.size > 0) {
                            chunks.push(event.data);
                        }
                    };
                    recorder.start(250);
                    return true;
                } catch (e) {
                    releaseRecordStream();
                    recorder = null;
                    chunks = [];
                    startedAt = null;
                    return false;
                }
            },
            stop: function () {
                return new Promise(function (resolve) {
                    if (!recorder || recorder.state === 'inactive') {
                        releaseRecordStream();
                        resolve({ audioBlob: null, durationMs: null });
                        return;
                    }

                    var durationMs = startedAt ? Date.now() - startedAt : null;

                    recorder.onstop = function () {
                        var blob = chunks.length > 0
                            ? new Blob(chunks, { type: recorder.mimeType || 'audio/webm' })
                            : null;
                        chunks = [];
                        recorder = null;
                        startedAt = null;
                        releaseRecordStream();
                        resolve({ audioBlob: blob, durationMs: durationMs });
                    };

                    try {
                        if (typeof recorder.requestData === 'function') {
                            recorder.requestData();
                        }
                        recorder.stop();
                    } catch (e) {
                        releaseRecordStream();
                        resolve({ audioBlob: null, durationMs: durationMs });
                    }
                });
            },
            abort: function () {
                if (!recorder) {
                    releaseRecordStream();
                    return Promise.resolve({ audioBlob: null, durationMs: null });
                }

                try {
                    if (recorder.state !== 'inactive') {
                        recorder.stop();
                    }
                } catch (e) {
                    /* ignore */
                }

                chunks = [];
                recorder = null;
                startedAt = null;
                releaseRecordStream();
                return Promise.resolve({ audioBlob: null, durationMs: null });
            },
        };
    }

    function attachAudioToResult(result, audio) {
        if (!result || !audio) {
            return result;
        }

        result.audioBlob = audio.audioBlob || null;
        result.durationMs = audio.durationMs || null;
        return result;
    }

    function scoreTranscript(transcript, target, passRatio) {
        passRatio = typeof passRatio === 'number' ? passRatio : PASS_RATIO;

        var targetWords = tokenize(target);
        var spokenWords = tokenize(transcript);
        var heard = transcript || '';

        if (targetWords.length === 0) {
            return {
                pass: false,
                ratio: 0,
                heard: heard,
                normalizedTarget: '',
                normalizedTranscript: normalizeText(transcript),
            };
        }

        if (spokenWords.length === 0) {
            return {
                pass: false,
                ratio: 0,
                heard: heard,
                normalizedTarget: normalizeText(target),
                normalizedTranscript: '',
            };
        }

        if (targetWords.length === 1) {
            var targetWord = targetWords[0];
            var singleMatch = spokenWords.some(function (spoken) {
                return spoken === targetWord || levenshteinRatio(spoken, targetWord) >= SINGLE_WORD_LEVENSHTEIN_THRESHOLD;
            });

            return {
                pass: singleMatch,
                ratio: singleMatch ? 1 : 0,
                heard: heard,
                normalizedTarget: normalizeText(target),
                normalizedTranscript: normalizeText(transcript),
            };
        }

        var remaining = spokenWords.slice();
        var matched = 0;
        var idx;

        targetWords.forEach(function (targetWord) {
            idx = findMatchingWordIndex(remaining, targetWord);
            if (idx >= 0) {
                matched++;
                remaining.splice(idx, 1);
            }
        });

        var ratio = matched / targetWords.length;

        return {
            pass: ratio >= passRatio,
            ratio: Math.round(ratio * 10000) / 10000,
            heard: heard,
            normalizedTarget: normalizeText(target),
            normalizedTranscript: normalizeText(transcript),
        };
    }

    function listen(options) {
        options = options || {};
        var Recognition = getRecognitionConstructor();

        if (!Recognition) {
            if (typeof options.onError === 'function') {
                options.onError({ code: 'unsupported', message: 'Speech recognition is not supported in this browser.' });
            }
            return { abort: function () {} };
        }

        var recognition = new Recognition();
        var finished = false;
        var timeoutId = null;
        var maxSeconds = options.maxSeconds || 10;

        recognition.lang = options.lang || 'en-US';
        recognition.interimResults = false;
        recognition.maxAlternatives = 1;
        recognition.continuous = false;

        function finish() {
            if (finished) {
                return;
            }
            finished = true;
            if (timeoutId) {
                clearTimeout(timeoutId);
                timeoutId = null;
            }
            if (typeof options.onEnd === 'function') {
                options.onEnd();
            }
        }

        recognition.onresult = function (event) {
            if (!event.results || !event.results.length) {
                if (typeof options.onError === 'function') {
                    options.onError({ code: 'no-speech', message: 'No speech was detected.' });
                }
                return;
            }

            var transcript = event.results[0][0].transcript || '';
            if (typeof options.onResult === 'function') {
                options.onResult(transcript, scoreTranscript(transcript, options.target || ''));
            }
        };

        recognition.onerror = function (event) {
            var code = event.error || 'unknown';
            if (code === 'audio-capture') {
                code = 'mic-unavailable';
            }
            if (typeof options.onError === 'function') {
                options.onError({
                    code: code,
                    message: event.message || 'Speech recognition failed.',
                });
            }
        };

        recognition.onend = finish;

        try {
            recognition.start();
        } catch (error) {
            if (typeof options.onError === 'function') {
                options.onError({ code: 'start-failed', message: error.message || 'Could not start speech recognition.' });
            }
            finish();
            return { abort: function () {} };
        }

        timeoutId = setTimeout(function () {
            try {
                recognition.stop();
            } catch (e) {
                /* ignore */
            }
        }, maxSeconds * 1000);

        return {
            abort: function () {
                try {
                    recognition.abort();
                } catch (e) {
                    /* ignore */
                }
                finish();
            },
        };
    }

    function renderFeedback(container, result, messages) {
        if (!container) {
            return;
        }

        messages = messages || {};
        var passMessage = messages.pass || 'Great job!';
        var failMessage = messages.fail || 'Keep practicing — try again.';
        var heardPrefix = messages.heardPrefix || 'We heard:';

        container.classList.remove('hidden', 'text-green-700', 'text-amber-700', 'bg-green-50', 'bg-amber-50', 'border-green-200', 'border-amber-200');
        container.classList.add('border', 'rounded-xl', 'px-4', 'py-3', 'text-sm');

        if (result.pass) {
            container.classList.add('text-green-700', 'bg-green-50', 'border-green-200');
            container.innerHTML = '<p class="font-semibold">' + passMessage + '</p><p class="mt-1">' + heardPrefix + ' “' + escapeHtml(result.heard) + '”</p>';
        } else {
            container.classList.add('text-amber-700', 'bg-amber-50', 'border-amber-200');
            container.innerHTML = '<p class="font-semibold">' + failMessage + '</p><p class="mt-1">' + heardPrefix + ' “' + escapeHtml(result.heard) + '”</p>';
        }
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function checkPronunciation(options) {
        options = options || {};
        var session = {
            aborted: false,
            listenHandle: null,
            audioRecorder: null,
            resultDelivered: false,
            recognitionEnded: false,
            finished: false,
        };

        if (!getRecognitionConstructor()) {
            if (typeof options.onUnsupported === 'function') {
                options.onUnsupported();
            }
            if (typeof options.onError === 'function') {
                options.onError({ code: 'unsupported', message: 'Speech recognition is not supported in this browser.' });
            }
            if (typeof options.onEnd === 'function') {
                options.onEnd();
            }
            return { abort: function () {} };
        }

        if (typeof options.onRequestingMic === 'function') {
            options.onRequestingMic();
        }

        function stopAudioRecorder() {
            if (!session.audioRecorder) {
                return Promise.resolve({ audioBlob: null, durationMs: null });
            }

            var recorder = session.audioRecorder;
            session.audioRecorder = null;
            return recorder.stop();
        }

        function abortAudioRecorder() {
            if (!session.audioRecorder) {
                return Promise.resolve();
            }

            var recorder = session.audioRecorder;
            session.audioRecorder = null;
            return recorder.abort();
        }

        function finishSession() {
            if (session.finished) {
                return;
            }

            session.finished = true;
            releaseMicrophoneAccess();

            if (typeof options.onEnd === 'function') {
                options.onEnd();
            }
        }

        function markResultDelivered() {
            session.resultDelivered = true;
            if (session.recognitionEnded) {
                finishSession();
            }
        }

        function markRecognitionEnded() {
            session.recognitionEnded = true;

            if (session.resultDelivered) {
                finishSession();
                return;
            }

            if (session.aborted) {
                finishSession();
                return;
            }

            global.setTimeout(function () {
                if (session.finished || session.resultDelivered) {
                    if (session.resultDelivered) {
                        finishSession();
                    }
                    return;
                }

                stopAudioRecorder().then(function (audio) {
                    if (session.finished || session.resultDelivered) {
                        if (session.resultDelivered) {
                            finishSession();
                        }
                        return;
                    }

                    if (typeof options.onError === 'function') {
                        options.onError({
                            code: 'no-speech',
                            message: 'No speech was detected.',
                            audioBlob: audio.audioBlob,
                            durationMs: audio.durationMs,
                        });
                    }
                    markResultDelivered();
                });
            }, 250);
        }

        requestMicrophoneAccess()
            .then(function (stream) {
                if (session.aborted) {
                    return;
                }

                if (options.recordAudio && stream) {
                    session.audioRecorder = createAudioRecorder(stream);
                    session.audioRecorder.start();
                }

                if (typeof options.onListening === 'function') {
                    options.onListening();
                }

                session.listenHandle = listen({
                    lang: options.lang || 'en-US',
                    maxSeconds: options.maxSeconds || 10,
                    target: options.target || '',
                    onResult: function (transcript, result) {
                        stopAudioRecorder().then(function (audio) {
                            if (typeof options.onFeedback === 'function') {
                                options.onFeedback(attachAudioToResult(result, audio));
                            }
                            markResultDelivered();
                        });
                    },
                    onError: function (error) {
                        stopAudioRecorder().then(function (audio) {
                            if (typeof options.onError === 'function') {
                                var payload = Object.assign({}, error);
                                payload.audioBlob = audio.audioBlob;
                                payload.durationMs = audio.durationMs;
                                options.onError(payload);
                            }
                            markResultDelivered();
                        });
                    },
                    onEnd: function () {
                        markRecognitionEnded();
                    },
                });
            })
            .catch(function (error) {
                if (session.aborted) {
                    return;
                }

                abortAudioRecorder();
                releaseMicrophoneAccess();

                if (typeof options.onError === 'function') {
                    options.onError(error && error.code ? error : {
                        code: 'mic-unavailable',
                        message: (error && error.message) || 'Could not access the microphone.',
                    });
                }

                finishSession();
            });

        return {
            abort: function () {
                session.aborted = true;
                abortAudioRecorder();
                releaseMicrophoneAccess();
                if (session.listenHandle) {
                    session.listenHandle.abort();
                }
                session.resultDelivered = true;
                session.recognitionEnded = true;
                finishSession();
            },
        };
    }

    global.TalmaSpeech = {
        isSupported: function () {
            return !!getRecognitionConstructor() && hasGetUserMedia();
        },
        requestMicrophoneAccess: requestMicrophoneAccess,
        releaseMicrophoneAccess: releaseMicrophoneAccess,
        normalizeText: normalizeText,
        tokenize: tokenize,
        scoreTranscript: scoreTranscript,
        listen: listen,
        checkPronunciation: checkPronunciation,
        renderFeedback: renderFeedback,
    };
})(typeof window !== 'undefined' ? window : globalThis);

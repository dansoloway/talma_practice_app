/**
 * Site-wide audio playback: play/pause toggle and exclusive playback.
 */
(function () {
    'use strict';

    function getUrl(button) {
        return button.dataset.audioUrl || button.dataset.audio || '';
    }

    function normalizeUrl(url) {
        if (!url) {
            return '';
        }

        try {
            return new URL(url, window.location.origin).href;
        } catch {
            return url;
        }
    }

    function findIcon(button) {
        return button.querySelector('.talma-audio-icon, i.fas, i.far');
    }

    function setPlayingState(button) {
        if (!button) {
            return;
        }

        const playIcon = button.querySelector('.play-icon, .talma-audio-play');
        const pauseIcon = button.querySelector('.pause-icon, .talma-audio-pause');

        if (playIcon && pauseIcon) {
            playIcon.style.display = 'none';
            pauseIcon.style.display = 'inline';
        } else {
            const icon = findIcon(button);
            if (icon) {
                icon.classList.remove('fa-play', 'fa-volume-up', 'fa-spinner', 'fa-spin');
                icon.classList.add('fa-pause');
            }
        }

        button.classList.add('talma-audio-playing');
        button.disabled = false;
    }

    function resetButton(button) {
        if (!button) {
            return;
        }

        const playIcon = button.querySelector('.play-icon, .talma-audio-play');
        const pauseIcon = button.querySelector('.pause-icon, .talma-audio-pause');

        if (playIcon && pauseIcon) {
            playIcon.style.display = 'inline';
            pauseIcon.style.display = 'none';
        } else {
            const icon = findIcon(button);
            if (icon) {
                icon.classList.remove('fa-pause', 'fa-spinner', 'fa-spin');
                const restoreVolume = button.dataset.talmaAudioIcon === 'volume-up';
                icon.classList.remove('fa-play');
                icon.classList.add(restoreVolume ? 'fa-volume-up' : 'fa-play');
            }
        }

        button.classList.remove('talma-audio-playing');
        button.disabled = false;
    }

    function resolvePlaybackRate(options) {
        const rate = options && typeof options.playbackRate === 'number'
            ? options.playbackRate
            : 1;

        return Math.min(4, Math.max(0.25, rate));
    }

    function ratesMatch(a, b) {
        return Math.abs(a - b) < 0.001;
    }

    const TalmaAudio = {
        audio: new Audio(),
        currentButton: null,
        currentUrl: null,
        currentPlaybackRate: 1,
        _onPlayingRateFix: null,

        resetButton(button) {
            resetButton(button);
        },

        setPlayingState(button) {
            setPlayingState(button);
        },

        _clearPlayingRateFix() {
            if (this._onPlayingRateFix) {
                this.audio.removeEventListener('playing', this._onPlayingRateFix);
                this._onPlayingRateFix = null;
            }
        },

        _applyPlaybackRate(rate) {
            this.audio.playbackRate = rate;
            this.audio.defaultPlaybackRate = rate;

            // Keep natural pitch while slowing/speeding (Chrome, Safari, Firefox).
            try {
                this.audio.preservesPitch = true;
            } catch (_) {
                // Older browsers may not support preservesPitch.
            }
            try {
                this.audio.mozPreservesPitch = true;
            } catch (_) {
                // Firefox legacy.
            }
            try {
                this.audio.webkitPreservesPitch = true;
            } catch (_) {
                // Safari legacy.
            }
        },

        _schedulePlayingRateFix(rate) {
            this._clearPlayingRateFix();

            const onPlaying = () => {
                this._applyPlaybackRate(rate);
                this.audio.removeEventListener('playing', onPlaying);
                if (this._onPlayingRateFix === onPlaying) {
                    this._onPlayingRateFix = null;
                }
            };

            this._onPlayingRateFix = onPlaying;
            this.audio.addEventListener('playing', onPlaying);
        },

        stop() {
            this._clearPlayingRateFix();
            this.audio.pause();
            this.audio.currentTime = 0;
            this._applyPlaybackRate(1);

            if (this.currentButton) {
                resetButton(this.currentButton);
            }

            this.currentButton = null;
            this.currentUrl = null;
            this.currentPlaybackRate = 1;
        },

        play(url, button, options) {
            if (!url) {
                return;
            }

            const normalizedUrl = normalizeUrl(url);
            const playbackRate = resolvePlaybackRate(options);

            if (this.currentButton && this.currentButton !== button) {
                resetButton(this.currentButton);
            }

            this.currentUrl = normalizedUrl;
            this.currentButton = button || null;
            this.currentPlaybackRate = playbackRate;

            // Pause first so rate changes apply reliably across browsers (esp. WebKit).
            if (!this.audio.paused) {
                this.audio.pause();
            }

            if (normalizeUrl(this.audio.src) !== normalizedUrl) {
                this.audio.src = url;
            }

            this.audio.currentTime = 0;
            this._applyPlaybackRate(playbackRate);
            this._schedulePlayingRateFix(playbackRate);

            if (button) {
                if (!button.dataset.talmaAudioIcon) {
                    const icon = findIcon(button);
                    if (icon && icon.classList.contains('fa-volume-up')) {
                        button.dataset.talmaAudioIcon = 'volume-up';
                    }
                }
                setPlayingState(button);
            }

            this.audio.play().catch((err) => {
                if (err && err.name === 'AbortError') {
                    return;
                }
                console.error('Error playing audio:', err);
                this.stop();
            });
        },

        toggle(url, button, options) {
            if (!url) {
                return;
            }

            const normalizedUrl = normalizeUrl(url);
            const playbackRate = resolvePlaybackRate(options);
            const isSameSession = this.currentUrl === normalizedUrl
                && this.currentButton === button
                && ratesMatch(this.currentPlaybackRate, playbackRate);

            // Same button + same rate + playing → pause
            if (isSameSession && !this.audio.paused && !this.audio.ended) {
                this.audio.pause();
                resetButton(button);
                return;
            }

            // Same button + same rate + paused mid-track → resume
            if (isSameSession && this.audio.paused && this.audio.currentTime > 0 && !this.audio.ended) {
                this._applyPlaybackRate(this.currentPlaybackRate);
                this._schedulePlayingRateFix(this.currentPlaybackRate);
                setPlayingState(button);
                this.audio.play().catch((err) => {
                    if (err && err.name === 'AbortError') {
                        return;
                    }
                    console.error('Error resuming audio:', err);
                    this.stop();
                });
                return;
            }

            // Different button and/or different rate → restart from beginning at requested rate
            this.play(url, button, { playbackRate: playbackRate });
        },

        handleClick(event) {
        const button = event.target.closest(
            '[data-audio-url], .btn-play-audio, .talma-audio-btn, .play-audio-btn[data-audio], .play-audio-strip[data-audio]'
        );

            if (!button || button.dataset.talmaAudioManual !== undefined) {
                return;
            }

            const url = getUrl(button);
            if (!url) {
                return;
            }

            if (button.classList.contains('play-audio-btn')) {
                event.stopPropagation();
            }

            this.toggle(url, button);
        },
    };

    TalmaAudio.audio.addEventListener('ended', () => {
        TalmaAudio._clearPlayingRateFix();
        if (TalmaAudio.currentButton) {
            resetButton(TalmaAudio.currentButton);
        }
        TalmaAudio.currentButton = null;
        TalmaAudio.currentUrl = null;
        TalmaAudio.currentPlaybackRate = 1;
        TalmaAudio._applyPlaybackRate(1);
    });

    TalmaAudio.audio.addEventListener('error', () => {
        console.error('Audio error:', TalmaAudio.audio.error);
        TalmaAudio.stop();
    });

    document.addEventListener('click', (event) => {
        TalmaAudio.handleClick(event);
    });

    window.TalmaAudio = TalmaAudio;
})();

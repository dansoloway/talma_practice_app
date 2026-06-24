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

    const TalmaAudio = {
        audio: new Audio(),
        currentButton: null,
        currentUrl: null,

        resetButton(button) {
            resetButton(button);
        },

        setPlayingState(button) {
            setPlayingState(button);
        },

        stop() {
            this.audio.pause();
            this.audio.currentTime = 0;

            if (this.currentButton) {
                resetButton(this.currentButton);
            }

            this.currentButton = null;
            this.currentUrl = null;
        },

        play(url, button) {
            if (!url) {
                return;
            }

            const normalizedUrl = normalizeUrl(url);

            if (this.currentButton && this.currentButton !== button) {
                resetButton(this.currentButton);
            }

            this.currentUrl = normalizedUrl;
            this.currentButton = button || null;

            if (normalizeUrl(this.audio.src) !== normalizedUrl) {
                this.audio.src = url;
            }

            this.audio.currentTime = 0;

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
                console.error('Error playing audio:', err);
                this.stop();
            });
        },

        toggle(url, button) {
            if (!url) {
                return;
            }

            const normalizedUrl = normalizeUrl(url);
            const isSame = this.currentUrl === normalizedUrl && this.currentButton === button;

            if (isSame && !this.audio.paused && !this.audio.ended) {
                this.audio.pause();
                resetButton(button);
                return;
            }

            if (isSame && this.audio.paused && this.audio.currentTime > 0 && !this.audio.ended) {
                setPlayingState(button);
                this.audio.play().catch((err) => {
                    console.error('Error resuming audio:', err);
                    this.stop();
                });
                return;
            }

            this.play(url, button);
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
        if (TalmaAudio.currentButton) {
            resetButton(TalmaAudio.currentButton);
        }
        TalmaAudio.currentButton = null;
        TalmaAudio.currentUrl = null;
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

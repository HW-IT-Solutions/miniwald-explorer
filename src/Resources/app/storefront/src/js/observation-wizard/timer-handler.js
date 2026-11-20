/**
 * TimerHandler — countdown modal, 15-minute observation timer, timer bar UI.
 */
export default class TimerHandler {
    constructor(wizard) {
        this.w = wizard;
        this.el = wizard.el;
        this.timerInterval = null;
        this.startedAt = null;
        this.elapsedSeconds = 0;
    }

    init() {
        const el = this.el;

        this.countdownModal = el.querySelector('[data-countdown-modal]');
        this.timerModal = el.querySelector('[data-timer-modal]');
        this.fertigModal = el.querySelector('[data-fertig-modal]');
        this.timerBar = el.querySelector('[data-timer-bar]');
        this.timerElapsed = el.querySelector('[data-timer-elapsed]');
        this.timerTotal = el.querySelector('[data-timer-total]');

        // Timer-beenden modal: keep going
        el.querySelector('[data-close-timer-modal]')?.addEventListener(
            'click',
            () => {
                this.w._toggleModal(this.timerModal, false);
            }
        );

        // Timer-beenden modal: cancel observation → redirect
        // (user already confirmed in this modal — skip the beforeunload guard)
        el.querySelector('[data-cancel-observation]')?.addEventListener(
            'click',
            () => {
                this.stop();
                this.w._toggleModal(this.timerModal, false);
                this.w.leaveGuardDisabled = true;
                window.location.href = '/';
            }
        );

        // Timer-beenden modal: enter results → step 4 (review)
        el.querySelector('[data-finish-timer]')?.addEventListener(
            'click',
            () => {
                this.stop();
                this.w._toggleModal(this.timerModal, false);
                this.w._goToStep(4);
            }
        );

        // Stop button on timer bar
        el.querySelector('[data-open-timer-modal]')?.addEventListener(
            'click',
            () => {
                this.w._toggleModal(this.timerModal, true);
            }
        );

        // Fertig modal: Weiter → step 4 (review)
        el.querySelector('[data-fertig-weiter]')?.addEventListener(
            'click',
            () => {
                this.w._toggleModal(this.fertigModal, false);
                this.w._goToStep(4);
            }
        );

        // Background tabs throttle setInterval heavily; when the tab becomes
        // visible again, sync the display (and fire the fertig modal if the
        // time ran out meanwhile) immediately instead of waiting for a tick.
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && this.timerInterval) {
                this._tick();
            }
        });

        this._updateUI();
    }

    /**
     * Show countdown (3→2→1) then begin the timer.
     * Only called for non-ground observation types.
     */
    startWithCountdown() {
        const modal = this.countdownModal;
        if (!modal) {
            this._begin();
            return;
        }

        const screens = [
            ...modal.querySelectorAll('[data-countdown-screen]'),
        ].sort(
            (a, b) =>
                parseInt(b.dataset.countdownScreen) -
                parseInt(a.dataset.countdownScreen)
        );

        screens.forEach((s) => {
            s.hidden = true;
        });
        modal.hidden = false;

        let index = 0;
        const showNext = () => {
            if (index > 0) screens[index - 1].hidden = true;
            if (index < screens.length) {
                screens[index].hidden = false;
                index++;
                setTimeout(showNext, this.w.options.countdownStepMs);
            } else {
                modal.hidden = true;
                this._begin();
            }
        };
        showNext();
    }

    stop() {
        if (!this.timerInterval) return;
        window.clearInterval(this.timerInterval);
        this.timerInterval = null;

        const step3 = this.el.querySelector('[data-step="3"]');
        if (step3) step3.classList.remove('is-timing');
    }

    // ─── private ─────────────────────────────────────────────────────────────

    _begin() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        const step3 = this.el.querySelector('[data-step="3"]');
        if (step3) step3.classList.add('is-timing');

        if (this.timerInterval) return;
        // Elapsed time is derived from a wall-clock start timestamp instead of
        // counting ticks: inactive tabs throttle setInterval, which used to
        // freeze the timer while the tab was in the background.
        this.startedAt = Date.now();
        this.elapsedSeconds = 0;
        this._updateUI();

        this.timerInterval = window.setInterval(() => this._tick(), 1000);
    }

    _tick() {
        const total = this.w.options.totalSeconds;
        this.elapsedSeconds = Math.min(
            total,
            Math.floor((Date.now() - this.startedAt) / 1000)
        );
        this._updateUI();
        if (this.elapsedSeconds >= total) {
            this.stop();
            this.w._toggleModal(this.fertigModal, true);
        }
    }

    _updateUI() {
        if (this.timerTotal) {
            this.timerTotal.textContent = this._format(
                this.w.options.totalSeconds
            );
        }
        if (this.timerElapsed) {
            this.timerElapsed.textContent = this._format(this.elapsedSeconds);
        }
        if (this.timerBar) {
            const progress = Math.min(
                100,
                (this.elapsedSeconds / this.w.options.totalSeconds) * 100
            );
            this.timerBar.style.setProperty('--timer-progress', `${progress}%`);
        }
    }

    _format(totalSeconds) {
        const m = Math.floor(totalSeconds / 60);
        const s = totalSeconds % 60;
        return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    }
}

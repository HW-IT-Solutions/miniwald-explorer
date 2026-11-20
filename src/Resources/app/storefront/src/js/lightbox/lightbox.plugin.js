const Plugin = window.PluginBaseClass;

/**
 * LightboxPlugin
 *
 * Mounted on [data-lightbox] containers (the photo card on the observation
 * detail page).  Collects all [data-lightbox-trigger] buttons inside the
 * container and shows a full-screen overlay when one is clicked.
 *
 * Features:
 *  - Prev / Next navigation (with wrap-around)
 *  - Keyboard: Escape → close, ArrowLeft / ArrowRight → navigate
 *  - Touch swipe (threshold 50 px)
 *  - Backdrop click → close
 *  - Body scroll lock while open
 */
export default class LightboxPlugin extends Plugin {
    init() {
        // ── Collect triggers ──────────────────────────────────────────────
        this._triggers = [
            ...this.el.querySelectorAll('[data-lightbox-trigger]'),
        ];
        this._total = this._triggers.length;

        if (this._total === 0) {
            return;
        }

        // ── DOM refs ──────────────────────────────────────────────────────
        this._overlay = this.el.querySelector('[data-lightbox-overlay]');
        this._image = this.el.querySelector('[data-lightbox-image]');
        this._counter = this.el.querySelector('[data-lightbox-counter]');
        this._prevBtn = this.el.querySelector('[data-lightbox-prev]');
        this._nextBtn = this.el.querySelector('[data-lightbox-next]');

        if (!this._overlay || !this._image) {
            return;
        }

        // ── State ─────────────────────────────────────────────────────────
        this._currentIndex = 0;
        this._touchStartX = null;

        // ── Event bindings ────────────────────────────────────────────────
        this._triggers.forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const idx = parseInt(btn.dataset.lightboxIndex ?? '0', 10);
                this._open(idx);
            });
        });

        // Close buttons / backdrop (all share data-lightbox-close)
        this.el
            .querySelectorAll('[data-lightbox-close]')
            .forEach((el) => el.addEventListener('click', () => this._close()));

        if (this._prevBtn) {
            this._prevBtn.addEventListener('click', () => this._prev());
        }
        if (this._nextBtn) {
            this._nextBtn.addEventListener('click', () => this._next());
        }

        // Keyboard
        this._onKeyDown = this._handleKeyDown.bind(this);

        // Touch swipe
        this._overlay.addEventListener(
            'touchstart',
            (e) => {
                this._touchStartX = e.changedTouches[0].clientX;
            },
            { passive: true }
        );

        this._overlay.addEventListener(
            'touchend',
            (e) => {
                if (this._touchStartX === null) return;
                const delta = e.changedTouches[0].clientX - this._touchStartX;
                this._touchStartX = null;
                if (Math.abs(delta) < 50) return;
                if (delta < 0) {
                    this._next();
                } else {
                    this._prev();
                }
            },
            { passive: true }
        );
    }

    // ── Open ──────────────────────────────────────────────────────────────

    _open(index) {
        this._currentIndex = index;
        this._render();
        this._overlay.removeAttribute('hidden');
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', this._onKeyDown);
        // Focus the overlay for keyboard accessibility
        this._overlay.focus?.();
    }

    // ── Close ─────────────────────────────────────────────────────────────

    _close() {
        this._overlay.setAttribute('hidden', '');
        document.body.style.overflow = '';
        document.removeEventListener('keydown', this._onKeyDown);
    }

    // ── Navigation ────────────────────────────────────────────────────────

    _prev() {
        this._currentIndex =
            (this._currentIndex - 1 + this._total) % this._total;
        this._render();
    }

    _next() {
        this._currentIndex = (this._currentIndex + 1) % this._total;
        this._render();
    }

    // ── Render current image ──────────────────────────────────────────────

    _render() {
        const trigger = this._triggers[this._currentIndex];
        if (!trigger) return;

        const src = trigger.dataset.lightboxSrc ?? '';
        const alt = trigger.dataset.lightboxAlt ?? '';

        // Fade: briefly hide image while src changes
        this._image.classList.add('is-loading');
        this._image.onload = () => this._image.classList.remove('is-loading');
        this._image.onerror = () => this._image.classList.remove('is-loading');
        this._image.src = src;
        this._image.alt = alt;

        // Counter
        if (this._counter) {
            this._counter.textContent = `${this._currentIndex + 1} / ${this._total}`;
        }

        // Prev / next visibility (hide when only 1 photo)
        const showNav = this._total > 1;
        if (this._prevBtn) {
            this._prevBtn.hidden = !showNav;
        }
        if (this._nextBtn) {
            this._nextBtn.hidden = !showNav;
        }
    }

    // ── Keyboard handler ──────────────────────────────────────────────────

    _handleKeyDown(e) {
        switch (e.key) {
            case 'Escape':
                this._close();
                break;
            case 'ArrowLeft':
                this._prev();
                break;
            case 'ArrowRight':
                this._next();
                break;
        }
    }
}

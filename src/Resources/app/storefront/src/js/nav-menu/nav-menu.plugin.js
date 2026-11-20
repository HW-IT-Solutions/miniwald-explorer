const Plugin = window.PluginBaseClass;

export default class NavMenuPlugin extends Plugin {
    init() {
        this._toggle = this.el.querySelector('[data-nav-toggle]');
        this._overlay = this.el.querySelector('[data-nav-overlay]');

        if (this._toggle) {
            this._toggle.addEventListener('click', this._onToggle.bind(this));
        }

        if (this._overlay) {
            this._overlay.addEventListener(
                'animationend',
                this._onAnimationEnd.bind(this)
            );
        }
    }

    _onToggle() {
        if (this.el.classList.contains('is-open')) {
            this._closeWithAnimation();
        } else {
            this._open();
        }
    }

    _open() {
        this.el.classList.remove('is-closing');
        this.el.classList.add('is-open');
        this._toggle.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }

    _closeWithAnimation() {
        this.el.classList.add('is-closing');
        this._toggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
        // Fallback: if animationend never fires, force-close after 300ms
        this._fallbackTimer = setTimeout(() => this._finishClose(), 300);
    }

    _onAnimationEnd() {
        if (this.el.classList.contains('is-closing')) {
            clearTimeout(this._fallbackTimer);
            this._finishClose();
        }
    }

    _finishClose() {
        this.el.classList.remove('is-open', 'is-closing');
    }

    close() {
        this._closeWithAnimation();
    }
}

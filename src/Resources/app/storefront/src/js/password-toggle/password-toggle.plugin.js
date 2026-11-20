const Plugin = window.PluginBaseClass;

export default class PasswordTogglePlugin extends Plugin {
    static options = {
        targetSelector: 'data-password-toggle-target',
        visibleClass: 'is-visible',
    };

    init() {
        this._target = document.querySelector(
            this.el.getAttribute(this.options.targetSelector)
        );

        if (!this._target) {
            return;
        }

        this._showLabel =
            this.el.getAttribute('data-password-toggle-show-label') ||
            this.el.getAttribute('aria-label');
        this._hideLabel =
            this.el.getAttribute('data-password-toggle-hide-label') ||
            this._showLabel;

        this._onClick = this._onClick.bind(this);
        this.el.addEventListener('click', this._onClick);
    }

    _onClick() {
        const isVisible = this._target.type === 'text';
        const willBeVisible = !isVisible;

        this._target.type = willBeVisible ? 'text' : 'password';
        this.el.classList.toggle(this.options.visibleClass, willBeVisible);
        this.el.setAttribute('aria-pressed', String(willBeVisible));
        this.el.setAttribute(
            'aria-label',
            willBeVisible ? this._hideLabel : this._showLabel
        );
    }
}

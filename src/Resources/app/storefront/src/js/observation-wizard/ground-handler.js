/**
 * GroundHandler — manages the 3-location sub-step flow for abiotic ground measurements.
 *
 * Sub-steps:
 *   0 → a. Miniwald  (wood_center)  — all 6 fields
 *   1 → b. Wiese     (wood_edge)    — all 6 fields
 *   2 → c. Bürgersteig (street_curb) — air temp, air humidity, light only
 *
 * Data attributes on inputs must stay:
 *   data-ground-field="wood_center|wood_edge|street_curb"
 *   data-field-name="airTemperature|humidity|groundTemperature|moisture|phValue|lightConditions"
 *   data-field-type="radio"  (only on radio buttons)
 */
export default class GroundHandler {
    constructor(wizard) {
        this.w = wizard;
        this.el = wizard.el;
        this.subStep = 0;
        this._subPanels = [];
        this._progressEl = null;
        this._nextBtn = null;
    }

    init() {
        this._subPanels = [
            ...this.el.querySelectorAll('[data-ground-sub]'),
        ].sort(
            (a, b) =>
                parseInt(a.dataset.groundSub) - parseInt(b.dataset.groundSub)
        );
        this._nextBtn = this.el.querySelector('[data-ground-next]');
        this._prevBtn = this.el.querySelector('[data-ground-prev]');

        this._nextBtn?.addEventListener('click', () => this._next());
        this._prevBtn?.addEventListener('click', () => this._prev());
    }

    /** Called when entering step 3 for ground type. Resets to sub-step 0. */
    show() {
        this.subStep = 0;
        this._showSubStep(0);
    }

    /** Called when returning from step 5 back to step 3 — land on last sub-step. */
    showLast() {
        const last = this._subPanels.length - 1;
        this.subStep = last;
        this._showSubStep(last);
    }

    /** Clear all inputs and radio buttons. */
    reset() {
        this.subStep = 0;
        this.el
            .querySelectorAll('[data-ground-matrix] input[data-ground-field]')
            .forEach((input) => {
                if (input.type === 'radio') {
                    input.checked = false;
                } else {
                    input.value = '';
                }
            });
    }

    /**
     * Collect ground measurement data from all sub-step form fields.
     * Returns an array of objects (one per location type that has any data).
     * @returns {Array<{locationType: string, [field]: number|string}>}
     */
    collectMeasurements() {
        const locationTypes = ['wood_center', 'wood_edge', 'street_curb'];
        const measurements = [];

        for (const locationType of locationTypes) {
            const fields = this.el.querySelectorAll(
                `[data-ground-field="${locationType}"]`
            );
            if (!fields.length) continue;

            const data = { locationType };
            let hasData = false;

            fields.forEach((field) => {
                const fieldName = field.dataset.fieldName;
                const fieldType = field.dataset.fieldType;

                if (fieldType === 'radio') {
                    if (field.checked) {
                        hasData = true;
                        data[fieldName] = field.value;
                    }
                    return;
                }

                const value = field.value.trim();
                if (!value) return;
                hasData = true;
                data[fieldName] = parseFloat(value);
            });

            if (hasData) measurements.push(data);
        }

        return measurements;
    }

    // ─── private ─────────────────────────────────────────────────────────────

    _showSubStep(index) {
        this._subPanels.forEach((panel, i) => {
            panel.hidden = i !== index;
        });

        const total = this._subPanels.length;

        if (this._nextBtn) {
            // Last sub-step: label changes to "Fertig"
            const isLast = index >= total - 1;
            const textNode = [...this._nextBtn.childNodes].find(
                (n) => n.nodeType === Node.TEXT_NODE
            );
            if (textNode) {
                textNode.textContent =
                    (isLast ? this.w.strings.done : this.w.strings.next) + ' ';
            }
        }

        // Hide back button on first sub-step (header cancel handles that navigation)
        if (this._prevBtn) {
            this._prevBtn.hidden = index === 0;
        }
    }

    _next() {
        if (this.subStep < this._subPanels.length - 1) {
            this.subStep++;
            this._showSubStep(this.subStep);
            this.w.el.scrollTo?.({ top: 0, behavior: 'smooth' });
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            this.w._goToStep(5);
        }
    }

    _prev() {
        if (this.subStep > 0) {
            this.subStep--;
            this._showSubStep(this.subStep);
            this.w.el.scrollTo?.({ top: 0, behavior: 'smooth' });
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            // On first sub-step: back goes to step 1 (type selection)
            this.w._goToStep(1);
        }
    }
}

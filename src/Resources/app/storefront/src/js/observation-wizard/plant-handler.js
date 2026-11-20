/**
 * PlantHandler — manages the plant observation panel.
 *
 * Two sub-steps inside the plant panel:
 *   Sub-step 0: Observation form (miniwald, date, weather, wind)
 *               → miniwald select enables "Weiter" + triggers plant fetch
 *               → "Weiter" [data-plant-next] advances to sub-step 1
 *   Sub-step 1: Plant grid + "add custom plant" + save button
 *               → [data-plant-prev] goes back to sub-step 0
 *               → [data-plant-save-btn] triggers save
 *
 * Back navigation from wizard (_goBack):
 *   - If on sub-step 1 → go to sub-step 0
 *   - If on sub-step 0 → go to wizard step 1 (handled by observation-wizard.plugin.js)
 */
export default class PlantHandler {
    constructor(wizard) {
        this.w = wizard;
        this.el = wizard.el;

        /** @type {HTMLElement|null} */
        this._panel = null;
        /** @type {HTMLElement[]} */
        this._subSteps = [];
        /** @type {number} */
        this._currentSubStep = 0;

        /** @type {HTMLElement|null} */
        this._grid = null;
        /** @type {HTMLSelectElement|null} */
        this._miniwaldSelect = null;
        /** @type {HTMLButtonElement|null} */
        this._nextBtn = null;
        /** @type {HTMLButtonElement|null} */
        this._saveBtnPlant = null;

        this._cardCounter = 0;
    }

    init() {
        this._panel = this.el.querySelector('[data-plant-panel]');
        this._grid = this.el.querySelector('[data-plant-grid]');
        this._miniwaldSelect = this.el.querySelector(
            '[data-plant-miniwald-select]'
        );
        this._nextBtn = this.el.querySelector('[data-plant-next]');
        this._saveBtnPlant = this.el.querySelector('[data-plant-save-btn]');
        this._subSteps = [
            ...(this._panel?.querySelectorAll('[data-plant-sub]') ?? []),
        ];

        if (!this._panel) return;

        // Miniwald select → fetch plants + enable "Weiter"
        this._miniwaldSelect?.addEventListener('change', (e) => {
            const val = e.target.value;
            if (this._nextBtn) {
                this._nextBtn.disabled = !val;
            }
            if (val === 'other') {
                this._fetchAndRenderPlants(null);
            } else if (val) {
                this._fetchAndRenderPlants(val);
            } else {
                this._clearGrid();
            }
        });

        // "Weiter" → advance to sub-step 1
        this._nextBtn?.addEventListener('click', () => {
            this._showSubStep(1);
        });

        // "Zurück" inside sub-step 1 → back to sub-step 0
        this._panel
            .querySelector('[data-plant-prev]')
            ?.addEventListener('click', () => {
                this._showSubStep(0);
            });

        // Add custom plant button
        this.el
            .querySelector('[data-add-custom-plant]')
            ?.addEventListener('click', () => {
                this._appendPlantCard(null, '', '', true);
            });

        // Save button
        this._saveBtnPlant?.addEventListener('click', () => {
            this.w.saveHandler._save();
        });
    }

    /** Called when entering step 3 for plants type. Always starts at sub-step 0. */
    show() {
        if (!this._panel) return;
        this._panel.hidden = false;
        this._showSubStep(0);

        // Pre-fill date/time to now
        const dateInput = this._panel.querySelector('[data-plant-date-input]');
        const timeInput = this._panel.querySelector('[data-plant-time-input]');
        if (dateInput && !dateInput.value) {
            dateInput.value = new Date().toISOString().slice(0, 10);
        }
        if (timeInput && !timeInput.value) {
            const now = new Date();
            timeInput.value = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
        }
    }

    /** Hide the plant panel (called when switching away from plants type). */
    hide() {
        if (this._panel) this._panel.hidden = true;
    }

    /** Reset all state — called on wizard reset. */
    reset() {
        this._clearGrid();
        this._cardCounter = 0;
        this._currentSubStep = 0;

        if (this._miniwaldSelect) this._miniwaldSelect.value = '';
        if (this._nextBtn) this._nextBtn.disabled = true;

        const dateInput = this._panel?.querySelector('[data-plant-date-input]');
        const timeInput = this._panel?.querySelector('[data-plant-time-input]');
        if (dateInput) dateInput.value = '';
        if (timeInput) timeInput.value = '';
    }

    /**
     * Returns true if the user is currently on sub-step 1 (plant grid).
     * Used by observation-wizard.plugin.js _goBack() to decide whether to
     * go back within the panel or back to wizard step 1.
     */
    isOnGridSubStep() {
        return this._currentSubStep === 1;
    }

    /** Go back to sub-step 0 from sub-step 1. */
    goBackToForm() {
        this._showSubStep(0);
    }

    /**
     * Collect all plant measurement data from the grid.
     * @returns {Array}
     */
    collectMeasurements() {
        const measurements = [];
        if (!this._grid) return measurements;

        this._grid.querySelectorAll('[data-plant-card]').forEach((card) => {
            const plantId = card.dataset.plantId || null;
            const nameInput = card.querySelector('[data-plant-name]');
            const trunkInput = card.querySelector('[data-plant-trunk]');
            const heightInput = card.querySelector('[data-plant-height]');
            const remarkInput = card.querySelector('[data-plant-remark]');

            const customPlantName = nameInput?.value?.trim() || null;
            const trunkVal = trunkInput?.value?.trim();
            const heightVal = heightInput?.value?.trim();
            const remarkVal = remarkInput?.value?.trim() || null;

            // Skip completely empty custom cards
            if (
                !plantId &&
                !customPlantName &&
                !trunkVal &&
                !heightVal &&
                !remarkVal
            ) {
                return;
            }

            measurements.push({
                plantId: plantId,
                customPlantName: plantId ? null : customPlantName,
                trunkCircumferenceCm: trunkVal ? parseFloat(trunkVal) : null,
                heightCm: heightVal ? parseInt(heightVal, 10) : null,
                remarks: remarkVal,
            });
        });

        return measurements;
    }

    /**
     * Read observation form data from the plant panel (sub-step 0).
     * @returns {{miniwaldId, observedAt, latitude, longitude, weather, wind}}
     */
    collectFormData() {
        const panel = this._panel;
        if (!panel) {
            return {
                miniwaldId: null,
                observedAt: new Date().toISOString(),
                latitude: null,
                longitude: null,
                weather: null,
                wind: null,
            };
        }

        const miniwaldSel = panel.querySelector('[data-plant-miniwald-select]');
        const dateInput = panel.querySelector('[data-plant-date-input]');
        const timeInput = panel.querySelector('[data-plant-time-input]');
        const weatherSel = panel.querySelector('[data-plant-weather-select]');
        const windSel = panel.querySelector('[data-plant-wind-select]');

        const _mwVal = miniwaldSel?.value;
        const miniwaldId = _mwVal && _mwVal !== 'other' ? _mwVal : null;
        const dateVal = dateInput?.value ?? '';
        const timeVal = timeInput?.value ?? '';
        const observedAt =
            dateVal && timeVal
                ? new Date(`${dateVal}T${timeVal}`).toISOString()
                : new Date().toISOString();

        const opt = miniwaldSel?.selectedOptions?.[0];
        const lat = opt?.dataset.lat ? parseFloat(opt.dataset.lat) : null;
        const lng = opt?.dataset.lng ? parseFloat(opt.dataset.lng) : null;

        return {
            miniwaldId,
            observedAt,
            latitude: lat !== null && !isNaN(lat) ? lat : null,
            longitude: lng !== null && !isNaN(lng) ? lng : null,
            weather: weatherSel?.value || null,
            wind: windSel?.value || null,
        };
    }

    // ─── private ─────────────────────────────────────────────────────────────

    _showSubStep(index) {
        this._currentSubStep = index;
        this._subSteps.forEach((el, i) => {
            el.hidden = i !== index;
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    async _fetchAndRenderPlants(miniwaldId) {
        this._clearGrid();

        if (this._grid) {
            const loading = document.createElement('p');
            loading.className = 'miniwald-plant__loading';
            loading.dataset.plantLoading = 'true';
            loading.textContent = '…';
            this._grid.appendChild(loading);
        }

        try {
            const res = await fetch('/observation/plants-for-miniwald', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ miniwaldId }),
            });

            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();

            this._clearGrid();

            if (Array.isArray(data.plants) && data.plants.length > 0) {
                data.plants.forEach((plant) => {
                    this._appendPlantCard(
                        plant.id,
                        plant.name,
                        plant.imageUrl,
                        false
                    );
                });
            }
        } catch (err) {
            this._clearGrid();
            console.warn('PlantHandler: failed to fetch plants', err);
        }
    }

    _clearGrid() {
        if (this._grid) this._grid.innerHTML = '';
        this._cardCounter = 0;
    }

    /**
     * Append a plant measurement card to the grid.
     * @param {string|null} plantId
     * @param {string}      name
     * @param {string|null} imageUrl
     * @param {boolean}     isCustom
     */
    _appendPlantCard(plantId, name, imageUrl, isCustom) {
        if (!this._grid) return;

        this._cardCounter++;
        const num = this._cardCounter;

        const card = document.createElement('div');
        card.className = 'miniwald-plant-card';
        card.dataset.plantCard = 'true';
        if (plantId) card.dataset.plantId = plantId;

        // ── Header ──────────────────────────────────────────────────────────
        const header = document.createElement('div');
        header.className = 'miniwald-plant-card__header';

        const numLabel = document.createElement('span');
        numLabel.className = 'miniwald-plant-card__number';
        numLabel.textContent = `${this.w.strings.plantCardNumber} ${num}`;
        header.appendChild(numLabel);

        if (isCustom) {
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className =
                'btn btn-transparent miniwald-plant-card__remove';
            removeBtn.setAttribute(
                'aria-label',
                this.w.strings.plantCardRemove
            );
            removeBtn.innerHTML = this.w.icons?.close ?? '✕';
            removeBtn.addEventListener('click', () => {
                card.remove();
                this._renumberCards();
            });
            header.appendChild(removeBtn);
        }

        card.appendChild(header);

        // ── Image (optional) ────────────────────────────────────────────────
        if (imageUrl) {
            const imgWrap = document.createElement('div');
            imgWrap.className = 'miniwald-plant-card__image';
            const img = document.createElement('img');
            img.src = imageUrl;
            img.alt = name;
            img.loading = 'lazy';
            imgWrap.appendChild(img);
            card.appendChild(imgWrap);
        } else {
            const iconSvg = this.w.icons?.['species-plants'];
            if (iconSvg) {
                const imgWrap = document.createElement('div');
                imgWrap.className = 'miniwald-plant-card__image';
                imgWrap.innerHTML = iconSvg;
                card.appendChild(imgWrap);
            }
        }

        // ── Fields ──────────────────────────────────────────────────────────
        const fields = document.createElement('div');
        fields.className = 'miniwald-plant-card__fields';

        fields.appendChild(
            this._buildField(
                this.w.strings.plantCardSpeciesName,
                'text',
                'data-plant-name',
                name,
                null,
                null,
                null
            )
        );
        fields.appendChild(
            this._buildField(
                this.w.strings.plantCardTrunk,
                'number',
                'data-plant-trunk',
                '',
                '0.1',
                null,
                null
            )
        );
        fields.appendChild(
            this._buildField(
                this.w.strings.plantCardHeight,
                'number',
                'data-plant-height',
                '',
                '1',
                '0',
                null
            )
        );

        // Remark textarea
        const remarkWrap = document.createElement('label');
        remarkWrap.className = 'miniwald-plant-card__field';
        const remarkLabel = document.createElement('span');
        remarkLabel.textContent = this.w.strings.plantCardRemark;
        const remarkTextarea = document.createElement('textarea');
        remarkTextarea.className = 'miniwald-plant-card__remark';
        remarkTextarea.dataset.plantRemark = 'true';
        remarkTextarea.rows = 2;
        remarkWrap.appendChild(remarkLabel);
        remarkWrap.appendChild(remarkTextarea);
        fields.appendChild(remarkWrap);

        card.appendChild(fields);
        this._grid.appendChild(card);
    }

    _buildField(labelText, type, dataAttr, value, step, min, max) {
        const wrap = document.createElement('label');
        wrap.className = 'miniwald-plant-card__field';

        const label = document.createElement('span');
        label.textContent = labelText;

        const input = document.createElement('input');
        input.type = type;
        input.setAttribute(dataAttr, 'true');
        if (value) input.value = value;
        if (step) input.step = step;
        if (min !== null && min !== undefined) input.min = String(min);
        if (max !== null && max !== undefined) input.max = String(max);
        if (type === 'number') input.inputMode = 'decimal';

        wrap.appendChild(label);
        wrap.appendChild(input);
        return wrap;
    }

    _renumberCards() {
        if (!this._grid) return;
        let i = 1;
        this._grid.querySelectorAll('[data-plant-card]').forEach((card) => {
            const numEl = card.querySelector('.miniwald-plant-card__number');
            if (numEl)
                numEl.textContent = `${this.w.strings.plantCardNumber} ${i}`;
            i++;
        });
        this._cardCounter = i - 1;
    }
}

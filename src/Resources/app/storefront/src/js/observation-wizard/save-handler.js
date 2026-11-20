/**
 * SaveHandler — collects form data, POSTs to /observation/save,
 * then uploads queued photos before allowing completion.
 *
 * UX features:
 *  - isSaving flag prevents double-submit
 *  - 30 s AbortController timeout on the observation POST
 *  - Inline error message (timeout vs generic) shown below the save button
 *  - Photo upload failures shown as a warning inside the finish modal
 */
export default class SaveHandler {
    constructor(wizard) {
        this.w = wizard;
        this.el = wizard.el;
        this._isSaving = false;
        this.photos = [];
        this.uploading = false;
        this.cancelled = false;
        this.activeUpload = null;
    }

    init() {
        this.el
            .querySelector('[data-save-observation]')
            ?.addEventListener('click', () => {
                this._save();
            });

        this.el
            .querySelector('[data-finish-redirect]')
            ?.addEventListener('click', () => {
                if (this.hasPendingPhotos()) return;
                this.w.leaveGuardDisabled = true;
                window.location.href = '/collection/observations';
            });

        this.el.querySelector('[data-photo-retry]')?.addEventListener('click', () => this._uploadPending());
        this.el.querySelector('[data-photo-cancel]')?.addEventListener('click', () => this.requestCancel());
        this.el.querySelector('[data-photo-cancel-stay]')?.addEventListener('click', () => {
            this._closeCancelConfirmation();
        });
        this.el.querySelector('[data-photo-cancel-confirm]')?.addEventListener('click', () => this._confirmCancel());
        this.el.querySelector('[data-photo-cancel-confirmation]')?.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') this._closeCancelConfirmation();
            if (event.key !== 'Tab') return;
            const stay = this.el.querySelector('[data-photo-cancel-stay]');
            const confirm = this.el.querySelector('[data-photo-cancel-confirm]');
            if (event.shiftKey && event.target === stay) {
                event.preventDefault();
                confirm.focus();
            } else if (!event.shiftKey && event.target === confirm) {
                event.preventDefault();
                stay.focus();
            }
        });

        // Note: [data-plant-save-btn] is wired by plant-handler.js → saveHandler._save()
        // Do NOT add a second listener here.
    }

    // ─── private ─────────────────────────────────────────────────────────────

    async _save() {
        if (this._isSaving) return;

        const saveBtn = this._activeSaveBtn();
        const errorEl = this._activeErrorEl();

        this._setSaving(true, saveBtn, errorEl);

        const isGround = this.w.selectedType === 'ground';
        const isPlants = this.w.selectedType === 'plants';

        let miniwaldId, observedAt, latitude, longitude, weather, wind;

        if (isPlants) {
            // Plants: read form data from the plant panel
            const formData = this.w.plantHandler.collectFormData();
            miniwaldId = formData.miniwaldId;
            observedAt = formData.observedAt;
            latitude = formData.latitude;
            longitude = formData.longitude;
            weather = formData.weather;
            wind = formData.wind;
        } else {
            const dateInput =
                this.el.querySelector('[data-date-input]')?.value ?? '';
            const timeInput =
                this.el.querySelector('[data-time-input]')?.value ?? '';
            observedAt =
                dateInput && timeInput
                    ? new Date(`${dateInput}T${timeInput}`).toISOString()
                    : new Date().toISOString();

            const coords = this._resolveCoordinates();
            latitude = coords.latitude;
            longitude = coords.longitude;

            const _mwVal = this.el.querySelector(
                '[data-miniwald-select]'
            )?.value;
            miniwaldId = _mwVal && _mwVal !== 'other' ? _mwVal : null;
            weather =
                this.el.querySelector('[data-weather-select]')?.value || null;
            wind = this.el.querySelector('[data-wind-select]')?.value || null;
        }

        const payload = {
            miniwaldId,
            observedAt,
            latitude,
            longitude,
            weather,
            wind,
            type: isGround ? 'ground' : isPlants ? 'plant' : 'animal',
            speciesCategoryId:
                isGround || isPlants ? null : this.w.selectedCategoryId,
            items:
                isGround || isPlants
                    ? []
                    : this.w.speciesHandler.collectItems(),
            groundMeasurements: isGround
                ? this.w.groundHandler.collectMeasurements()
                : [],
            plantMeasurements: isPlants
                ? this.w.plantHandler.collectMeasurements()
                : [],
        };

        // 30 s timeout via AbortController
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000);

        try {
            const res = await fetch('/observation/save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
                signal: controller.signal,
            });

            clearTimeout(timeoutId);

            if (res.ok) {
                const data = await res.json();

                // Ground measurements are not visible in the collection, so the
                // finish modal shows a different confirmation text for them
                const defaultText = this.el.querySelector(
                    '[data-finish-text-default]'
                );
                const groundText = this.el.querySelector(
                    '[data-finish-text-ground]'
                );
                if (defaultText && groundText) {
                    defaultText.hidden = isGround;
                    groundText.hidden = !isGround;
                }

                this.photos = [];
                if (Array.isArray(data.items)) {
                    for (const { speciesId, subspeciesId, clientId, itemId } of data.items) {
                        // photosBySpecies is keyed by the card key, which is the
                        // subspecies id for subspecies cards
                        const photoKey = clientId ?? subspeciesId ?? speciesId;
                        const files =
                            this.w.speciesHandler.photosBySpecies.get(photoKey);
                        if (files?.length) {
                            for (const file of files) {
                                const bytes = crypto.getRandomValues(new Uint8Array(16));
                                const photoId = Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join('');
                                this.photos.push({ itemId, file, photoId, done: false });
                            }
                        }
                    }
                }
                this.w._toggleModal(this.w.finishModal, true);
                if (this.photos.length) {
                    this._renderUploadState();
                    await this._uploadPending();
                } else {
                    this._finishUpload();
                }
            } else {
                clearTimeout(timeoutId);
                this._setSaving(false, saveBtn, errorEl);
                this._showError(errorEl, false);
            }
        } catch (err) {
            clearTimeout(timeoutId);
            this._setSaving(false, saveBtn, errorEl);
            const isTimeout = err.name === 'AbortError';
            this._showError(errorEl, isTimeout);
        }
    }

    /**
     * Returns the currently visible save button (step 5 or plant panel).
     */
    _activeSaveBtn() {
        const plantBtn = this.el.querySelector('[data-plant-save-btn]');
        if (
            plantBtn &&
            !plantBtn.closest('[hidden]') &&
            this.w.selectedType === 'plants'
        ) {
            return plantBtn;
        }
        return this.el.querySelector('[data-save-observation]');
    }

    /**
     * Returns the error element adjacent to the active save button.
     */
    _activeErrorEl() {
        if (this.w.selectedType === 'plants') {
            return this.el.querySelector('[data-plant-save-error]');
        }
        return this.el.querySelector('[data-save-error]');
    }

    _setSaving(saving, btn, errorEl) {
        this._isSaving = saving;
        if (!btn) return;

        if (saving) {
            btn.classList.add('is-saving');
            btn.disabled = true;
            if (errorEl) errorEl.hidden = true;
        } else {
            btn.classList.remove('is-saving');
            // Re-enable only if the miniwald select has a value (step 5 guard)
            const sel = this.el.querySelector('[data-miniwald-select]');
            const plantSel = this.el.querySelector(
                '[data-plant-miniwald-select]'
            );
            const hasValue = (sel && sel.value) || (plantSel && plantSel.value);
            btn.disabled = !hasValue;
        }
    }

    _showError(errorEl, isTimeout) {
        if (!errorEl) return;
        const msg = isTimeout
            ? (this.w.strings.saveErrorTimeout ??
              'Das Speichern dauert zu lange. Bitte prüfe deine Internetverbindung und versuche es erneut.')
            : (this.w.strings.saveError ??
              'Speichern fehlgeschlagen. Bitte versuche es erneut.');
        errorEl.textContent = msg;
        errorEl.hidden = false;
    }

    hasPendingPhotos() {
        return !this.cancelled && this.photos.some(photo => !photo.done);
    }

    requestCancel() {
        if (!this.hasPendingPhotos()) return;
        this.el.querySelector('[data-photo-cancel-confirmation]').hidden = false;
        this.el.querySelector('[data-photo-cancel-stay]').focus?.();
    }

    _closeCancelConfirmation() {
        this.el.querySelector('[data-photo-cancel-confirmation]').hidden = true;
        const target = this.hasPendingPhotos()
            ? this.el.querySelector('[data-photo-cancel]')
            : this.el.querySelector('[data-finish-redirect]');
        target.focus?.();
    }

    _confirmCancel() {
        if (!this.hasPendingPhotos()) return;
        this.cancelled = true;
        this.activeUpload?.abort();
        this.el.querySelector('[data-photo-cancel-confirmation]').hidden = true;
        this.el.querySelector('[data-photo-uploading]').hidden = true;
        this.el.querySelector('[data-photo-upload-warning]').hidden = true;
        this.el.querySelector('[data-photo-upload-actions]').hidden = true;
        this.el.querySelector('[data-photo-cancelled]').hidden = false;
        this.el.querySelector('[data-finish-redirect]').hidden = false;
        this.w.leaveGuardDisabled = true;
        this.el.querySelector('[data-finish-redirect]').focus?.();
    }

    _renderUploadState() {
        const done = this.photos.filter(photo => photo.done).length;
        const total = this.photos.length;
        const progress = this.el.querySelector('[data-photo-upload-progress]');
        progress.textContent = this.w.strings.photoUploadProgress
            .replace('%done%', String(done))
            .replace('%total%', String(total));
        this.el.querySelector('[data-photo-upload-bar]').value = done;
        this.el.querySelector('[data-photo-upload-bar]').max = total;
        this.el.querySelector('[data-photo-uploading]').hidden = false;
        this.el.querySelector('[data-finish-success]').hidden = true;
        this.el.querySelector('[data-finish-redirect]').hidden = true;
        this.el.querySelector('[data-photo-upload-warning]').hidden = true;
        this.el.querySelector('[data-photo-upload-actions]').hidden = false;
        this.el.querySelector('[data-photo-retry]').hidden = this.uploading;
        this.el.querySelector('[data-photo-cancel]').hidden = !this.uploading;
    }

    async _uploadPending() {
        if (this.uploading || this.cancelled) return;
        this.uploading = true;
        this._renderUploadState();
        for (const photo of this.photos) {
            if (this.cancelled) break;
            if (photo.done) continue;
            photo.done = await this._uploadPhotoWithRetry(photo);
            if (!this.cancelled) this._renderUploadState();
        }
        this.uploading = false;
        if (this.cancelled) return;
        if (this.hasPendingPhotos()) {
            this.el.querySelector('[data-photo-upload-warning]').hidden = false;
            this.el.querySelector('[data-photo-upload-actions]').hidden = false;
            this.el.querySelector('[data-photo-retry]').hidden = false;
            this.el.querySelector('[data-photo-cancel]').hidden = false;
        } else {
            this._finishUpload();
        }
    }

    _finishUpload() {
        const confirmation = this.el.querySelector('[data-photo-cancel-confirmation]');
        const wasConfirming = !confirmation.hidden;
        confirmation.hidden = true;
        this.el.querySelector('[data-photo-uploading]').hidden = true;
        this.el.querySelector('[data-photo-upload-warning]').hidden = true;
        this.el.querySelector('[data-photo-upload-actions]').hidden = true;
        this.el.querySelector('[data-finish-success]').hidden = false;
        this.el.querySelector('[data-finish-redirect]').hidden = false;
        this.w.leaveGuardDisabled = true;
        if (wasConfirming) this.el.querySelector('[data-finish-redirect]').focus?.();
    }

    async _uploadPhotoWithRetry(photo) {
        for (let attempt = 0; attempt < 3 && !this.cancelled; attempt++) {
            const controller = new AbortController();
            this.activeUpload = controller;
            const timeout = setTimeout(() => controller.abort(), 30000);
            let transient = true;
            try {
                const fd = new FormData();
                fd.append('observationItemId', photo.itemId);
                fd.append('photoId', photo.photoId);
                fd.append('file', photo.file, 'photo.jpg');
                const res = await fetch('/observation/upload-photo', {
                    method: 'POST', body: fd, signal: controller.signal,
                });
                const body = await res.json().catch(() => ({}));
                if (res.ok && body.success === true) return true;
                transient = res.status === 408 || res.status === 409 || res.status === 429 || res.status >= 500;
            } catch (error) {
                if (this.cancelled) return false;
                console.warn('Photo upload network error:', error);
            } finally {
                clearTimeout(timeout);
                this.activeUpload = null;
            }
            if (!transient) break;
            if (attempt < 2 && !this.cancelled) await new Promise(resolve => setTimeout(resolve, 1000 * (2 ** attempt)));
        }
        return false;
    }

    _resolveCoordinates() {
        if (this.w.detectedLat !== null && this.w.detectedLng !== null) {
            return {
                latitude: this.w.detectedLat,
                longitude: this.w.detectedLng,
            };
        }

        const sel = this.el.querySelector('[data-miniwald-select]');
        const opt = sel?.selectedOptions?.[0];
        const lat = opt?.dataset.lat ? parseFloat(opt.dataset.lat) : null;
        const lng = opt?.dataset.lng ? parseFloat(opt.dataset.lng) : null;

        if (lat !== null && lng !== null && !isNaN(lat) && !isNaN(lng)) {
            return { latitude: lat, longitude: lng };
        }

        return { latitude: null, longitude: null };
    }

}

/**
 * SpeciesHandler — counters, custom species cards, photo upload + resize,
 * remarks, and step 3 → step 4 sync.
 */
export default class SpeciesHandler {
    constructor(wizard) {
        this.w = wizard;
        this.el = wizard.el;

        /** @type {Map<string, File[]>} speciesId → array of File objects */
        this.photosBySpecies = new Map();
        /** @type {Map<string, string>} speciesId → remark text */
        this.remarksBySpecies = new Map();
        /** @type {Array<{id: string, name: string, subspeciesId: ?string, count: number}>} */
        this.customSpecies = [];
        this.hasCamera = true;
    }

    init() {
        const el = this.el;

        // Counter buttons (delegated — also covers dynamically added cards)
        el.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-counter]');
            if (btn) this._handleCounter(btn);
        });

        // Direct number input on counter value fields (delegated)
        el.addEventListener('input', (e) => {
            const input = e.target.closest('[data-counter-value]');
            if (!input) return;
            const card = input.closest('.miniwald-observation-species-card');
            if (!card) return;
            const raw = parseInt(input.value, 10);
            const next = isNaN(raw) ? 0 : Math.max(0, raw);
            this._updateCardZeroState(card, next);
            if (card.dataset.customSpecies) {
                const speciesId = card.dataset.speciesId;
                const entry = this.customSpecies.find(
                    (e) => e.id === speciesId
                );
                if (entry) entry.count = next;
            }
        });

        // On blur: normalise empty/invalid input back to 0
        el.addEventListener(
            'blur',
            (e) => {
                const input = e.target.closest('[data-counter-value]');
                if (!input) return;
                const raw = parseInt(input.value, 10);
                if (isNaN(raw) || raw < 0) {
                    input.value = '0';
                    const card = input.closest(
                        '.miniwald-observation-species-card'
                    );
                    if (card) this._updateCardZeroState(card, 0);
                } else if (raw >= 1000) {
                    input.value = '999';
                } else {
                    input.value = String(raw);
                }
            },
            true
        ); // capture so blur fires on input elements

        // Remark remove button (delegated)
        el.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-remark-remove]');
            if (!btn) return;
            const card = btn.closest('.miniwald-observation-species-card');
            const speciesId = card?.dataset.speciesId;
            const remarkArea = card?.querySelector('[data-remark-area]');
            const remarkInput = card?.querySelector('[data-remark-input]');
            if (remarkInput) remarkInput.value = '';
            if (remarkArea) remarkArea.hidden = true;
            if (speciesId) this.remarksBySpecies.delete(speciesId);
        });

        // Add custom species buttons (present in both step 3 and step 4)
        el.querySelectorAll('[data-add-custom-species]').forEach((btn) => {
            btn.addEventListener('click', () => this._addCustomSpeciesCard());
        });

        // Species info modal: help buttons
        el.querySelectorAll(
            '.miniwald-observation-species-card__help-btn'
        ).forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const card = btn.closest('.miniwald-observation-species-card');
                if (card) this.w._openSpeciesModal(card);
            });
        });

        // Add-photo buttons: toggle popup
        el.querySelectorAll('[data-add-photo]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const addPhotoDiv = btn.closest(
                    '.miniwald-observation-species-card__add-photo'
                );
                if (addPhotoDiv?.classList.contains('disabled')) return;
                const popup = addPhotoDiv?.querySelector('[data-add-popup]');
                if (!popup) return;
                const isOpen = !popup.hidden;
                this._closeAllPopups();
                if (!isOpen) popup.hidden = false;
            });
        });

        // Popup: "Kamera"
        el.querySelectorAll('[data-popup-add-camera]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const addPhotoDiv = btn.closest(
                    '.miniwald-observation-species-card__add-photo'
                );
                if (addPhotoDiv?.classList.contains('disabled')) return;
                const popup = addPhotoDiv?.querySelector('[data-add-popup]');
                if (popup) popup.hidden = true;
                addPhotoDiv?.querySelector('[data-camera-input]')?.click();
            });
        });

        // Popup: "Galerie"
        el.querySelectorAll('[data-popup-add-gallery]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const addPhotoDiv = btn.closest(
                    '.miniwald-observation-species-card__add-photo'
                );
                if (addPhotoDiv?.classList.contains('disabled')) return;
                const popup = addPhotoDiv?.querySelector('[data-add-popup]');
                if (popup) popup.hidden = true;
                addPhotoDiv?.querySelector('[data-gallery-input]')?.click();
            });
        });

        // Popup: "Anmerkung hinzufügen"
        el.querySelectorAll('[data-popup-add-remark]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const addPhotoDiv = btn.closest(
                    '.miniwald-observation-species-card__add-photo'
                );
                if (addPhotoDiv?.classList.contains('disabled')) return;
                const popup = addPhotoDiv?.querySelector('[data-add-popup]');
                if (popup) popup.hidden = true;
                const card = btn.closest('.miniwald-observation-species-card');
                const remarkArea = card?.querySelector('[data-remark-area]');
                if (remarkArea) {
                    remarkArea.hidden = false;
                    remarkArea.querySelector('[data-remark-input]')?.focus();
                }
            });
        });

        // File inputs: accumulate selected files per species
        el.querySelectorAll('[data-photo-input]').forEach((input) => {
            input.addEventListener('change', () =>
                this._handlePhotoInput(input)
            );
        });

        // Close popups on document click (outside) and Escape
        document.addEventListener('click', () => this._closeAllPopups());
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this._closeAllPopups();
        });

        // Set initial zero state on all cards
        el.querySelectorAll('.miniwald-observation-species-card').forEach(
            (card) => {
                this._updateCardZeroState(card, 0);
            }
        );

        this._detectCamera();
    }

    async _detectCamera() {
        if (!navigator.mediaDevices?.enumerateDevices) return;
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            if (devices.length === 0) return;
            this.hasCamera = devices.some((d) => d.kind === 'videoinput');
            if (!this.hasCamera) {
                this.el
                    .querySelectorAll('[data-popup-add-camera]')
                    .forEach((btn) => {
                        btn.hidden = true;
                    });
            }
        } catch {
            // keep hasCamera = true on error
        }
    }

    reset() {
        this.photosBySpecies.clear();
        this.remarksBySpecies.clear();
        this.customSpecies = [];

        // Reset all counter values to 0
        this.el.querySelectorAll('[data-counter-value]').forEach((el) => {
            el.value = '0';
        });
        this.el
            .querySelectorAll('.miniwald-observation-species-card')
            .forEach((card) => {
                this._updateCardZeroState(card, 0);
            });

        // Clear photo previews
        this.el.querySelectorAll('[data-photo-preview]').forEach((el) => {
            if (el._objectUrls) {
                el._objectUrls.forEach((u) => URL.revokeObjectURL(u));
                el._objectUrls = [];
            }
            el.innerHTML = '';
            el.hidden = true;
        });

        // Clear remark areas
        this.el.querySelectorAll('[data-remark-area]').forEach((el) => {
            el.hidden = true;
        });
        this.el.querySelectorAll('[data-remark-input]').forEach((el) => {
            el.value = '';
        });

        // Remove custom species cards
        this.el
            .querySelectorAll('[data-custom-species]')
            .forEach((el) => el.remove());
    }

    /** Sync counts, photos, remarks from step 3 cards to step 4 cards. */
    syncToStep4() {
        this.el
            .querySelectorAll('[data-step="3"] [data-species-id]')
            .forEach((card) => {
                const speciesId = card.dataset.speciesId;
                if (card.dataset.customSpecies) return;

                const value =
                    parseInt(
                        card.querySelector('[data-counter-value]')?.value ??
                            '0',
                        10
                    ) || 0;
                const step4Card = this.el.querySelector(
                    `[data-step="4"] [data-species-id="${speciesId}"]`
                );
                if (!step4Card) return;

                const valueEl = step4Card.querySelector('[data-counter-value]');
                if (valueEl) valueEl.value = String(value);
                this._updateCardZeroState(step4Card, value);
                this._renderPhotoPreview(step4Card, speciesId);

                const step3RemarkInput = card.querySelector(
                    '[data-remark-input]'
                );
                const remarkText = step3RemarkInput?.value?.trim() ?? '';
                if (remarkText)
                    this.remarksBySpecies.set(speciesId, remarkText);

                const step4RemarkArea =
                    step4Card.querySelector('[data-remark-area]');
                const step4RemarkInput = step4Card.querySelector(
                    '[data-remark-input]'
                );
                if (step4RemarkInput && remarkText) {
                    step4RemarkInput.value = remarkText;
                    if (step4RemarkArea) step4RemarkArea.hidden = false;
                }
            });

        this._renderCustomCardsInStep(4);
    }

    /**
     * Collect all species items from step 4 with count > 0 (or photos/remarks).
     * @returns {Array}
     */
    collectItems() {
        const items = [];
        this.el
            .querySelectorAll('[data-step="4"] [data-species-id]')
            .forEach((card) => {
                const speciesId = card.dataset.speciesId;
                const count =
                    parseInt(
                        card.querySelector('[data-counter-value]')?.value ??
                            '0',
                        10
                    ) || 0;
                const hasPhotos =
                    (this.photosBySpecies.get(speciesId)?.length ?? 0) > 0;
                const remarkText =
                    card.querySelector('[data-remark-input]')?.value?.trim() ??
                    '';
                if (remarkText)
                    this.remarksBySpecies.set(speciesId, remarkText);
                const remarks = this.remarksBySpecies.get(speciesId) ?? null;

                if (card.dataset.customSpecies) {
                    // Either a known subspecies was picked from the dropdown or a
                    // free-text name was typed — never both (the card enforces it).
                    const subspeciesId =
                        card.querySelector('[data-custom-subspecies]')?.value ||
                        null;
                    const name =
                        card
                            .querySelector('[data-custom-species-name]')
                            ?.value?.trim() || '';
                    if (
                        (subspeciesId || name) &&
                        (count > 0 || hasPhotos || remarks)
                    ) {
                        items.push({
                            speciesId: null,
                            subspeciesId,
                            clientId: speciesId,
                            customSpeciesName: subspeciesId ? null : name,
                            count,
                            remarks,
                        });
                    }
                    return;
                }

                if (count > 0 || hasPhotos || remarks) {
                    items.push({ speciesId, count, remarks });
                }
            });
        return items;
    }

    _closeAllPopups() {
        this.el.querySelectorAll('[data-add-popup]').forEach((p) => {
            p.hidden = true;
        });
    }

    _handleCounter(btn) {
        const card = btn.closest('.miniwald-observation-species-card');
        if (!card) return;
        const valueEl = card.querySelector('[data-counter-value]');
        if (!valueEl) return;
        const current = parseInt(valueEl.value, 10) || 0;
        const isPlus = btn.dataset.counter === 'plus';
        const next = Math.max(0, current + (isPlus ? 1 : -1));
        valueEl.value = String(next);
        this._updateCardZeroState(card, next);

        // Sync count back to the customSpecies entry so it survives step transitions
        if (card.dataset.customSpecies) {
            const speciesId = card.dataset.speciesId;
            const entry = this.customSpecies.find((e) => e.id === speciesId);
            if (entry) entry.count = next;
        }
    }

    _updateCardZeroState(card, value) {
        const isZero = value === 0;
        card.classList.toggle('is-zero', isZero);
        const minusBtn = card.querySelector('[data-counter="minus"]');
        if (minusBtn) minusBtn.disabled = isZero;
    }

    async _handlePhotoInput(input) {
        const card = input.closest('.miniwald-observation-species-card');
        if (!card) return;
        const speciesId = card.dataset.speciesId;
        if (!speciesId) return;

        const existing = this.photosBySpecies.get(speciesId) ?? [];

        // Use allSettled so one bad file doesn't block the rest.
        // Rejected promises mean the browser couldn't decode the image
        // (e.g. HEIC on unsupported browsers) — we skip those files and
        // warn the user instead of uploading the raw file with EXIF intact.
        const results = await Promise.allSettled(
            [...input.files].map((f) => this._resizeImage(f))
        );

        const blobs = [];
        let hadUnsupported = false;
        for (const result of results) {
            if (result.status === 'fulfilled') {
                blobs.push(result.value);
            } else {
                hadUnsupported = true;
            }
        }

        if (hadUnsupported) {
            // eslint-disable-next-line no-alert
            alert(
                this.w.strings.photoUnsupportedFormat ??
                    'Dieses Bildformat wird nicht unterstützt. Bitte JPEG oder PNG verwenden.'
            );
        }

        this.photosBySpecies.set(speciesId, [...existing, ...blobs]);
        input.value = '';

        this._renderPhotoPreview(card, speciesId);

        // Sync to counterpart card in the other step
        const stepAttr = card.closest('[data-step]')?.dataset.step;
        const otherStep = stepAttr === '3' ? '4' : '3';
        const otherCard = this.el.querySelector(
            `[data-step="${otherStep}"] [data-species-id="${speciesId}"]`
        );
        if (otherCard) this._renderPhotoPreview(otherCard, speciesId);
    }

    _renderPhotoPreview(card, speciesId) {
        const previewEl = card.querySelector('[data-photo-preview]');
        if (!previewEl) return;

        if (previewEl._objectUrls) {
            previewEl._objectUrls.forEach((u) => URL.revokeObjectURL(u));
        }
        previewEl._objectUrls = [];
        previewEl.innerHTML = '';

        const photos = this.photosBySpecies.get(speciesId) ?? [];
        if (photos.length === 0) {
            previewEl.hidden = true;
            return;
        }

        previewEl.hidden = false;
        photos.forEach((blob, idx) => {
            const url = URL.createObjectURL(blob);
            previewEl._objectUrls.push(url);

            const thumb = document.createElement('div');
            thumb.className = 'miniwald-observation-species-card__photo-thumb';

            const img = document.createElement('img');
            img.src = url;
            img.alt = '';
            img.loading = 'lazy';

            const removeBtn = document.createElement('button');
            removeBtn.className =
                'miniwald-observation-species-card__photo-thumb-remove';
            removeBtn.type = 'button';
            removeBtn.innerHTML = this.w.icons.close ?? '✕';
            removeBtn.setAttribute('aria-label', this.w.strings.removePhoto);
            removeBtn.addEventListener('click', () =>
                this._removePhoto(speciesId, idx)
            );

            thumb.appendChild(img);
            thumb.appendChild(removeBtn);
            previewEl.appendChild(thumb);
        });
    }

    _removePhoto(speciesId, idx) {
        const photos = this.photosBySpecies.get(speciesId) ?? [];
        photos.splice(idx, 1);
        if (photos.length === 0) {
            this.photosBySpecies.delete(speciesId);
        } else {
            this.photosBySpecies.set(speciesId, photos);
        }
        this.el
            .querySelectorAll(`[data-species-id="${speciesId}"]`)
            .forEach((card) => {
                this._renderPhotoPreview(card, speciesId);
            });
    }

    _resizeImage(file) {
        return new Promise((resolve, reject) => {
            const maxW = this.w.options.photoMaxWidth;
            const maxH = this.w.options.photoMaxHeight;
            const quality = this.w.options.photoQuality;
            const img = new Image();
            const url = URL.createObjectURL(file);

            img.onload = () => {
                URL.revokeObjectURL(url);
                let { width, height } = img;
                const ratio = Math.min(maxW / width, maxH / height, 1);
                width = Math.round(width * ratio);
                height = Math.round(height * ratio);

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                canvas.getContext('2d').drawImage(img, 0, 0, width, height);
                canvas.toBlob(
                    (blob) => {
                        if (blob) {
                            resolve(blob);
                        } else {
                            // canvas.toBlob returned null — unsupported format
                            reject(new Error('unsupported_format'));
                        }
                    },
                    'image/jpeg',
                    quality
                );
            };
            img.onerror = () => {
                URL.revokeObjectURL(url);
                // Browser could not decode the image (e.g. HEIC on unsupported browsers).
                // Reject so the caller can show a user-facing error instead of
                // uploading the raw file with EXIF data intact.
                reject(new Error('unsupported_format'));
            };
            img.src = url;
        });
    }

    _addCustomSpeciesCard() {
        const tempId = `custom-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`;
        const entry = { id: tempId, name: '', subspeciesId: null, count: 0 };
        this.customSpecies.push(entry);

        const activeStep = this.el.querySelector(
            '.miniwald-observation-step.is-active'
        );
        if (!activeStep) return;
        const grid =
            activeStep.querySelector(
                '[data-species-grid] .miniwald-observation-species-group.is-active'
            ) ?? activeStep.querySelector('[data-species-grid]');
        if (!grid) return;

        const card = this._buildCustomCard(entry);
        grid.appendChild(card);
        this._updateCardZeroState(card, 0);
    }

    _buildCustomCard(entry) {
        const card = document.createElement('div');
        card.className =
            'miniwald-observation-species-card miniwald-observation-species-card--custom';
        card.dataset.speciesId = entry.id;
        card.dataset.customSpecies = 'true';

        // ── content wrapper ──────────────────────────────────────────────────
        const contentDiv = document.createElement('div');
        contentDiv.className = 'miniwald-observation-species-card__content';

        const imageDiv = document.createElement('div');
        imageDiv.className = 'miniwald-observation-species-card__image';
        const iconSvg = this.w.icons['species-' + (this.w.selectedType ?? '')];
        if (iconSvg) {
            imageDiv.innerHTML = iconSvg;
        } else if (this.w.selectedCategoryImage) {
            const img = document.createElement('img');
            img.src = this.w.selectedCategoryImage;
            img.alt = '';
            img.loading = 'lazy';
            imageDiv.appendChild(img);
        }

        const infoDiv = document.createElement('div');
        infoDiv.className = 'miniwald-observation-species-card__column-info';

        const labelDiv = document.createElement('div');
        labelDiv.className = 'miniwald-observation-species-card__label';

        const input = document.createElement('input');
        input.type = 'text';
        input.className =
            'miniwald-observation-species-card__label-content miniwald-observation-species-card__custom-name';
        input.placeholder = '…';
        input.value = entry.name;
        input.dataset.customSpeciesName = 'true';
        input.disabled = !!entry.subspeciesId;

        // Dropdown of the known subspecies of the selected category (if any).
        // Rendered by Twig once per category and cloned here.
        const subspeciesTemplate = this.el.querySelector(
            `[data-subspecies-template][data-category-id="${this.w.selectedCategoryId ?? ''}"] select`
        );
        const subspeciesSelect = subspeciesTemplate
            ? subspeciesTemplate.cloneNode(true)
            : null;

        input.addEventListener('input', () => {
            entry.name = input.value;
        });

        if (subspeciesSelect) {
            subspeciesSelect.value = entry.subspeciesId ?? '';
            subspeciesSelect.addEventListener('change', () => {
                entry.subspeciesId = subspeciesSelect.value || null;
                if (entry.subspeciesId) {
                    input.value = '';
                    entry.name = '';
                }
                input.disabled = !!entry.subspeciesId;
            });
        }

        const removeBtn = document.createElement('button');
        removeBtn.className =
            'btn btn-transparent miniwald-observation-species-card__remove-btn';
        removeBtn.type = 'button';
        removeBtn.innerHTML = this.w.icons.close ?? '✕';
        removeBtn.addEventListener('click', () => {
            this.customSpecies = this.customSpecies.filter(
                (e) => e.id !== entry.id
            );
            this.el
                .querySelectorAll(`[data-species-id="${entry.id}"]`)
                .forEach((el) => el.remove());
        });

        labelDiv.appendChild(input);
        labelDiv.appendChild(removeBtn);

        const actionsDiv = document.createElement('div');
        actionsDiv.className = 'miniwald-observation-species-card__actions';

        const counterDiv = document.createElement('div');
        counterDiv.className = 'miniwald-observation-counter';

        const minusBtn = document.createElement('button');
        minusBtn.className = 'miniwald-observation-counter__btn';
        minusBtn.type = 'button';
        minusBtn.dataset.counter = 'minus';
        minusBtn.innerHTML = this.w.icons.decrease ?? '−';

        const valueSpan = document.createElement('input');
        valueSpan.type = 'number';
        valueSpan.min = '0';
        valueSpan.setAttribute('inputmode', 'numeric');
        valueSpan.className = 'miniwald-observation-counter__value';
        valueSpan.dataset.counterValue = 'true';
        valueSpan.value = String(entry.count);

        const plusBtn = document.createElement('button');
        plusBtn.className = 'miniwald-observation-counter__btn';
        plusBtn.type = 'button';
        plusBtn.dataset.counter = 'plus';
        plusBtn.innerHTML = this.w.icons.increase ?? '+';

        [minusBtn, valueSpan, plusBtn].forEach((el) =>
            counterDiv.appendChild(el)
        );

        // ── add-photo popup ──────────────────────────────────────────────────
        const addPhotoDiv = document.createElement('div');
        addPhotoDiv.className = 'miniwald-observation-species-card__add-photo';

        const addPhotoBtn = document.createElement('button');
        addPhotoBtn.className =
            'btn btn-green miniwald-observation-species-card__add-photo-btn';
        addPhotoBtn.type = 'button';
        addPhotoBtn.dataset.addPhoto = 'true';
        addPhotoBtn.setAttribute('aria-label', this.w.strings.addPhoto);
        addPhotoBtn.innerHTML = this.w.icons['plus'] ?? '';

        const photoBadge = document.createElement('span');
        photoBadge.className = 'miniwald-observation-species-card__photo-badge';
        photoBadge.dataset.photoCount = 'true';
        photoBadge.textContent = '0';
        photoBadge.hidden = true;
        addPhotoBtn.appendChild(photoBadge);

        const popup = document.createElement('div');
        popup.className = 'miniwald-observation-species-card__add-popup';
        popup.hidden = true;
        popup.dataset.addPopup = 'true';

        addPhotoBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = !popup.hidden;
            this._closeAllPopups();
            if (!isOpen) popup.hidden = false;
        });

        const popupCameraBtn = document.createElement('button');
        popupCameraBtn.className = 'btn btn-green';
        popupCameraBtn.type = 'button';
        popupCameraBtn.innerHTML =
            (this.w.icons['add-photo'] ?? '') + ' ' + this.w.strings.addCamera;
        popupCameraBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (addPhotoDiv.classList.contains('disabled')) return;
            popup.hidden = true;
            cameraInput.click();
        });
        popupCameraBtn.hidden = !this.hasCamera;

        const popupGalleryBtn = document.createElement('button');
        popupGalleryBtn.className = 'btn btn-green';
        popupGalleryBtn.type = 'button';
        popupGalleryBtn.innerHTML =
            (this.w.icons['add-photo'] ?? '') + ' ' + this.w.strings.addGallery;
        popupGalleryBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (addPhotoDiv.classList.contains('disabled')) return;
            popup.hidden = true;
            galleryInput.click();
        });

        const popupRemarkBtn = document.createElement('button');
        popupRemarkBtn.className = 'btn btn-green';
        popupRemarkBtn.type = 'button';
        popupRemarkBtn.innerHTML =
            (this.w.icons['add-remark'] ?? '') + ' ' + this.w.strings.addRemark;
        popupRemarkBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (addPhotoDiv.classList.contains('disabled')) return;
            popup.hidden = true;
            remarkArea.hidden = false;
            remarkInput.focus();
        });

        popup.appendChild(popupCameraBtn);
        popup.appendChild(popupGalleryBtn);
        popup.appendChild(popupRemarkBtn);

        const cameraInput = document.createElement('input');
        cameraInput.type = 'file';
        cameraInput.accept = 'image/*';
        cameraInput.capture = 'environment';
        cameraInput.className =
            'miniwald-observation-species-card__photo-input';
        cameraInput.dataset.photoInput = 'true';
        cameraInput.dataset.cameraInput = 'true';
        cameraInput.hidden = true;
        cameraInput.addEventListener('change', () =>
            this._handlePhotoInput(cameraInput)
        );

        const galleryInput = document.createElement('input');
        galleryInput.type = 'file';
        galleryInput.accept = 'image/*';
        galleryInput.multiple = true;
        galleryInput.className =
            'miniwald-observation-species-card__photo-input';
        galleryInput.dataset.photoInput = 'true';
        galleryInput.dataset.galleryInput = 'true';
        galleryInput.hidden = true;
        galleryInput.addEventListener('change', () =>
            this._handlePhotoInput(galleryInput)
        );

        addPhotoDiv.appendChild(addPhotoBtn);
        addPhotoDiv.appendChild(popup);
        addPhotoDiv.appendChild(cameraInput);
        addPhotoDiv.appendChild(galleryInput);

        actionsDiv.appendChild(counterDiv);
        actionsDiv.appendChild(addPhotoDiv);

        infoDiv.appendChild(labelDiv);
        if (subspeciesSelect) infoDiv.appendChild(subspeciesSelect);
        infoDiv.appendChild(actionsDiv);

        contentDiv.appendChild(imageDiv);
        contentDiv.appendChild(infoDiv);
        card.appendChild(contentDiv);

        // ── remark + photo preview container ─────────────────────────────────
        const remarkPhotoContainer = document.createElement('div');
        remarkPhotoContainer.className =
            'miniwald-observation-species-card__remark-photo-container';

        const photoPreview = document.createElement('div');
        photoPreview.className =
            'miniwald-observation-species-card__photo-preview';
        photoPreview.dataset.photoPreview = 'true';
        photoPreview.hidden = true;

        const remarkArea = document.createElement('div');
        remarkArea.className = 'miniwald-observation-species-card__remark-area';
        remarkArea.dataset.remarkArea = 'true';
        remarkArea.hidden = true;

        const remarkHeader = document.createElement('div');
        remarkHeader.className =
            'miniwald-observation-species-card__remark-header';

        const remarkLabel = document.createElement('span');
        remarkLabel.className =
            'miniwald-observation-species-card__remark-label';
        remarkLabel.textContent = this.w.strings.remarkLabel;

        const remarkRemoveBtn = document.createElement('button');
        remarkRemoveBtn.className =
            'btn btn-transparent miniwald-observation-species-card__remark-remove';
        remarkRemoveBtn.type = 'button';
        remarkRemoveBtn.dataset.remarkRemove = 'true';
        remarkRemoveBtn.innerHTML = this.w.icons.close ?? '✕';
        remarkRemoveBtn.setAttribute('aria-label', this.w.strings.remarkRemove);
        remarkRemoveBtn.addEventListener('click', () => {
            remarkInput.value = '';
            remarkArea.hidden = true;
            this.remarksBySpecies.delete(entry.id);
        });

        remarkHeader.appendChild(remarkLabel);
        remarkHeader.appendChild(remarkRemoveBtn);

        const remarkInput = document.createElement('textarea');
        remarkInput.className =
            'miniwald-observation-species-card__remark-input';
        remarkInput.dataset.remarkInput = 'true';
        remarkInput.rows = 3;
        remarkInput.addEventListener('input', () => {
            const text = remarkInput.value.trim();
            if (text) {
                this.remarksBySpecies.set(entry.id, text);
            } else {
                this.remarksBySpecies.delete(entry.id);
            }
        });

        remarkArea.appendChild(remarkHeader);
        remarkArea.appendChild(remarkInput);

        remarkPhotoContainer.appendChild(photoPreview);
        remarkPhotoContainer.appendChild(remarkArea);
        card.appendChild(remarkPhotoContainer);

        return card;
    }

    _renderCustomCardsInStep(stepNum) {
        const stepEl = this.el.querySelector(`[data-step="${stepNum}"]`);
        if (!stepEl) return;

        stepEl
            .querySelectorAll('[data-custom-species]')
            .forEach((el) => el.remove());
        if (this.customSpecies.length === 0) return;

        const grid =
            stepEl.querySelector(
                '[data-species-grid] .miniwald-observation-species-group.is-active'
            ) ?? stepEl.querySelector('[data-species-grid]');
        if (!grid) return;

        this.customSpecies.forEach((entry) => {
            const card = this._buildCustomCard(entry);
            const valueEl = card.querySelector('[data-counter-value]');
            if (valueEl) valueEl.textContent = String(entry.count);
            this._updateCardZeroState(card, entry.count);
            this._renderPhotoPreview(card, entry.id);
            const remark = this.remarksBySpecies.get(entry.id);
            if (remark) {
                const ri = card.querySelector('[data-remark-input]');
                const ra = card.querySelector('[data-remark-area]');
                if (ri) ri.value = remark;
                if (ra) ra.hidden = false;
            }
            grid.appendChild(card);
        });
    }
}

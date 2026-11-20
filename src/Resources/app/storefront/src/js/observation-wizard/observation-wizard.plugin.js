const Plugin = window.PluginBaseClass;
import GroundHandler from './ground-handler';
import PlantHandler from './plant-handler';
import SpeciesHandler from './species-handler';
import TimerHandler from './timer-handler';
import SaveHandler from './save-handler';

export default class ObservationWizardPlugin extends Plugin {
    static options = {
        totalSeconds: 15 * 60,
        countdownStepMs: 800,
        photoMaxWidth: 2048,
        photoMaxHeight: 1536,
        photoQuality: 0.85,
    };

    init() {
        // ── Shared state ──────────────────────────────────────────────────────
        this.currentStep = 1;
        this.selectedCategoryId = null;
        this.selectedType = null;
        this.selectedCategoryLabel = null;
        this.selectedCategoryImage = null;
        this.detectedLat = null;
        this.detectedLng = null;

        // ── DOM refs ──────────────────────────────────────────────────────────
        this.stepContainer = this.el.querySelector(
            '.miniwald-observation-wizard'
        );
        this.isLoggedIn = this.stepContainer?.dataset.isLoggedIn === '1';
        this.loginUrl =
            this.stepContainer?.dataset.loginUrl ?? '/account/login';
        this.hideInstruction = {
            insects: this.stepContainer?.dataset.hideInstructionInsects === '1',
            birds: this.stepContainer?.dataset.hideInstructionBirds === '1',
            butterflies:
                this.stepContainer?.dataset.hideInstructionButterflies === '1',
            soil: this.stepContainer?.dataset.hideInstructionSoil === '1',
            ground: this.stepContainer?.dataset.hideInstructionGround === '1',
            plants: this.stepContainer?.dataset.hideInstructionPlants === '1',
        };
        this.steps = [...this.el.querySelectorAll('[data-step]')];
        this.finishModal = this.el.querySelector('[data-finish-modal]');
        this.leaveModal = this.el.querySelector('[data-leave-modal]');
        this.speciesModal = this.el.querySelector('[data-species-modal]');
        this.instructionModals = {
            insects: this.el.querySelector('[data-instruction-modal-insects]'),
            birds: this.el.querySelector('[data-instruction-modal-birds]'),
            butterflies: this.el.querySelector(
                '[data-instruction-modal-butterflies]'
            ),
            soil: this.el.querySelector('[data-instruction-modal-soil]'),
        };
        this.groundInstructionModal = this.el.querySelector(
            '[data-ground-instruction-modal]'
        );
        this.plantsInstructionModal = this.el.querySelector(
            '[data-plants-instruction-modal]'
        );

        this.helpBtn = document.querySelector('[data-obs-help]');
        this.cancelBtn = document.querySelector('[data-obs-cancel]');
        this.cancelLabel = document.querySelector(
            '.miniwald-header__cancel .text'
        );
        this.cancelLabelDefault = this.cancelLabel?.textContent ?? '';
        this.saveBtn = this.el.querySelector('[data-save-btn]');

        // ── Icon sprites ──────────────────────────────────────────────────────
        this.icons = {};
        document
            .querySelectorAll('[data-icon-sprites] [data-icon]')
            .forEach((el) => {
                this.icons[el.dataset.icon] = el.innerHTML;
            });

        // ── Translated strings (passed via data-str-* from Twig) ──────────────
        const wiz = this.stepContainer;
        this.strings = {
            next: wiz?.dataset.strNext ?? 'Weiter',
            done: wiz?.dataset.strDone ?? 'Fertig',
            observeCategory:
                wiz?.dataset.strObserveCategory ?? '%name% beobachten',
            addPhoto: wiz?.dataset.strAddPhoto ?? 'Foto hinzufügen',
            addCamera: wiz?.dataset.strAddCamera ?? 'Bild aufnehmen',
            addGallery: wiz?.dataset.strAddGallery ?? 'Galleriebild hochladen',
            addRemark: wiz?.dataset.strAddRemark ?? 'Anmerkung hinzufügen',
            remarkLabel: wiz?.dataset.strRemarkLabel ?? 'Anmerkung',
            remarkRemove: wiz?.dataset.strRemarkRemove ?? 'Anmerkung entfernen',
            removePhoto: wiz?.dataset.strRemovePhoto ?? 'Foto entfernen',
            plantCardNumber: wiz?.dataset.strPlantCardNumber ?? 'Pflanze Nr.',
            plantCardRemove:
                wiz?.dataset.strPlantCardRemove ?? 'Pflanze entfernen',
            plantCardSpeciesName:
                wiz?.dataset.strPlantCardSpeciesName ?? 'Artname',
            plantCardTrunk:
                wiz?.dataset.strPlantCardTrunk ?? 'Stammumfang (cm)',
            plantCardHeight: wiz?.dataset.strPlantCardHeight ?? 'Höhe (cm)',
            plantCardCrown:
                wiz?.dataset.strPlantCardCrown ?? 'Kronendurchmesser (cm)',
            plantCardRemark: wiz?.dataset.strPlantCardRemark ?? 'Anmerkung',
            saveErrorTimeout:
                wiz?.dataset.strSaveErrorTimeout ??
                'Das Speichern dauert zu lange. Bitte prüfe deine Internetverbindung und versuche es erneut.',
            saveError:
                wiz?.dataset.strSaveError ??
                'Speichern fehlgeschlagen. Bitte versuche es erneut.',
            photoUploadWarning:
                wiz?.dataset.strPhotoUploadWarning ??
                'Einige Fotos konnten nicht hochgeladen werden.',
            photoUploadProgress:
                wiz?.dataset.strPhotoUploadProgress ??
                '%done% von %total% Fotos hochgeladen',
        };

        // ── Handlers ──────────────────────────────────────────────────────────
        this.groundHandler = new GroundHandler(this);
        this.plantHandler = new PlantHandler(this);
        this.speciesHandler = new SpeciesHandler(this);
        this.timerHandler = new TimerHandler(this);
        this.saveHandler = new SaveHandler(this);

        this.groundHandler.init();
        this.plantHandler.init();
        this.speciesHandler.init();
        this.timerHandler.init();
        this.saveHandler.init();

        this._bindEvents();

        // ── Leave guard: warn before the tab/page is closed while an
        // observation is in progress (step 3+). Disabled after a successful
        // save including photos or an explicit cancel. ──
        this.leaveGuardDisabled = false;
        window.addEventListener('beforeunload', (e) => {
            if (this.currentStep >= 3 && !this.leaveGuardDisabled) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // ── Auto-restore wizard state from URL params (post-login redirect) ──
        this._restoreFromUrl();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Auth gating
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Redirect to login if not authenticated.
     * Builds a returnUrl pointing back to /observation with wizard state as
     * query params. The login form embeds this as a hidden field; the
     * StorefrontSubscriber reads it after successful login and redirects there.
     *
     * @param {object} returnParams  e.g. { type: 'voegel', categoryId: 'abc', step: 'timer' }
     * @returns {boolean}  true = logged in (proceed), false = redirecting
     */
    _requireAuth(returnParams) {
        if (this.isLoggedIn) {
            return true;
        }

        // Build the return URL: /observation?type=X&categoryId=Y&step=Z
        const obsParams = new URLSearchParams();
        Object.entries(returnParams).forEach(([k, v]) => {
            if (v !== null && v !== undefined) {
                obsParams.set(k, v);
            }
        });
        const returnUrl = '/observation?' + obsParams.toString();

        // Redirect to login page with returnUrl as query param so the login
        // template can embed it as a hidden field in the form
        const loginParams = new URLSearchParams();
        loginParams.set('returnUrl', returnUrl);

        window.location.href = this.loginUrl + '?' + loginParams.toString();
        return false;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Auto-restore from URL params after login redirect
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * After a login redirect, Shopware appends the redirectParameters as query
     * params to the observation URL. We parse them and restore the wizard state.
     *
     * Expected params: type, categoryId (optional), step (optional: 'timer'|'review')
     */
    _restoreFromUrl() {
        if (!this.isLoggedIn) return;

        const urlParams = new URLSearchParams(window.location.search);
        const type = urlParams.get('type');
        if (!type) return;

        const categoryId = urlParams.get('categoryId') || null;

        // Clean up URL without triggering a reload
        const cleanUrl = window.location.pathname;
        history.replaceState(null, '', cleanUrl);

        // Find the matching category card and simulate a click to set all state
        let card = null;
        if (categoryId) {
            card = this.el.querySelector(
                `a.miniwald-observation-type-card[data-category-id="${categoryId}"]`
            );
        }
        if (!card && type) {
            card = this.el.querySelector(
                `a.miniwald-observation-type-card[data-type="${type}"]`
            );
        }

        if (!card) return;

        // Set state from card (mirrors what _bindEvents does on click)
        this.selectedCategoryId = card.dataset.categoryId || null;
        this.selectedType = card.dataset.type || null;
        this.selectedCategoryLabel =
            card
                .querySelector('.miniwald-observation-type-card__label')
                ?.textContent?.trim() || null;
        this.selectedCategoryImage = card.querySelector('img')?.src || null;

        if (this.selectedCategoryId) {
            this._selectCategory(this.selectedCategoryId);
        }
        this._updateCancelLabel();

        // Navigate to the appropriate step, skipping instruction modals
        // (user already saw them before being redirected to login)
        if (type === 'ground' || type === 'plants') {
            // Go directly to step 3 — instruction was already shown (or hidden)
            this._goToStep(3);
        } else {
            // Animal type: go to step 2
            // If step param indicates they wanted to start the timer or go to review,
            // land on step 2 so they can click the button again (no data loss since
            // they hadn't entered any counts yet).
            this._goToStep(2);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Step navigation
    // ─────────────────────────────────────────────────────────────────────────

    _goToStep(step) {
        if (step < 1 || step > this.steps.length) return;
        this._prevStep = this.currentStep;
        this.currentStep = step;

        this.steps.forEach((panel) => {
            panel.classList.toggle(
                'is-active',
                parseInt(panel.dataset.step, 10) === step
            );
        });

        window.scrollTo({ top: 0, behavior: 'smooth' });
        document.body.classList.toggle('wizard-in-progress', step > 1);

        // Help icon: show on step 2 + 3 for non-ground, non-plants types
        if (this.helpBtn) {
            this.helpBtn.hidden = !(
                [2, 3].includes(step) &&
                this.selectedType !== 'ground' &&
                this.selectedType !== 'plants'
            );
        }

        if ([4, 5].includes(step)) {
            this._updateCategoryTitles();
        }

        if (step === 3) {
            if (this.selectedType === 'ground') {
                this._showGroundMatrix(this._prevStep);
                this._hidePlantPanel();
            } else if (this.selectedType === 'plants') {
                this._hideGroundMatrix();
                this._showPlantPanel();
            } else {
                this._hideGroundMatrix();
                this._hidePlantPanel();
            }
        }

        if (step === 4) {
            // Ground type skips step 4 (review) — goes straight to step 5
            if (this.selectedType === 'ground') {
                this._goToStep(5);
                return;
            }
            // Plants type skips step 4 — stays on step 3 (plant panel has its own save)
            if (this.selectedType === 'plants') {
                return;
            }
            // Only sync step 3 → step 4 when navigating forward (from step 3 or earlier).
            // When coming back from step 5, preserve whatever the user entered in step 4.
            if (this._prevStep < 4) {
                this.speciesHandler.syncToStep4();
            }
        }

        if (step === 5) {
            // Plants type never reaches step 5 (save is in plant panel)
            if (this.selectedType === 'plants') {
                return;
            }
            this._detectMiniwald();
        }

        // Stop timer when leaving step 3
        if (step !== 3) {
            this.timerHandler.stop();
        }
    }

    /**
     * Smart back navigation.
     * - Step 5, ground type → go to step 3 (lands on last sub-step via _prevStep tracking)
     * - Step 5, other types → go to step 4
     * - Step 4 → go to step 3
     * - Step 3 → go to step 2 (or step 1 for plants/ground)
     * - Step 2 → go to step 1
     */
    _goBack() {
        if (this.currentStep === 5 && this.selectedType === 'ground') {
            this._goToStep(3);
        } else if (this.currentStep === 3 && this.selectedType === 'plants') {
            // If on plant grid sub-step, go back to form sub-step
            if (this.plantHandler.isOnGridSubStep()) {
                this.plantHandler.goBackToForm();
            } else {
                // On form sub-step → back to step 1
                this._goToStep(1);
            }
        } else if (this.currentStep === 3 && this.selectedType === 'ground') {
            this._goToStep(1);
        } else {
            this._goToStep(this.currentStep - 1);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Ground matrix show/hide
    // ─────────────────────────────────────────────────────────────────────────

    _showGroundMatrix(fromStep = null) {
        const speciesPanel = this.el.querySelector('[data-species-panel]');
        const timerBar = this.el.querySelector('[data-timer]');
        const matrix = this.el.querySelector('[data-ground-matrix]');

        if (speciesPanel) speciesPanel.hidden = true;
        if (timerBar) timerBar.hidden = true;
        if (matrix) matrix.hidden = false;

        // Coming back from step 5: land on last sub-step; otherwise start from 0
        if (fromStep === 5) {
            this.groundHandler.showLast();
        } else {
            this.groundHandler.show();
        }
    }

    _hideGroundMatrix() {
        const speciesPanel = this.el.querySelector('[data-species-panel]');
        const timerBar = this.el.querySelector('[data-timer]');
        const matrix = this.el.querySelector('[data-ground-matrix]');

        if (speciesPanel) speciesPanel.hidden = false;
        if (timerBar) timerBar.hidden = false;
        if (matrix) matrix.hidden = true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Plant panel show/hide
    // ─────────────────────────────────────────────────────────────────────────

    _showPlantPanel() {
        const speciesPanel = this.el.querySelector('[data-species-panel]');
        const timerBar = this.el.querySelector('[data-timer]');

        if (speciesPanel) speciesPanel.hidden = true;
        if (timerBar) timerBar.hidden = true;

        this.plantHandler.show();
    }

    _hidePlantPanel() {
        this.plantHandler.hide();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Event wiring
    // ─────────────────────────────────────────────────────────────────────────

    _bindEvents() {
        // Category selection
        this.el
            .querySelectorAll('a.miniwald-observation-type-card[data-type]')
            .forEach((btn) => {
                btn.addEventListener('click', () => {
                    this.selectedCategoryId = btn.dataset.categoryId || null;
                    this.selectedType = btn.dataset.type || null;
                    this.selectedCategoryLabel =
                        btn
                            .querySelector(
                                '.miniwald-observation-type-card__label'
                            )
                            ?.textContent?.trim() || null;
                    this.selectedCategoryImage =
                        btn.querySelector('img')?.src || null;
                    if (this.selectedCategoryId) {
                        this._selectCategory(this.selectedCategoryId);
                    }
                    this._updateCancelLabel();
                    this._openInstructionModal();
                });
            });

        // Generic next/prev step buttons
        this.el.querySelectorAll('[data-next-step]').forEach((btn) => {
            btn.addEventListener('click', () =>
                this._goToStep(this.currentStep + 1)
            );
        });
        this.el.querySelectorAll('[data-prev-step]').forEach((btn) => {
            btn.addEventListener('click', () => this._goBack());
        });

        // "Timer Starten" → requires auth, then step 3 + countdown
        this.el.querySelectorAll('[data-next-start]').forEach((btn) => {
            btn.addEventListener('click', () => {
                if (
                    !this._requireAuth({
                        type: this.selectedType,
                        categoryId: this.selectedCategoryId,
                        step: 'timer',
                    })
                ) {
                    return;
                }
                this._goToStep(3);
                this.timerHandler.startWithCountdown();
            });
        });

        // "Ergebnis Eintragen" → requires auth, then skip timer, go to step 4
        this.el
            .querySelector('[data-skip-to-review]')
            ?.addEventListener('click', () => {
                if (
                    !this._requireAuth({
                        type: this.selectedType,
                        categoryId: this.selectedCategoryId,
                        step: 'review',
                    })
                ) {
                    return;
                }
                this._goToStep(4);
            });

        // Cancel button in header
        // Step 3+ (observation running, data may be entered): ask first.
        // Step 2: reset silently — nothing entered yet.
        this.cancelBtn?.addEventListener('click', (e) => {
            if (this.saveHandler.hasPendingPhotos()) {
                e.preventDefault();
                this.saveHandler.requestCancel();
                return;
            }
            if (this.currentStep >= 3) {
                e.preventDefault();
                this._toggleModal(this.leaveModal, true);
            } else if (this.currentStep > 1) {
                e.preventDefault();
                this._resetToStep1();
            }
        });

        // Leave modal: stay
        this.el
            .querySelector('[data-close-leave-modal]')
            ?.addEventListener('click', () => {
                this._toggleModal(this.leaveModal, false);
            });
        this.leaveModal?.addEventListener('click', (e) => {
            if (e.target === this.leaveModal)
                this._toggleModal(this.leaveModal, false);
        });

        // Leave modal: confirm → discard all data, back to step 1
        this.el
            .querySelector('[data-confirm-leave]')
            ?.addEventListener('click', () => {
                this._toggleModal(this.leaveModal, false);
                this._resetToStep1();
            });

        // Help button in header — reopens the modal for the currently selected type
        this.helpBtn?.addEventListener('click', () => {
            const modal = this.instructionModals[this.selectedType];
            if (modal) this._toggleModal(modal, true);
        });

        // Per-type animal instruction modals: close button + backdrop
        ['insects', 'birds', 'butterflies', 'soil'].forEach((type) => {
            const modal = this.instructionModals[type];
            if (!modal) return;
            modal
                .querySelector(`[data-close-instruction-modal-${type}]`)
                ?.addEventListener('click', () => {
                    this._closeInstructionModal(modal, type);
                    if (this.currentStep < 2) this._goToStep(2);
                });
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this._closeInstructionModal(modal, type);
                    if (this.currentStep < 2) this._goToStep(2);
                }
            });
        });

        // Ground instruction modal: close → check auth → go to step 3
        this.el
            .querySelector('[data-close-ground-instruction-modal]')
            ?.addEventListener('click', () => {
                this._closeInstructionModal(
                    this.groundInstructionModal,
                    'ground'
                );
                if (
                    !this._requireAuth({
                        type: 'ground',
                    })
                ) {
                    return;
                }
                this._goToStep(3);
            });
        this.groundInstructionModal?.addEventListener('click', (e) => {
            if (e.target === this.groundInstructionModal) {
                this._closeInstructionModal(
                    this.groundInstructionModal,
                    'ground'
                );
                if (
                    !this._requireAuth({
                        type: 'ground',
                    })
                ) {
                    return;
                }
                this._goToStep(3);
            }
        });

        // Plants instruction modal: close → check auth → go to step 3
        this.el
            .querySelector('[data-close-plants-instruction-modal]')
            ?.addEventListener('click', () => {
                this._closeInstructionModal(
                    this.plantsInstructionModal,
                    'plants'
                );
                if (
                    !this._requireAuth({
                        type: 'plants',
                    })
                ) {
                    return;
                }
                this._goToStep(3);
            });
        this.plantsInstructionModal?.addEventListener('click', (e) => {
            if (e.target === this.plantsInstructionModal) {
                this._closeInstructionModal(
                    this.plantsInstructionModal,
                    'plants'
                );
                if (
                    !this._requireAuth({
                        type: 'plants',
                    })
                ) {
                    return;
                }
                this._goToStep(3);
            }
        });

        // Species info modal: close button
        this.el
            .querySelector('[data-close-species-modal]')
            ?.addEventListener('click', () => {
                this._toggleModal(this.speciesModal, false);
            });

        // Species info modal: close on backdrop click
        this.speciesModal?.addEventListener('click', (e) => {
            if (e.target === this.speciesModal)
                this._toggleModal(this.speciesModal, false);
        });

        // Enable save button when miniwald select has a value (step 5 form)
        this.el
            .querySelector('[data-miniwald-select]')
            ?.addEventListener('change', (e) => {
                if (this.saveBtn) this.saveBtn.disabled = !e.target.value;
            });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Instruction modal
    // ─────────────────────────────────────────────────────────────────────────

    _openInstructionModal() {
        const type = this.selectedType;
        if (type === 'ground') {
            if (this.hideInstruction.ground) {
                // Instruction hidden — require auth immediately
                if (!this._requireAuth({ type: 'ground' })) {
                    return;
                }
                this._goToStep(3);
                return;
            }
            // Show instruction modal — auth check happens on modal close
            this._toggleModal(this.groundInstructionModal, true);
            return;
        }
        if (type === 'plants') {
            if (this.hideInstruction.plants) {
                // Instruction hidden — require auth immediately
                if (!this._requireAuth({ type: 'plants' })) {
                    return;
                }
                this._goToStep(3);
                return;
            }
            // Show instruction modal — auth check happens on modal close
            this._toggleModal(this.plantsInstructionModal, true);
            return;
        }
        // Per-type animal modals: no auth gate here — user can browse the
        // instruction and step 2 preview without logging in.
        const modal = this.instructionModals[type];
        if (!modal) {
            // Unknown type — skip instruction, go straight to step 2
            this._goToStep(2);
            return;
        }
        if (this.hideInstruction[type]) {
            this._goToStep(2);
            return;
        }
        this._toggleModal(modal, true);
    }

    /**
     * Close an instruction modal, optionally saving the hide-instruction preference.
     * Navigation after close is handled by the caller.
     */
    _closeInstructionModal(modal, type) {
        const checkbox = modal?.querySelector('.hide-instructions__checkbox');
        if (checkbox?.checked) {
            if (this.hideInstruction) {
                this.hideInstruction[type] = true;
            }
            fetch('/observation/hide-instruction', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type }),
            }).catch(() => {});
        }
        this._toggleModal(modal, false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Species info modal
    // ─────────────────────────────────────────────────────────────────────────

    _openSpeciesModal(card) {
        if (!this.speciesModal) return;

        const name = card.dataset.speciesName || '';
        const scientific = card.dataset.speciesScientific || '';
        const description = card.dataset.speciesDescription || '';
        const identification = card.dataset.speciesIdentification || '';

        const thumbEl = card.querySelector(
            '.miniwald-species-card__modal-thumb'
        );
        const imgEl = this.speciesModal.querySelector(
            '[data-species-modal-image]'
        );
        if (imgEl) {
            imgEl.src = thumbEl ? thumbEl.src : '';
            imgEl.alt = name;
            imgEl.hidden = !thumbEl?.src;
        }

        this.speciesModal
            .querySelectorAll('[data-species-modal-name]')
            .forEach((el) => {
                el.textContent = name;
            });

        const scientificEl = this.speciesModal.querySelector(
            '[data-species-modal-scientific]'
        );
        if (scientificEl) {
            scientificEl.textContent = scientific;
            scientificEl.hidden = !scientific;
        }

        const descEl = this.speciesModal.querySelector(
            '[data-species-modal-description]'
        );
        const descSection = this.speciesModal.querySelector(
            '[data-species-modal-description-section]'
        );
        if (descEl) descEl.textContent = description;
        if (descSection) descSection.hidden = !description;

        const tipsEl = this.speciesModal.querySelector(
            '[data-species-modal-tips]'
        );
        const tipsSection = this.speciesModal.querySelector(
            '[data-species-modal-tips-section]'
        );
        if (tipsEl) tipsEl.textContent = identification;
        if (tipsSection) tipsSection.hidden = !identification;

        this._toggleModal(this.speciesModal, true);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Reset
    // ─────────────────────────────────────────────────────────────────────────

    _resetToStep1() {
        this.timerHandler.stop();
        this.speciesHandler.reset();
        this.groundHandler.reset();
        this.plantHandler.reset();

        // Restore matrix/species panel visibility to defaults
        const speciesPanel = this.el.querySelector('[data-species-panel]');
        const timerBar = this.el.querySelector('[data-timer]');
        const matrix = this.el.querySelector('[data-ground-matrix]');
        if (speciesPanel) speciesPanel.hidden = false;
        if (timerBar) timerBar.hidden = false;
        if (matrix) matrix.hidden = true;

        // Restore cancel label
        this.selectedCategoryId = null;
        this.selectedType = null;
        this.selectedCategoryLabel = null;
        if (this.cancelLabel)
            this.cancelLabel.textContent = this.cancelLabelDefault;

        this._goToStep(1);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    _toggleModal(modal, shouldShow) {
        if (!modal) return;
        modal.hidden = !shouldShow;
    }

    _selectCategory(categoryId) {
        this.el.querySelectorAll('[data-category-id]').forEach((el) => {
            el.classList.toggle(
                'is-active',
                el.dataset.categoryId === categoryId
            );
        });
        this.el
            .querySelectorAll('.miniwald-observation-species-group')
            .forEach((group) => {
                group.classList.toggle(
                    'is-active',
                    group.dataset.categoryId === categoryId
                );
            });
    }

    _updateCategoryTitles() {
        if (!this.selectedCategoryLabel) return;
        this.el.querySelectorAll('[data-category-title]').forEach((el) => {
            el.textContent = this.selectedCategoryLabel;
        });
    }

    _updateCancelLabel() {
        if (!this.cancelLabel || !this.selectedCategoryLabel) return;
        this.cancelLabel.textContent = this.strings.observeCategory.replace(
            '%name%',
            this.selectedCategoryLabel
        );
    }

    /**
     * Request GPS, store lat/lng, POST to /miniwald/detect, prefill select if match found.
     * Called when entering step 5 (not used for plants — plant panel has its own form).
     */
    _detectMiniwald() {
        if (!navigator.geolocation) return;

        navigator.geolocation.getCurrentPosition(
            async (pos) => {
                this.detectedLat = pos.coords.latitude;
                this.detectedLng = pos.coords.longitude;

                try {
                    const res = await fetch('/miniwald/detect', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            latitude: this.detectedLat,
                            longitude: this.detectedLng,
                        }),
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    const sel = this.el.querySelector('[data-miniwald-select]');
                    if (sel) {
                        sel.value = data.miniwaldId ?? 'other';
                        sel.dispatchEvent(new Event('change'));
                    }
                } catch {
                    // silent fail
                }
            },
            () => {
                // GPS denied — user selects manually
            }
        );
    }
}

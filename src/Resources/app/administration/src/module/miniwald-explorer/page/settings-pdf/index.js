import template from './settings-pdf.html.twig';
import { mediaFolderMixin } from '../../mixin/media-folder.mixin';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

/**
 * Admin page for managing PDF observation sheets.
 *
 * Displays a dynamic list of observation sheet slots:
 * - One row per species category (type=animal, linked via speciesCategoryId)
 * - One fixed row for plant measurements
 * - One fixed row for ground/air measurements
 *
 * Uses the miniwald_explorer_observation_sheet entity which stores one row
 * per slot with a media_id reference to the uploaded PDF.
 */
Component.register('miniwald-settings-pdf', {
    template,

    inject: ['repositoryFactory'],

    mixins: [Mixin.getByName('notification'), mediaFolderMixin],

    data() {
        return {
            isLoading: false,
            sheets: null,
            speciesCategories: null,
            showMediaModal: false,
            activeSlotKey: null,
            pdfMediaFolderId: null,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        sheetRepository() {
            return this.repositoryFactory.create(
                'miniwald_explorer_observation_sheet'
            );
        },

        speciesCategoryRepository() {
            return this.repositoryFactory.create(
                'miniwald_explorer_species_category'
            );
        },

        /**
         * Build the list of sheet slots to display.
         * Each slot has: { key, type, label, icon, speciesCategoryId? }
         */
        sheetSlots() {
            const slots = [];

            // Animal slots — one per species category
            if (this.speciesCategories) {
                this.speciesCategories.forEach((category) => {
                    slots.push({
                        key: `animal__${category.id}`,
                        type: 'animal',
                        label: category.translated?.name || category.name,
                        speciesCategoryId: category.id,
                    });
                });
            }

            // Fixed slots
            slots.push({
                key: 'plant',
                type: 'plant',
                label: this.$tc('miniwald-explorer.settingsPdf.typePlant'),
                speciesCategoryId: null,
            });

            slots.push({
                key: 'ground',
                type: 'ground',
                label: this.$tc('miniwald-explorer.settingsPdf.typeGround'),
                speciesCategoryId: null,
            });

            return slots;
        },

        /**
         * Build a lookup map: slotKey → sheet entity (or null).
         */
        sheetsBySlotKey() {
            const map = {};
            if (this.sheets) {
                this.sheets.forEach((sheet) => {
                    const key =
                        sheet.type === 'animal'
                            ? `animal__${sheet.speciesCategoryId}`
                            : sheet.type;
                    map[key] = sheet;
                });
            }
            return map;
        },
    },

    created() {
        this.loadData();
        this.loadMediaFolderId();
    },

    methods: {
        loadData() {
            this.isLoading = true;

            Promise.all([this.loadSheets(), this.loadSpeciesCategories()])
                .then(() => {
                    this.isLoading = false;
                })
                .catch(() => {
                    this.isLoading = false;
                    this.createNotificationError({
                        message: this.$tc(
                            'miniwald-explorer.settingsPdf.errorLoad'
                        ),
                    });
                });
        },

        loadSheets() {
            const criteria = new Criteria(1, 100);
            criteria.addAssociation('media');
            criteria.addAssociation('speciesCategory');

            return this.sheetRepository
                .search(criteria, Shopware.Context.api)
                .then((result) => {
                    this.sheets = result;
                });
        },

        loadSpeciesCategories() {
            const criteria = new Criteria(1, 100);
            criteria.addSorting(Criteria.sort('position', 'ASC'));

            return this.speciesCategoryRepository
                .search(criteria, Shopware.Context.api)
                .then((result) => {
                    this.speciesCategories = result;
                });
        },

        getSheetForSlot(slotKey) {
            return this.sheetsBySlotKey[slotKey] || null;
        },

        getMediaForSlot(slotKey) {
            const sheet = this.getSheetForSlot(slotKey);
            return sheet ? sheet.media : null;
        },

        getMediaNameForSlot(slotKey) {
            const media = this.getMediaForSlot(slotKey);
            if (!media) {
                return this.$tc('miniwald-explorer.settingsPdf.noPdf');
            }
            return media.fileName + '.' + media.fileExtension;
        },

        getUploadDateForSlot(slotKey) {
            const sheet = this.getSheetForSlot(slotKey);
            if (!sheet || (!sheet.updatedAt && !sheet.createdAt)) {
                return '—';
            }
            const date = sheet.updatedAt || sheet.createdAt;
            return new Date(date).toLocaleDateString('de-DE', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
            });
        },

        hasMediaForSlot(slotKey) {
            return this.getMediaForSlot(slotKey) !== null;
        },

        getDownloadUrl(slot) {
            if (slot.type === 'animal') {
                return `/miniwald/pdf/animal/${this.slugify(slot.label)}`;
            }
            return `/miniwald/pdf/${slot.type}`;
        },

        /**
         * Convert a string to a URL-friendly slug.
         * Handles German umlauts (ä→ae, ö→oe, ü→ue, ß→ss).
         */
        slugify(text) {
            if (!text) {
                return '';
            }
            return text
                .replace(/ä/g, 'ae')
                .replace(/ö/g, 'oe')
                .replace(/ü/g, 'ue')
                .replace(/Ä/g, 'ae')
                .replace(/Ö/g, 'oe')
                .replace(/Ü/g, 'ue')
                .replace(/ß/g, 'ss')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        },

        /**
         * Open the media modal for selecting/uploading a PDF.
         */
        onOpenMediaModal(slotKey) {
            this.activeSlotKey = slotKey;
            this.showMediaModal = true;
        },

        /**
         * Handle media selection from the media modal.
         */
        onMediaSelectionChange(mediaItems) {
            if (!mediaItems || mediaItems.length === 0 || !this.activeSlotKey) {
                this.showMediaModal = false;
                return;
            }

            const mediaEntity = mediaItems[0];
            this.assignMediaToSlot(this.activeSlotKey, mediaEntity.id);
            this.showMediaModal = false;
        },

        /**
         * Find the slot definition by key.
         */
        findSlotByKey(slotKey) {
            return this.sheetSlots.find((s) => s.key === slotKey) || null;
        },

        /**
         * Assign a media ID to a sheet slot. Creates the sheet entity if it doesn't exist.
         */
        assignMediaToSlot(slotKey, mediaId) {
            this.isLoading = true;

            const slotDef = this.findSlotByKey(slotKey);
            if (!slotDef) {
                this.isLoading = false;
                return;
            }

            let sheet = this.getSheetForSlot(slotKey);

            if (sheet) {
                // Update existing sheet
                sheet.mediaId = mediaId;
                this.sheetRepository
                    .save(sheet, Shopware.Context.api)
                    .then(() => {
                        this.createNotificationSuccess({
                            message: this.$tc(
                                'miniwald-explorer.settingsPdf.successUpload'
                            ),
                        });
                        this.loadData();
                    })
                    .catch(() => {
                        this.isLoading = false;
                        this.createNotificationError({
                            message: this.$tc(
                                'miniwald-explorer.settingsPdf.errorUpload'
                            ),
                        });
                    });
            } else {
                // Create new sheet entity
                sheet = this.sheetRepository.create(Shopware.Context.api);
                sheet.type = slotDef.type;
                sheet.mediaId = mediaId;

                if (slotDef.speciesCategoryId) {
                    sheet.speciesCategoryId = slotDef.speciesCategoryId;
                }

                this.sheetRepository
                    .save(sheet, Shopware.Context.api)
                    .then(() => {
                        this.createNotificationSuccess({
                            message: this.$tc(
                                'miniwald-explorer.settingsPdf.successUpload'
                            ),
                        });
                        this.loadData();
                    })
                    .catch(() => {
                        this.isLoading = false;
                        this.createNotificationError({
                            message: this.$tc(
                                'miniwald-explorer.settingsPdf.errorUpload'
                            ),
                        });
                    });
            }
        },

        /**
         * Remove the PDF from a sheet slot (set media_id to null).
         */
        onRemovePdf(slotKey) {
            const sheet = this.getSheetForSlot(slotKey);
            if (!sheet) {
                return;
            }

            this.isLoading = true;

            this.sheetRepository
                .delete(sheet.id, Shopware.Context.api)
                .then(() => {
                    this.createNotificationSuccess({
                        message: this.$tc(
                            'miniwald-explorer.settingsPdf.successRemove'
                        ),
                    });
                    this.loadData();
                })
                .catch(() => {
                    this.isLoading = false;
                    this.createNotificationError({
                        message: this.$tc(
                            'miniwald-explorer.settingsPdf.errorRemove'
                        ),
                    });
                });
        },

        /**
         * Preview a PDF in a new browser tab.
         */
        onPreviewPdf(slotKey) {
            const media = this.getMediaForSlot(slotKey);
            if (media && media.url) {
                window.open(media.url, '_blank');
            }
        },

        /**
         * Load media folder ID for observation sheets
         */
        async loadMediaFolderId() {
            this.pdfMediaFolderId = await this.getMediaFolderId(
                'MiniwaldExplorer/Observation Sheets'
            );
        },
    },
});

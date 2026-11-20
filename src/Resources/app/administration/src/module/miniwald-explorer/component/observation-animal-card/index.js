import template from './observation-animal-card.html.twig';
import { mediaFolderMixin } from '../../mixin/media-folder.mixin';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('miniwald-observation-animal-card', {
    template,

    inject: ['repositoryFactory'],

    mixins: [Mixin.getByName('notification'), mediaFolderMixin],

    props: {
        observation: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            showPhotoModal: false,
            photoModalItem: null,
            photoToDelete: null,
            // Lightbox state
            lightboxPhoto: null,
            lightboxIndex: -1,
            // Drag-and-drop state
            draggedPhotoIndex: null,
            dragOverPhotoIndex: null,
            // Media folder
            observationMediaFolderId: null,
        };
    },

    created() {
        this.loadMediaFolderId();
    },

    computed: {
        observationItemRepository() {
            return this.repositoryFactory.create(
                'miniwald_explorer_observation_item'
            );
        },

        photoRepository() {
            return this.repositoryFactory.create('miniwald_explorer_photo');
        },

        mediaRepository() {
            return this.repositoryFactory.create('media');
        },

        items() {
            return this.observation.items || [];
        },

        speciesCriteria() {
            const criteria = new Criteria(1, 100);
            criteria.addAssociation('speciesCategory');
            criteria.addSorting(Criteria.sort('position', 'ASC'));
            // Restrict the selectable species to the observation's category;
            // without a category all species are offered and picking one
            // auto-fills the category (see onSpeciesChange)
            if (this.observation.speciesCategoryId) {
                criteria.addFilter(
                    Criteria.equals(
                        'speciesCategoryId',
                        this.observation.speciesCategoryId
                    )
                );
            }
            return criteria;
        },

        speciesCategoryCriteria() {
            const criteria = new Criteria(1, 100);
            criteria.addSorting(Criteria.sort('position', 'ASC'));
            return criteria;
        },

        photoModalPhotos() {
            if (!this.photoModalItem || !this.photoModalItem.photos) {
                return [];
            }
            return this.photoModalItem.photos;
        },

        photoModalUploadTag() {
            if (!this.photoModalItem) {
                return '';
            }
            return 'observation-item-' + this.photoModalItem.id;
        },

        lightboxMediaUrl() {
            if (!this.lightboxPhoto || !this.lightboxPhoto.media) {
                return null;
            }
            return this.lightboxPhoto.media.url;
        },
    },

    methods: {
        onAddItem() {
            const newItem = this.observationItemRepository.create(
                Shopware.Context.api
            );
            newItem.observationId = this.observation.id;
            newItem.count = 1;

            if (!this.observation.items) {
                this.observation.items = [];
            }
            this.observation.items.push(newItem);
        },

        onRemoveItem(item) {
            const index = this.observation.items.findIndex(
                (i) => i.id === item.id
            );
            if (index > -1) {
                this.observation.items.splice(index, 1);
            }
        },

        getItemPhotoCount(item) {
            return item.photos ? item.photos.length : 0;
        },

        getSubspeciesCriteria(speciesId) {
            const criteria = new Criteria(1, 100);
            if (speciesId) {
                criteria.addFilter(Criteria.equals('speciesId', speciesId));
            }
            criteria.addSorting(Criteria.sort('position', 'ASC'));
            return criteria;
        },

        onSpeciesChange(item, newSpeciesId, species) {
            // Check for duplicate species+subspecies combination
            if (
                newSpeciesId &&
                this.hasDuplicateSpecies(item, newSpeciesId, null)
            ) {
                this.createNotificationWarning({
                    message: this.$tc(
                        'miniwald-explorer.observation.detail.animal.duplicateWarning'
                    ),
                });
                // Revert the change
                this.$nextTick(() => {
                    item.speciesId = null;
                });
                return;
            }
            // Clear subspecies when species changes
            item.subspeciesId = null;

            // Auto-fill the observation-level category from the chosen
            // species so custom entries stay categorizable
            if (
                !this.observation.speciesCategoryId &&
                species &&
                species.speciesCategoryId
            ) {
                this.observation.speciesCategoryId = species.speciesCategoryId;
            }
        },

        onSubspeciesChange(item, newSubspeciesId) {
            if (
                item.speciesId &&
                this.hasDuplicateSpecies(item, item.speciesId, newSubspeciesId)
            ) {
                this.createNotificationWarning({
                    message: this.$tc(
                        'miniwald-explorer.observation.detail.animal.duplicateWarning'
                    ),
                });
                // Revert the change
                this.$nextTick(() => {
                    item.subspeciesId = null;
                });
            }
        },

        hasDuplicateSpecies(currentItem, speciesId, subspeciesId) {
            return this.items.some((other) => {
                if (other.id === currentItem.id) {
                    return false;
                }
                return (
                    other.speciesId === speciesId &&
                    other.subspeciesId === subspeciesId
                );
            });
        },

        /**
         * Check if an item is a duplicate of another item in the list.
         * Used for visual highlighting in the template.
         */
        isDuplicate(item) {
            if (!item.speciesId) {
                return false;
            }
            return this.items.some((other) => {
                if (other.id === item.id) {
                    return false;
                }
                return (
                    other.speciesId === item.speciesId &&
                    other.subspeciesId === item.subspeciesId
                );
            });
        },

        // ==========================================
        // Photo modal methods
        // ==========================================

        onOpenPhotoModal(item) {
            this.photoModalItem = item;
            this.showPhotoModal = true;
        },

        onClosePhotoModal() {
            this.showPhotoModal = false;
            this.photoModalItem = null;
            this.lightboxPhoto = null;
            this.lightboxIndex = -1;
            this.draggedPhotoIndex = null;
            this.dragOverPhotoIndex = null;
        },

        onRequestRemovePhoto(photo) {
            this.photoToDelete = photo;
        },

        onCancelRemovePhoto() {
            this.photoToDelete = null;
        },

        onConfirmRemovePhoto() {
            if (
                this.photoToDelete &&
                this.photoModalItem &&
                this.photoModalItem.photos
            ) {
                const index = this.photoModalItem.photos.findIndex(
                    (p) => p.id === this.photoToDelete.id
                );
                if (index > -1) {
                    this.photoModalItem.photos.splice(index, 1);
                    // Update sortOrder for remaining photos
                    this._updatePhotoSortOrder();
                }
            }
            this.photoToDelete = null;
        },

        onMediaUploadFinish({ targetId }) {
            if (!this.photoModalItem) {
                return;
            }

            const newPhoto = this.photoRepository.create(Shopware.Context.api);
            newPhoto.observationItemId = this.photoModalItem.id;
            newPhoto.mediaId = targetId;
            newPhoto.sortOrder = this.getItemPhotoCount(this.photoModalItem);
            newPhoto.isApproved = true;

            // Load the media entity so the preview renders immediately
            this.mediaRepository
                .get(targetId, Shopware.Context.api)
                .then((mediaEntity) => {
                    newPhoto.media = mediaEntity;

                    if (!this.photoModalItem.photos) {
                        this.photoModalItem.photos = [];
                    }
                    this.photoModalItem.photos.push(newPhoto);
                });
        },

        // ==========================================
        // Lightbox methods
        // ==========================================

        onOpenLightbox(photo, index) {
            this.lightboxPhoto = photo;
            this.lightboxIndex = index;

            // Focus the lightbox container for keyboard events
            this.$nextTick(() => {
                if (this.$refs.lightboxContainer) {
                    this.$refs.lightboxContainer.focus();
                }
            });
        },

        onCloseLightbox() {
            this.lightboxPhoto = null;
            this.lightboxIndex = -1;
        },

        onLightboxPrev() {
            if (this.photoModalPhotos.length <= 1) {
                return;
            }
            const newIndex =
                this.lightboxIndex <= 0
                    ? this.photoModalPhotos.length - 1
                    : this.lightboxIndex - 1;

            this.lightboxIndex = newIndex;
            this.lightboxPhoto = this.photoModalPhotos[newIndex];
        },

        onLightboxNext() {
            if (this.photoModalPhotos.length <= 1) {
                return;
            }
            const newIndex =
                this.lightboxIndex >= this.photoModalPhotos.length - 1
                    ? 0
                    : this.lightboxIndex + 1;

            this.lightboxIndex = newIndex;
            this.lightboxPhoto = this.photoModalPhotos[newIndex];
        },

        onLightboxKeydown(event) {
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                this.onLightboxPrev();
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                this.onLightboxNext();
            } else if (event.key === 'Escape') {
                event.preventDefault();
                this.onCloseLightbox();
            }
        },

        // ==========================================
        // Drag-and-drop reorder methods
        // ==========================================

        onPhotoDragStart(index, event) {
            this.draggedPhotoIndex = index;
            event.dataTransfer.effectAllowed = 'move';
            // Required for Firefox
            event.dataTransfer.setData('text/plain', String(index));
        },

        onPhotoDragOver(index, event) {
            event.dataTransfer.dropEffect = 'move';
            this.dragOverPhotoIndex = index;
        },

        onPhotoDrop(targetIndex, event) {
            event.preventDefault();

            const sourceIndex = this.draggedPhotoIndex;
            if (sourceIndex === null || sourceIndex === targetIndex) {
                this._clearDragState();
                return;
            }

            if (!this.photoModalItem || !this.photoModalItem.photos) {
                this._clearDragState();
                return;
            }

            // Reorder the photos array
            const photos = this.photoModalItem.photos;
            const movedPhoto = photos.splice(sourceIndex, 1)[0];
            photos.splice(targetIndex, 0, movedPhoto);

            // Update sortOrder on all photos
            this._updatePhotoSortOrder();
            this._clearDragState();
        },

        onPhotoDragEnd() {
            this._clearDragState();
        },

        /**
         * Update sortOrder field on all photos to match their array position.
         * @private
         */
        _updatePhotoSortOrder() {
            if (!this.photoModalItem || !this.photoModalItem.photos) {
                return;
            }
            this.photoModalItem.photos.forEach((photo, idx) => {
                photo.sortOrder = idx;
            });
        },

        /**
         * Clear drag-and-drop visual state.
         * @private
         */
        _clearDragState() {
            this.draggedPhotoIndex = null;
            this.dragOverPhotoIndex = null;
        },

        /**
         * Load media folder ID for observations
         */
        async loadMediaFolderId() {
            this.observationMediaFolderId = await this.getMediaFolderId(
                'MiniwaldExplorer/Observations'
            );
        },
    },
});

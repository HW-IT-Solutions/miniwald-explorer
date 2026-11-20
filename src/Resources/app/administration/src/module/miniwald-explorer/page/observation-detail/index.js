import template from './observation-detail.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('miniwald-observation-detail', {
    template,

    inject: ['repositoryFactory', 'acl'],

    mixins: [Mixin.getByName('notification'), Mixin.getByName('placeholder')],

    props: {
        observationId: {
            type: String,
            required: false,
            default: null,
        },
    },

    data() {
        return {
            observation: null,
            isLoading: false,
            processSuccess: false,
            showDeleteModal: false,
            intendedType: null,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(this.identifier),
        };
    },

    computed: {
        identifier() {
            if (!this.observation) {
                return '';
            }
            const date = this.observation.observedAt
                ? new Date(this.observation.observedAt).toLocaleDateString(
                      'de-DE',
                      { timeZone: 'Europe/Berlin' }
                  )
                : '';
            return `${this.observationTypeLabel} — ${date}`;
        },

        observationRepository() {
            return this.repositoryFactory.create(
                'miniwald_explorer_observation'
            );
        },

        observationType() {
            if (!this.observation) {
                return this.intendedType || 'unknown';
            }
            // Persisted type (singular) takes precedence; UI keys are plural
            const typeMap = {
                animal: 'animals',
                plant: 'plants',
                ground: 'ground',
            };
            if (this.observation.type && typeMap[this.observation.type]) {
                return typeMap[this.observation.type];
            }
            // Legacy fallback: derive from loaded associations
            if (this.observation.items && this.observation.items.length > 0) {
                return 'animals';
            }
            if (
                this.observation.plantMeasurements &&
                this.observation.plantMeasurements.length > 0
            ) {
                return 'plants';
            }
            if (
                this.observation.groundMeasurements &&
                this.observation.groundMeasurements.length > 0
            ) {
                return 'ground';
            }
            // Fall back to intended type (set during create or load)
            if (this.intendedType) {
                return this.intendedType;
            }
            return 'unknown';
        },

        observationTypeLabel() {
            return this.$tc(
                `miniwald-explorer.observation.list.type.${this.observationType}`
            );
        },

        observationTypeVariant() {
            const variants = {
                animals: 'info',
                plants: 'success',
                ground: 'warning',
                unknown: 'neutral',
            };
            return variants[this.observationType] || 'neutral';
        },

        isCreateMode() {
            return this.$route.params.id === 'create';
        },

        entityIsLoading() {
            return this.isLoading || this.observation == null;
        },

        tooltipSave() {
            const systemKey = this.$device.getSystemKey();
            return {
                message: `${systemKey} + S`,
                appearance: 'light',
            };
        },

        tooltipCancel() {
            return {
                message: 'ESC',
                appearance: 'light',
            };
        },

        loadCriteria() {
            const criteria = new Criteria(1, 1);
            criteria.addAssociation('customer');
            criteria.addAssociation('miniwald');
            criteria.addAssociation('items.species.speciesCategory');
            criteria.addAssociation('items.subspecies');
            criteria.addAssociation('items.photos.media');
            criteria.addAssociation('groundMeasurements');
            criteria.addAssociation('plantMeasurements.plant');
            return criteria;
        },
    },

    watch: {
        '$route.params.id'() {
            this.createdComponent();
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            if (this.$route.params.id && this.$route.params.id !== 'create') {
                this.loadEntityData();
            } else {
                this.createNewObservation();
            }
        },

        createNewObservation() {
            const type = this.$route.query.type || 'animals';
            this.intendedType = type;

            this.observation = this.observationRepository.create(
                Shopware.Context.api
            );
            this.observation.status = 'draft';
            this.observation.observedAt = new Date().toISOString();
            // Persist the type explicitly (entity uses singular values)
            this.observation.type =
                { animals: 'animal', plants: 'plant', ground: 'ground' }[
                    type
                ] || null;

            // Initialize type-specific child entities
            if (type === 'ground') {
                this.observation.groundMeasurements = [];
            } else if (type === 'plants') {
                this.observation.plantMeasurements = [];
            }
            // For animals, items start as empty array — user adds them via the card
        },

        loadEntityData() {
            this.isLoading = true;

            this.observationRepository
                .get(
                    this.$route.params.id,
                    Shopware.Context.api,
                    this.loadCriteria
                )
                .then((entity) => {
                    this.observation = entity;
                    // Prefer the persisted type; derive from associations
                    // only for legacy rows without one
                    const typeMap = {
                        animal: 'animals',
                        plant: 'plants',
                        ground: 'ground',
                    };
                    if (entity.type && typeMap[entity.type]) {
                        this.intendedType = typeMap[entity.type];
                    } else if (entity.items && entity.items.length > 0) {
                        this.intendedType = 'animals';
                    } else if (
                        entity.plantMeasurements &&
                        entity.plantMeasurements.length > 0
                    ) {
                        this.intendedType = 'plants';
                    } else if (
                        entity.groundMeasurements &&
                        entity.groundMeasurements.length > 0
                    ) {
                        this.intendedType = 'ground';
                    }
                    this.isLoading = false;
                })
                .catch(() => {
                    this.isLoading = false;
                    this.createNotificationError({
                        message: this.$tc(
                            'miniwald-explorer.observation.detail.errorLoadFailed'
                        ),
                    });
                });
        },

        onClickSave() {
            // Block save if there are duplicate species/subspecies combinations
            if (this.hasDuplicateItems()) {
                this.createNotificationError({
                    message: this.$tc(
                        'miniwald-explorer.observation.detail.errorDuplicateItems'
                    ),
                });
                return;
            }

            // Animal observations require a species category — without it,
            // custom entries cannot be categorized in exports
            if (
                this.observationType === 'animals' &&
                !this.observation.speciesCategoryId
            ) {
                this.createNotificationError({
                    message: this.$tc(
                        'miniwald-explorer.observation.detail.errorMissingSpeciesCategory'
                    ),
                });
                return;
            }

            this.isLoading = true;

            this.observationRepository
                .save(this.observation, Shopware.Context.api)
                .then(() => {
                    this.isLoading = false;
                    this.processSuccess = true;

                    if (this.isCreateMode) {
                        this.$router.push({
                            name: 'miniwald.explorer.observation.detail',
                            params: { id: this.observation.id },
                        });
                    } else {
                        this.loadEntityData();
                    }
                })
                .catch((error) => {
                    this.isLoading = false;
                    this.createNotificationError({
                        message: this.$tc(
                            'miniwald-explorer.observation.detail.errorSaveFailed'
                        ),
                    });
                    console.error('Save observation failed:', error);
                });
        },

        onClickCancel() {
            this.$router.push({ name: 'miniwald.explorer.observation.list' });
        },

        onClickDelete() {
            this.showDeleteModal = true;
        },

        onConfirmDelete() {
            this.showDeleteModal = false;
            this.isLoading = true;

            this.observationRepository
                .delete(this.observation.id, Shopware.Context.api)
                .then(() => {
                    this.isLoading = false;
                    this.$router.push({
                        name: 'miniwald.explorer.observation.list',
                    });
                })
                .catch(() => {
                    this.isLoading = false;
                    this.createNotificationError({
                        message: this.$tc(
                            'miniwald-explorer.observation.detail.errorDeleteFailed'
                        ),
                    });
                });
        },

        onCloseDeleteModal() {
            this.showDeleteModal = false;
        },

        saveFinish() {
            this.processSuccess = false;
        },

        /**
         * Check if the observation has duplicate species/subspecies combinations
         * in its animal observation items.
         */
        hasDuplicateItems() {
            const items = this.observation && this.observation.items;
            if (!items || items.length < 2) {
                return false;
            }

            const seen = new Set();
            for (const item of items) {
                if (!item.speciesId) {
                    continue;
                }
                const key = `${item.speciesId}|${item.subspeciesId || ''}`;
                if (seen.has(key)) {
                    return true;
                }
                seen.add(key);
            }
            return false;
        },
    },
});

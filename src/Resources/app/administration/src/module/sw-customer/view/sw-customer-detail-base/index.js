import template from './sw-customer-detail-base.html.twig';

const { Criteria } = Shopware.Data;

/**
 * Override sw-customer-detail-base to:
 * - Remove default addresses card
 * - Add observation counts card (total + per type)
 */
Shopware.Component.override('sw-customer-detail-base', {
    template,

    inject: ['repositoryFactory'],

    data() {
        return {
            observationCounts: {
                total: 0,
                animals: 0,
                plants: 0,
                ground: 0,
            },
            observationCountsLoading: false,
        };
    },

    computed: {
        observationRepository() {
            return this.repositoryFactory.create(
                'miniwald_explorer_observation'
            );
        },
    },

    watch: {
        customer: {
            immediate: true,
            handler(customer) {
                if (customer && customer.id) {
                    this.loadObservationCounts();
                }
            },
        },
    },

    methods: {
        async loadObservationCounts() {
            if (!this.customer || !this.customer.id) {
                return;
            }

            this.observationCountsLoading = true;

            try {
                // Load total count
                const totalCriteria = new Criteria(1, 1);
                totalCriteria.addFilter(
                    Criteria.equals('customerId', this.customer.id)
                );
                totalCriteria.setLimit(1);

                const totalResult =
                    await this.observationRepository.search(totalCriteria);
                this.observationCounts.total = totalResult.total || 0;

                // Load observations with associations to determine types
                const detailCriteria = new Criteria(1, 500);
                detailCriteria.addFilter(
                    Criteria.equals('customerId', this.customer.id)
                );
                detailCriteria.addAssociation('items');
                detailCriteria.addAssociation('groundMeasurements');
                detailCriteria.addAssociation('plantMeasurements');

                const observations =
                    await this.observationRepository.search(detailCriteria);

                let animals = 0;
                let plants = 0;
                let ground = 0;

                observations.forEach((obs) => {
                    // Persisted type takes precedence; associations are the
                    // legacy fallback for rows without one
                    if (
                        obs.type === 'animal' ||
                        (!obs.type && obs.items && obs.items.length > 0)
                    ) {
                        animals++;
                    }
                    if (
                        obs.type === 'plant' ||
                        (!obs.type &&
                            obs.plantMeasurements &&
                            obs.plantMeasurements.length > 0)
                    ) {
                        plants++;
                    }
                    if (
                        obs.type === 'ground' ||
                        (!obs.type &&
                            obs.groundMeasurements &&
                            obs.groundMeasurements.length > 0)
                    ) {
                        ground++;
                    }
                });

                this.observationCounts.animals = animals;
                this.observationCounts.plants = plants;
                this.observationCounts.ground = ground;
            } catch (error) {
                console.error('Failed to load observation counts:', error);
            } finally {
                this.observationCountsLoading = false;
            }
        },
    },
});

import template from './sw-customer-detail-observations.html.twig';

const { Criteria } = Shopware.Data;
const { Component, Mixin } = Shopware;

/**
 * New view component: Observations tab on customer detail page.
 *
 * Shows a list of observations filtered by the current customer,
 * with a non-removable customer filter and no observer column.
 */
Component.register('sw-customer-detail-observations', {
    template,

    inject: ['repositoryFactory'],

    mixins: [Mixin.getByName('listing')],

    props: {
        customer: {
            type: Object,
            required: true,
        },
        customerEditMode: {
            type: Boolean,
            required: false,
            default: false,
        },
    },

    data() {
        return {
            observations: null,
            isLoading: false,
            sortBy: 'observedAt',
            sortDirection: 'DESC',
        };
    },

    computed: {
        observationRepository() {
            return this.repositoryFactory.create(
                'miniwald_explorer_observation'
            );
        },

        observationColumns() {
            return [
                {
                    property: 'observedAt',
                    dataIndex: 'observedAt',
                    label: this.$tc(
                        'miniwald-explorer.observation.list.columnDate'
                    ),
                    allowResize: true,
                    primary: true,
                },
                {
                    property: 'type',
                    label: this.$tc(
                        'miniwald-explorer.observation.list.columnType'
                    ),
                    sortable: false,
                    allowResize: true,
                },
                {
                    property: 'miniwald.name',
                    label: this.$tc(
                        'miniwald-explorer.observation.list.columnLocation'
                    ),
                    sortable: false,
                    allowResize: true,
                },
                {
                    property: 'status',
                    dataIndex: 'status',
                    label: this.$tc(
                        'miniwald-explorer.observation.list.columnStatus'
                    ),
                    allowResize: true,
                    align: 'center',
                    width: '120px',
                },
            ];
        },

        defaultCriteria() {
            const criteria = new Criteria(this.page, this.limit);

            criteria.addFilter(Criteria.equals('customerId', this.customer.id));
            criteria.addSorting(Criteria.sort(this.sortBy, this.sortDirection));
            criteria.addAssociation('miniwald');
            criteria.addAssociation('items');
            criteria.addAssociation('groundMeasurements');
            criteria.addAssociation('plantMeasurements');

            if (this.term) {
                criteria.setTerm(this.term);
            }

            return criteria;
        },
    },

    watch: {
        customer() {
            this.getList();
        },
    },

    created() {
        this.getList();
    },

    methods: {
        async getList() {
            this.isLoading = true;

            try {
                const result = await this.observationRepository.search(
                    this.defaultCriteria
                );
                this.observations = result;
                this.total = result.total || 0;
            } catch (error) {
                console.error('Failed to load observations:', error);
            } finally {
                this.isLoading = false;
            }
        },

        getObservationType(item) {
            // Persisted type takes precedence (entity values are singular)
            const typeMap = {
                animal: 'animals',
                plant: 'plants',
                ground: 'ground',
            };
            if (item.type && typeMap[item.type]) {
                return typeMap[item.type];
            }
            // Legacy fallback: derive from loaded associations
            if (item.items && item.items.length > 0) {
                return 'animals';
            }
            if (item.plantMeasurements && item.plantMeasurements.length > 0) {
                return 'plants';
            }
            if (item.groundMeasurements && item.groundMeasurements.length > 0) {
                return 'ground';
            }
            return 'unknown';
        },

        getObservationTypeLabel(item) {
            const type = this.getObservationType(item);
            return this.$tc(`miniwald-explorer.observation.list.type.${type}`);
        },

        getStatusVariant(status) {
            const variants = {
                draft: 'info',
                submitted: 'warning',
                verified: 'success',
                rejected: 'danger',
            };
            return variants[status] || 'neutral';
        },

        getStatusLabel(status) {
            if (!status) {
                return '';
            }
            return this.$tc(
                `miniwald-explorer.observation.list.status.${status}`
            );
        },
    },
});

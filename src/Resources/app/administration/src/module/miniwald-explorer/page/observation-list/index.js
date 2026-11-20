import template from './observation-list.html.twig';
import { miniwaldListMixin } from '../../mixin/miniwald-list.mixin';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('miniwald-observation-list', {
    template,

    mixins: [
        Mixin.getByName('listing'),
        Mixin.getByName('notification'),
        miniwaldListMixin({
            entityName: 'miniwald_explorer_observation',
            defaultSortField: 'observedAt',
            defaultSortDirection: 'DESC',
            associations: [
                'customer',
                'miniwald',
                'items',
                'groundMeasurements',
                'plantMeasurements',
            ],
        }),
    ],

    data() {
        return {
            selectedItems: {},
            isBulkLoading: false,
            showFilters: false,
            filterDateFrom: null,
            filterDateTo: null,
            filterType: null,
            filterCustomerId: null,
            filterMiniwaldId: null,
            filterStatus: null,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        columns() {
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
                    property: 'customer.lastName',
                    label: this.$tc(
                        'miniwald-explorer.observation.list.columnObserver'
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

        selectedItemCount() {
            return Object.keys(this.selectedItems).length;
        },

        typeFilterOptions() {
            return [
                {
                    value: 'animals',
                    label: this.$tc(
                        'miniwald-explorer.observation.list.type.animals'
                    ),
                },
                {
                    value: 'plants',
                    label: this.$tc(
                        'miniwald-explorer.observation.list.type.plants'
                    ),
                },
                {
                    value: 'ground',
                    label: this.$tc(
                        'miniwald-explorer.observation.list.type.ground'
                    ),
                },
            ];
        },

        statusFilterOptions() {
            return [
                {
                    value: 'draft',
                    label: this.$tc(
                        'miniwald-explorer.observation.list.status.draft'
                    ),
                },
                {
                    value: 'submitted',
                    label: this.$tc(
                        'miniwald-explorer.observation.list.status.submitted'
                    ),
                },
                {
                    value: 'verified',
                    label: this.$tc(
                        'miniwald-explorer.observation.list.status.verified'
                    ),
                },
                {
                    value: 'rejected',
                    label: this.$tc(
                        'miniwald-explorer.observation.list.status.rejected'
                    ),
                },
            ];
        },

        activeFilterCount() {
            let count = 0;
            if (this.filterDateFrom) count++;
            if (this.filterDateTo) count++;
            if (this.filterType) count++;
            if (this.filterCustomerId) count++;
            if (this.filterMiniwaldId) count++;
            if (this.filterStatus) count++;
            return count;
        },
    },

    methods: {
        /**
         * Override mixin's getList to apply filters to the criteria.
         */
        getList() {
            this.isLoading = true;

            const criteria = new Criteria(this.page, this.limit);
            criteria.addSorting(Criteria.sort('observedAt', 'DESC'));

            // Associations
            criteria.addAssociation('customer');
            criteria.addAssociation('miniwald');
            criteria.addAssociation('items');
            criteria.addAssociation('groundMeasurements');
            criteria.addAssociation('plantMeasurements');

            // Apply filters
            if (this.filterDateFrom) {
                criteria.addFilter(
                    Criteria.range('observedAt', { gte: this.filterDateFrom })
                );
            }
            if (this.filterDateTo) {
                criteria.addFilter(
                    Criteria.range('observedAt', { lte: this.filterDateTo })
                );
            }
            if (this.filterCustomerId) {
                criteria.addFilter(
                    Criteria.equals('customerId', this.filterCustomerId)
                );
            }
            if (this.filterMiniwaldId) {
                criteria.addFilter(
                    Criteria.equals('miniwaldId', this.filterMiniwaldId)
                );
            }
            if (this.filterStatus) {
                criteria.addFilter(
                    Criteria.equals('status', this.filterStatus)
                );
            }
            if (this.filterType) {
                this.applyTypeFilter(criteria, this.filterType);
            }

            // Apply search term from listing mixin
            if (this.term) {
                criteria.setTerm(this.term);
            }

            this.entityRepository
                .search(criteria, Shopware.Context.api)
                .then((result) => {
                    this.items = result;
                    this.total = result.total;
                    this.isLoading = false;
                })
                .catch(() => {
                    this.isLoading = false;
                });
        },

        applyTypeFilter(criteria, type) {
            // Filter on the persisted type column (entity uses singular values)
            const typeMap = {
                animals: 'animal',
                plants: 'plant',
                ground: 'ground',
            };
            if (typeMap[type]) {
                criteria.addFilter(Criteria.equals('type', typeMap[type]));
            }
        },

        onToggleFilters() {
            this.showFilters = !this.showFilters;
        },

        onResetFilters() {
            this.filterDateFrom = null;
            this.filterDateTo = null;
            this.filterType = null;
            this.filterCustomerId = null;
            this.filterMiniwaldId = null;
            this.filterStatus = null;
            this.getList();
        },

        onFilterChange() {
            // Reset to page 1 when filters change
            this.page = 1;
            this.getList();
        },

        onFilterByCustomer(customerId) {
            if (!customerId) {
                return;
            }
            this.filterCustomerId = customerId;
            this.showFilters = true;
            this.page = 1;
            this.getList();
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

        getCustomerName(item) {
            if (!item.customer) {
                return this.$tc(
                    'miniwald-explorer.observation.list.unknownObserver'
                );
            }
            const { firstName, company } = item.customer;
            if (company) {
                return company;
            }
            return firstName || '';
        },

        onCreateObservation(type) {
            this.$router.push({
                name: 'miniwald.explorer.observation.detail',
                params: { id: 'create' },
                query: { type },
            });
        },

        onSelectionChange(selection) {
            this.selectedItems = selection;
        },

        onBulkSetStatus(newStatus) {
            const selectedEntities = Object.values(this.selectedItems);
            if (selectedEntities.length === 0) {
                return;
            }

            this.isBulkLoading = true;

            const promises = selectedEntities.map((entity) => {
                entity.status = newStatus;
                return this.entityRepository.save(entity, Shopware.Context.api);
            });

            Promise.all(promises)
                .then(() => {
                    this.isBulkLoading = false;
                    this.createNotificationSuccess({
                        message: this.$tc(
                            'miniwald-explorer.observation.list.bulkStatusSuccess',
                            selectedEntities.length,
                            {
                                count: selectedEntities.length,
                                status: this.getStatusLabel(newStatus),
                            }
                        ),
                    });
                    this.selectedItems = {};
                    this.getList();
                })
                .catch(() => {
                    this.isBulkLoading = false;
                    this.createNotificationError({
                        message: this.$tc(
                            'miniwald-explorer.observation.list.bulkStatusError'
                        ),
                    });
                });
        },

        onBulkVerify() {
            this.onBulkSetStatus('verified');
        },

        onBulkReject() {
            this.onBulkSetStatus('rejected');
        },

        onBulkSetDraft() {
            this.onBulkSetStatus('draft');
        },

        onDeleteItemFinish() {
            this.createNotificationSuccess({
                message: this.$tc(
                    'miniwald-explorer.observation.list.deleteSuccess'
                ),
            });
            this.getList();
        },

        onBulkDeleteFinish() {
            this.createNotificationSuccess({
                message: this.$tc(
                    'miniwald-explorer.observation.list.bulkDeleteSuccess'
                ),
            });
            this.getList();
        },
    },
});

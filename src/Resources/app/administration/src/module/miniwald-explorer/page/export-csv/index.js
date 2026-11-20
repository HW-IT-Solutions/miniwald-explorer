import template from './export-csv.html.twig';

const { Component, Mixin } = Shopware;

Component.register('miniwald-export-csv', {
    template,

    inject: ['repositoryFactory'],

    mixins: [Mixin.getByName('notification')],

    data() {
        return {
            isExporting: false,
            filterDateFrom: null,
            filterDateTo: null,
            filterType: null,
            filterCustomerId: null,
            filterMiniwaldId: null,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        typeOptions() {
            return [
                {
                    value: '',
                    label: this.$tc('miniwald-explorer.export.allTypes'),
                },
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

        customerRepository() {
            return this.repositoryFactory.create('customer');
        },

        miniwaldRepository() {
            return this.repositoryFactory.create('miniwald_explorer_miniwald');
        },

        customerCriteria() {
            const criteria = new Shopware.Data.Criteria(1, 500);
            criteria.addSorting(
                Shopware.Data.Criteria.sort('firstName', 'ASC')
            );
            return criteria;
        },

        miniwaldCriteria() {
            const criteria = new Shopware.Data.Criteria(1, 100);
            criteria.addSorting(Shopware.Data.Criteria.sort('name', 'ASC'));
            return criteria;
        },

        activeFilterCount() {
            let count = 0;
            if (this.filterDateFrom) count++;
            if (this.filterDateTo) count++;
            if (this.filterType) count++;
            if (this.filterCustomerId) count++;
            if (this.filterMiniwaldId) count++;
            return count;
        },

        exportUrl() {
            const params = new URLSearchParams();

            if (this.filterDateFrom)
                params.set('dateFrom', this.filterDateFrom);
            if (this.filterDateTo) params.set('dateTo', this.filterDateTo);
            if (this.filterType) params.set('type', this.filterType);
            if (this.filterCustomerId)
                params.set('customerId', this.filterCustomerId);
            if (this.filterMiniwaldId)
                params.set('miniwaldId', this.filterMiniwaldId);

            const queryString = params.toString();
            return `/api/_action/miniwald/export/csv${queryString ? '?' + queryString : ''}`;
        },
    },

    methods: {
        onExport() {
            this.isExporting = true;

            // Use the Shopware API client to get the auth headers, then trigger download
            const headers = {
                Authorization: `Bearer ${Shopware.Context.api.authToken.access}`,
                Accept: 'text/csv',
            };

            fetch(this.exportUrl, { headers })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(`Export failed: ${response.status}`);
                    }
                    return response.blob();
                })
                .then((blob) => {
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = `miniwald-observations-${new Date().toISOString().slice(0, 10)}.csv`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);

                    this.isExporting = false;
                    this.createNotificationSuccess({
                        message: this.$tc(
                            'miniwald-explorer.export.csv.successMessage'
                        ),
                    });
                })
                .catch((error) => {
                    this.isExporting = false;
                    this.createNotificationError({
                        message: this.$tc(
                            'miniwald-explorer.export.csv.errorMessage'
                        ),
                    });
                    console.error('CSV export error:', error);
                });
        },

        onResetFilters() {
            this.filterDateFrom = null;
            this.filterDateTo = null;
            this.filterType = null;
            this.filterCustomerId = null;
            this.filterMiniwaldId = null;
        },
    },
});

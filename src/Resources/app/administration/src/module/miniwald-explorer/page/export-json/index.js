import template from './export-json.html.twig';

const { Component, Mixin } = Shopware;

Component.register('miniwald-export-json', {
    template,

    inject: ['repositoryFactory'],

    mixins: [Mixin.getByName('notification')],

    data() {
        return {
            isExporting: false,
            isPreviewing: false,
            previewData: null,
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

        queryParams() {
            const params = new URLSearchParams();

            if (this.filterDateFrom)
                params.set('dateFrom', this.filterDateFrom);
            if (this.filterDateTo) params.set('dateTo', this.filterDateTo);
            if (this.filterType) params.set('type', this.filterType);
            if (this.filterCustomerId)
                params.set('customerId', this.filterCustomerId);
            if (this.filterMiniwaldId)
                params.set('miniwaldId', this.filterMiniwaldId);

            return params;
        },

        previewUrl() {
            const params = new URLSearchParams(this.queryParams);
            params.set('page', '1');
            params.set('limit', '5');
            const queryString = params.toString();
            return `/api/_action/miniwald/export/json${queryString ? '?' + queryString : ''}`;
        },

        exportFileUrl() {
            const queryString = this.queryParams.toString();
            return `/api/_action/miniwald/export/json-file${queryString ? '?' + queryString : ''}`;
        },

        formattedPreview() {
            if (!this.previewData) {
                return '';
            }
            return JSON.stringify(this.previewData, null, 2);
        },
    },

    methods: {
        getAuthHeaders() {
            return {
                Authorization: `Bearer ${Shopware.Context.api.authToken.access}`,
                Accept: 'application/json',
            };
        },

        onPreview() {
            this.isPreviewing = true;
            this.previewData = null;

            fetch(this.previewUrl, { headers: this.getAuthHeaders() })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(`Preview failed: ${response.status}`);
                    }
                    return response.json();
                })
                .then((data) => {
                    this.previewData = data;
                    this.isPreviewing = false;
                })
                .catch((error) => {
                    this.isPreviewing = false;
                    this.createNotificationError({
                        message: this.$tc(
                            'miniwald-explorer.export.json.errorPreview'
                        ),
                    });
                    console.error('JSON preview error:', error);
                });
        },

        onExportFile() {
            this.isExporting = true;

            fetch(this.exportFileUrl, {
                headers: {
                    Authorization: `Bearer ${Shopware.Context.api.authToken.access}`,
                    Accept: 'application/json',
                },
            })
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
                    link.download = `miniwald-observations-${new Date().toISOString().slice(0, 10)}.json`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);

                    this.isExporting = false;
                    this.createNotificationSuccess({
                        message: this.$tc(
                            'miniwald-explorer.export.json.successMessage'
                        ),
                    });
                })
                .catch((error) => {
                    this.isExporting = false;
                    this.createNotificationError({
                        message: this.$tc(
                            'miniwald-explorer.export.json.errorMessage'
                        ),
                    });
                    console.error('JSON export error:', error);
                });
        },

        onResetFilters() {
            this.filterDateFrom = null;
            this.filterDateTo = null;
            this.filterType = null;
            this.filterCustomerId = null;
            this.filterMiniwaldId = null;
            this.previewData = null;
        },
    },
});

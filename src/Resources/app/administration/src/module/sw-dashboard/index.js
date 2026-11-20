import template from './sw-dashboard.html.twig';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

/**
 * Override the Shopware default dashboard (sw-dashboard-index) to show
 * InReNa observation data instead of e-commerce metrics.
 */
Component.override('sw-dashboard-index', {
    template,

    inject: ['repositoryFactory'],

    data() {
        return {
            dashboardIsLoading: true,
            totalObservations: 0,
            pendingCount: 0,
            animalCount: 0,
            plantCount: 0,
            groundCount: 0,
            recentObservations: [],
            observationChartSeries: [],
            observationChartOptions: {},
            colors: [],
            observationDateRange: '30Days',
        };
    },

    computed: {
        observationRepository() {
            return this.repositoryFactory.create(
                'miniwald_explorer_observation'
            );
        },

        statsRows() {
            return [
                {
                    label: this.$tc('miniwald-explorer.dashboard.statTotal'),
                    value: this.totalObservations,
                },
                {
                    label: this.$tc('miniwald-explorer.dashboard.typeAnimals'),
                    value: this.animalCount,
                },
                {
                    label: this.$tc('miniwald-explorer.dashboard.typePlants'),
                    value: this.plantCount,
                },
                {
                    label: this.$tc('miniwald-explorer.dashboard.typeGround'),
                    value: this.groundCount,
                },
            ];
        },
    },

    created() {
        this.loadDashboardData();
    },

    methods: {
        async loadDashboardData() {
            this.dashboardIsLoading = true;
            try {
                await Promise.all([
                    this.loadCounts(),
                    this.loadRecentObservations(),
                    this.loadChartData(this.observationDateRange),
                ]);
            } finally {
                this.dashboardIsLoading = false;
            }
        },

        async loadCounts() {
            const totalCriteria = new Criteria(1, 1);
            const totalResult = await this.observationRepository.search(
                totalCriteria,
                Shopware.Context.api
            );
            this.totalObservations = totalResult.total;

            const pendingCriteria = new Criteria(1, 1);
            pendingCriteria.addFilter(Criteria.equals('status', 'submitted'));
            const pendingResult = await this.observationRepository.search(
                pendingCriteria,
                Shopware.Context.api
            );
            this.pendingCount = pendingResult.total;

            const animalCriteria = new Criteria(1, 1);
            animalCriteria.addFilter(
                Criteria.not('and', [Criteria.equals('items.id', null)])
            );
            const animalResult = await this.observationRepository.search(
                animalCriteria,
                Shopware.Context.api
            );
            this.animalCount = animalResult.total;

            const plantCriteria = new Criteria(1, 1);
            plantCriteria.addFilter(
                Criteria.not('and', [
                    Criteria.equals('plantMeasurements.id', null),
                ])
            );
            const plantResult = await this.observationRepository.search(
                plantCriteria,
                Shopware.Context.api
            );
            this.plantCount = plantResult.total;

            const groundCriteria = new Criteria(1, 1);
            groundCriteria.addFilter(
                Criteria.not('and', [
                    Criteria.equals('groundMeasurements.id', null),
                ])
            );
            const groundResult = await this.observationRepository.search(
                groundCriteria,
                Shopware.Context.api
            );
            this.groundCount = groundResult.total;
        },

        async loadRecentObservations() {
            const criteria = new Criteria(1, 10);
            criteria.addSorting(Criteria.sort('observedAt', 'DESC'));
            criteria.addAssociation('customer');
            criteria.addAssociation('miniwald');
            criteria.addAssociation('items');
            criteria.addAssociation('groundMeasurements');
            criteria.addAssociation('plantMeasurements');

            this.recentObservations = await this.observationRepository.search(
                criteria,
                Shopware.Context.api
            );
        },

        async loadChartData(range) {
            const daysMap = {
                '30Days': 30,
                '14Days': 14,
                '7Days': 7,
                '24Hours': 1,
                yesterday: 2,
            };
            const days = daysMap[range] || 30;

            const since = new Date();
            since.setDate(since.getDate() - days);

            const criteria = new Criteria(1, 500);
            criteria.addFilter(
                Criteria.range('observedAt', { gte: since.toISOString() })
            );
            criteria.addSorting(Criteria.sort('observedAt', 'ASC'));
            criteria.addAssociation('items');
            criteria.addAssociation('groundMeasurements');
            criteria.addAssociation('plantMeasurements');

            const result = await this.observationRepository.search(
                criteria,
                Shopware.Context.api
            );

            const buckets = this.buildBuckets(result, days);

            this.observationChartSeries = [
                {
                    name: this.$tc('miniwald-explorer.dashboard.typeAnimals'),
                    data: buckets.map((b) => ({ x: b.label, y: b.animals })),
                },
                {
                    name: this.$tc('miniwald-explorer.dashboard.typePlants'),
                    data: buckets.map((b) => ({ x: b.label, y: b.plants })),
                },
                {
                    name: this.$tc('miniwald-explorer.dashboard.typeGround'),
                    data: buckets.map((b) => ({ x: b.label, y: b.ground })),
                },
            ];

            // Color: Animals=blue, Plants=green, Ground=orange
            this.colors = ['#2196F3', '#4CAF50', '#FF9800'];
            this.observationChartOptions = {
                xaxis: {
                    type: 'category',
                    categories: buckets.map((b) => b.label),
                },
                yaxis: {
                    min: 0,
                    tickAmount: 4,
                    labels: {
                        formatter: (val) => Math.round(val),
                    },
                },
                colors: this.colors, // for marker? -> does not work
                stroke: {
                    colors: this.colors, // for lines
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                },
                dataLabels: { enabled: false },
                legend: { position: 'top' },
            };
        },

        buildBuckets(observations, days) {
            // Use weekly buckets for 30/14 days, daily for 7/1
            const useWeekly = days >= 14;
            const bucketCount = useWeekly ? Math.ceil(days / 7) : days;
            const bucketDays = useWeekly ? 7 : 1;

            const now = new Date();
            const buckets = [];

            for (let i = bucketCount - 1; i >= 0; i--) {
                const end = new Date(now);
                end.setDate(end.getDate() - i * bucketDays);
                const start = new Date(end);
                start.setDate(start.getDate() - bucketDays);

                buckets.push({
                    label: this.formatBucketLabel(start, end, useWeekly),
                    start,
                    end,
                    animals: 0,
                    plants: 0,
                    ground: 0,
                });
            }

            observations.forEach((obs) => {
                const date = obs.observedAt ? new Date(obs.observedAt) : null;
                if (!date) return;
                const type = this.getObservationType(obs);
                const bucket = buckets.find(
                    (b) => date >= b.start && date < b.end
                );
                if (bucket) bucket[type]++;
            });

            return buckets;
        },

        formatBucketLabel(start, end, weekly) {
            const fmt = (d) =>
                `${d.getDate().toString().padStart(2, '0')}.${(d.getMonth() + 1).toString().padStart(2, '0')}`;
            return weekly ? `${fmt(start)}–${fmt(end)}` : fmt(end);
        },

        onObservationRangeUpdate(range) {
            this.observationDateRange = range;
            this.loadChartData(range);
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
            if (item.items && item.items.length > 0) return 'animals';
            if (item.plantMeasurements && item.plantMeasurements.length > 0)
                return 'plants';
            if (item.groundMeasurements && item.groundMeasurements.length > 0)
                return 'ground';
            return 'animals';
        },

        getObservationTypeLabel(item) {
            const type = this.getObservationType(item);
            return this.$tc(
                `miniwald-explorer.dashboard.type${type.charAt(0).toUpperCase() + type.slice(1)}`
            );
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
            if (!status) return '';
            return this.$tc(
                `miniwald-explorer.observation.list.status.${status}`
            );
        },

        getCustomerName(item) {
            if (!item.customer)
                return this.$tc(
                    'miniwald-explorer.observation.list.unknownObserver'
                );
            const { firstName, company } = item.customer;
            if (company) return company;
            return firstName || '';
        },

        formatDate(date) {
            if (!date) return '–';
            return new Intl.DateTimeFormat('de-DE', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                timeZone: 'Europe/Berlin',
            }).format(new Date(date));
        },

        onGoToPendingObservations() {
            this.$router.push({ name: 'miniwald.explorer.observation.list' });
        },

        onOpenObservation(id) {
            this.$router.push({
                name: 'miniwald.explorer.observation.detail',
                params: { id },
            });
        },
    },
});

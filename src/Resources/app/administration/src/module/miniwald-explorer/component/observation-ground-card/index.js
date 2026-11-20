import template from './observation-ground-card.html.twig';

const { Component } = Shopware;

const LOCATION_TYPES = [
    {
        value: 'wood_center',
        labelKey:
            'miniwald-explorer.observation.detail.ground.locationTypeWoodCenter',
    },
    {
        value: 'wood_edge',
        labelKey:
            'miniwald-explorer.observation.detail.ground.locationTypeWoodEdge',
    },
    {
        value: 'street_curb',
        labelKey:
            'miniwald-explorer.observation.detail.ground.locationTypeStreetCurb',
    },
];

Component.register('miniwald-observation-ground-card', {
    template,

    inject: ['repositoryFactory'],

    props: {
        observation: {
            type: Object,
            required: true,
        },
    },

    computed: {
        groundMeasurementRepository() {
            return this.repositoryFactory.create(
                'miniwald_explorer_ground_measurement'
            );
        },

        measurements() {
            return this.observation.groundMeasurements || [];
        },

        usedLocationTypes() {
            return this.measurements.map((m) => m.locationType);
        },

        availableLocationTypes() {
            return this.locationTypeOptions.filter(
                (t) => !this.usedLocationTypes.includes(t.value)
            );
        },

        canAddMore() {
            return (
                this.measurements.length < 3 &&
                this.availableLocationTypes.length > 0
            );
        },

        lightConditionsOptions() {
            return [
                {
                    value: 'sunny',
                    label: this.$tc(
                        'miniwald-explorer.observation.detail.ground.lightConditionsSunny'
                    ),
                },
                {
                    value: 'shady',
                    label: this.$tc(
                        'miniwald-explorer.observation.detail.ground.lightConditionsShady'
                    ),
                },
            ];
        },

        locationTypeOptions() {
            return LOCATION_TYPES.map((t) => ({
                value: t.value,
                label: this.$tc(t.labelKey),
            }));
        },
    },

    methods: {
        getLocationTypeLabel(locationType) {
            const found = LOCATION_TYPES.find((t) => t.value === locationType);
            return found ? this.$tc(found.labelKey) : locationType;
        },

        onAddMeasurement(locationType) {
            const newMeasurement = this.groundMeasurementRepository.create(
                Shopware.Context.api
            );
            newMeasurement.observationId = this.observation.id;
            newMeasurement.locationType = locationType;

            if (!this.observation.groundMeasurements) {
                this.observation.groundMeasurements = [];
            }
            this.observation.groundMeasurements.push(newMeasurement);
        },

        onRemoveMeasurement(measurement) {
            const index = this.observation.groundMeasurements.findIndex(
                (m) => m.id === measurement.id
            );
            if (index > -1) {
                this.observation.groundMeasurements.splice(index, 1);
            }
        },
    },
});

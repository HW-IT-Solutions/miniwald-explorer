import template from './observation-plant-card.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('miniwald-observation-plant-card', {
    template,

    inject: ['repositoryFactory'],

    mixins: [Mixin.getByName('notification')],

    props: {
        observation: {
            type: Object,
            required: true,
        },
    },

    computed: {
        plantMeasurementRepository() {
            return this.repositoryFactory.create(
                'miniwald_explorer_plant_measurement'
            );
        },

        measurements() {
            return this.observation.plantMeasurements || [];
        },

        plantCriteria() {
            const criteria = new Criteria(1, 500);
            criteria.addSorting(Criteria.sort('name', 'ASC'));
            if (this.observation.miniwaldId) {
                criteria.addFilter(
                    Criteria.equals('miniwaldId', this.observation.miniwaldId)
                );
            }
            return criteria;
        },
    },

    methods: {
        onAddMeasurement() {
            const newMeasurement = this.plantMeasurementRepository.create(
                Shopware.Context.api
            );
            newMeasurement.observationId = this.observation.id;

            if (!this.observation.plantMeasurements) {
                this.observation.plantMeasurements = [];
            }
            this.observation.plantMeasurements.push(newMeasurement);
        },

        onRemoveMeasurement(measurement) {
            const index = this.observation.plantMeasurements.findIndex(
                (m) => m.id === measurement.id
            );
            if (index > -1) {
                this.observation.plantMeasurements.splice(index, 1);
            }
        },

        isDuplicate(measurement) {
            if (!measurement.plantId) {
                return false;
            }
            return this.measurements.some((other) => {
                if (other.id === measurement.id) {
                    return false;
                }
                return other.plantId === measurement.plantId;
            });
        },

        onPlantChange(measurement, newPlantId) {
            if (
                newPlantId &&
                this.isDuplicate({ ...measurement, plantId: newPlantId })
            ) {
                this.createNotificationWarning({
                    message: this.$tc(
                        'miniwald-explorer.observation.detail.plant.duplicateWarning'
                    ),
                });
                this.$nextTick(() => {
                    measurement.plantId = null;
                });
            }
        },
    },
});

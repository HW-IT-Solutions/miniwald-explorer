import template from './observation-metadata-card.html.twig';

const { Component } = Shopware;

Component.register('miniwald-observation-metadata-card', {
    template,

    inject: ['repositoryFactory'],

    props: {
        observation: {
            type: Object,
            required: true,
        },
    },

    computed: {
        weatherOptions() {
            return [
                {
                    value: 'sunny',
                    label: this.$tc(
                        'miniwald-explorer.observation.detail.weatherSunny'
                    ),
                },
                {
                    value: 'partly_cloudy',
                    label: this.$tc(
                        'miniwald-explorer.observation.detail.weatherPartlyCloudy'
                    ),
                },
                {
                    value: 'cloudy',
                    label: this.$tc(
                        'miniwald-explorer.observation.detail.weatherCloudy'
                    ),
                },
                {
                    value: 'rainy',
                    label: this.$tc(
                        'miniwald-explorer.observation.detail.weatherRainy'
                    ),
                },
            ];
        },

        windOptions() {
            return [
                {
                    value: 'calm',
                    label: this.$tc(
                        'miniwald-explorer.observation.detail.windCalm'
                    ),
                },
                {
                    value: 'light_wind',
                    label: this.$tc(
                        'miniwald-explorer.observation.detail.windLightWind'
                    ),
                },
                {
                    value: 'moderate_wind',
                    label: this.$tc(
                        'miniwald-explorer.observation.detail.windModerateWind'
                    ),
                },
                {
                    value: 'strong_wind',
                    label: this.$tc(
                        'miniwald-explorer.observation.detail.windStrongWind'
                    ),
                },
            ];
        },

        statusOptions() {
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
    },
});

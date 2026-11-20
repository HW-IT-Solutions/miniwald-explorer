import template from './sw-customer-detail.html.twig';

/**
 * Override sw-customer-detail to:
 * - Hide Addresses and Orders tabs
 * - Add Observations tab
 * - Add observationsRoute computed property
 */
Shopware.Component.override('sw-customer-detail', {
    template,

    computed: {
        observationsRoute() {
            return {
                name: 'sw.customer.detail.observations',
                params: { id: this.customerId },
                query: { edit: this.editMode },
            };
        },
    },
});

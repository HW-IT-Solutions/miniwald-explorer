import template from './sw-customer-create.html.twig';

/**
 * Override sw-customer-create for MiniwaldExplorer.
 *
 * Shows only firstName, email, password.
 * Auto-fills all other required fields before saving:
 * - lastName = '-'
 * - salutationId = 'not_specified'
 * - groupId = first available customer group
 * - salesChannelId = first available sales channel
 * - customerNumber = reserved from number range
 * - address: lastName, street, zipcode, city, countryId (Germany), salutationId
 */

const { mapPropertyErrors } = Shopware.Component.getComponentHelper();
const { Criteria } = Shopware.Data;

Shopware.Component.override('sw-customer-create', {
    template,

    inject: [
        'repositoryFactory',
        'numberRangeService',
        'customerValidationService',
    ],

    computed: {
        ...mapPropertyErrors('customer', ['firstName', 'email', 'password']),

        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

        customerGroupRepository() {
            return this.repositoryFactory.create('customer_group');
        },

        countryRepository() {
            return this.repositoryFactory.create('country');
        },
    },

    methods: {
        /**
         * Override createdComponent to auto-fill all non-visible required fields.
         * Calls super first to get the base customer/address objects, then fills in dummies.
         */
        async createdComponent() {
            await this.$super('createdComponent');

            // Fetch required IDs in parallel
            const [salesChannelId, groupId, countryId] = await Promise.all([
                this.getDefaultSalesChannelId(),
                this.getDefaultCustomerGroupId(),
                this.getGermanyCountryId(),
            ]);

            // Auto-fill customer-level fields
            this.customer.lastName = '-';
            if (salesChannelId) {
                this.customer.salesChannelId = salesChannelId;
            }
            if (groupId) {
                this.customer.groupId = groupId;
            }

            // Reserve customer number for the selected sales channel
            if (salesChannelId) {
                try {
                    const response = await this.numberRangeService.reserve(
                        'customer',
                        salesChannelId,
                        true
                    );
                    this.customerNumberPreview = response.number;
                    this.customer.customerNumber = response.number;
                } catch {
                    // non-fatal: number range may not be configured
                }
            }

            // Auto-fill address fields
            if (this.address) {
                this.address.firstName = this.customer.firstName || '-';
                this.address.lastName = '-';
                this.address.street = 'MiniwaldExplorer';
                this.address.zipcode = '00000';
                this.address.city = 'MiniwaldExplorer';
                if (countryId) {
                    this.address.countryId = countryId;
                }
                // salutationId is already set by super's createdComponent (not_specified)
            }
        },

        /**
         * Override onSave to sync address.firstName from customer.firstName before saving.
         */
        async onSave() {
            // Keep address.firstName in sync with what the user typed
            if (this.address && this.customer) {
                this.address.firstName = this.customer.firstName || '-';
            }
            return this.$super('onSave');
        },

        async getDefaultSalesChannelId() {
            const criteria = new Criteria(1, 1);
            criteria.addFilter(Criteria.equals('active', true));
            criteria.addFilter(
                Criteria.equals('typeId', '8a243080f92e4c719546314b577cf82b')
            ); // Storefront type
            try {
                const result =
                    await this.salesChannelRepository.searchIds(criteria);
                if (result?.data?.[0]) {
                    return result.data[0];
                }
                // Fallback: any sales channel
                const fallback = await this.salesChannelRepository.searchIds(
                    new Criteria(1, 1)
                );
                return fallback?.data?.[0] ?? null;
            } catch {
                return null;
            }
        },

        async getDefaultCustomerGroupId() {
            try {
                const result = await this.customerGroupRepository.searchIds(
                    new Criteria(1, 1)
                );
                return result?.data?.[0] ?? null;
            } catch {
                return null;
            }
        },

        async getGermanyCountryId() {
            const criteria = new Criteria(1, 1);
            criteria.addFilter(Criteria.equals('iso', 'DE'));
            try {
                const result = await this.countryRepository.searchIds(criteria);
                return result?.data?.[0] ?? null;
            } catch {
                return null;
            }
        },
    },
});

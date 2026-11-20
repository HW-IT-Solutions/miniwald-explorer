import template from './sw-customer-list.html.twig';

/**
 * Override sw-customer-list to simplify columns for MiniwaldExplorer.
 *
 * Changes:
 * - Name column shows only firstName (not "lastName, firstName")
 * - Removed columns: street, zip, city, customerNumber, group, affiliateCode, campaignCode, company, boundSalesChannelId
 * - Kept columns: firstName (Name), email, active (visible), createdAt
 */
Shopware.Component.override('sw-customer-list', {
    template,

    methods: {
        getCustomerColumns() {
            return [
                {
                    property: 'firstName',
                    dataIndex: 'firstName',
                    label: 'sw-customer.list.columnName',
                    routerLink: 'sw.customer.detail',
                    width: '250px',
                    allowResize: true,
                    primary: true,
                    useCustomSort: true,
                },
                {
                    property: 'email',
                    inlineEdit: 'string',
                    label: 'sw-customer.list.columnEmail',
                    allowResize: true,
                    useCustomSort: true,
                },
                {
                    property: 'active',
                    inlineEdit: 'boolean',
                    label: 'sw-customer.list.columnActive',
                    allowResize: true,
                    visible: true,
                    useCustomSort: true,
                },
                {
                    property: 'createdAt',
                    label: 'sw-customer.list.columnCreatedAt',
                    allowResize: true,
                },
            ];
        },
    },
});

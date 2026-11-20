import template from './sw-customer-base-info.html.twig';

/**
 * Override sw-customer-base-info to remove e-commerce fields:
 * - Customer group
 * - Customer number
 * - Affiliate code
 * - Campaign code
 * - Turnover
 * - Birthday
 * - Orders count
 */
Shopware.Component.override('sw-customer-base-info', {
    template,
});

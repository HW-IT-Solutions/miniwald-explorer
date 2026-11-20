import template from './sw-customer-card.html.twig';

/**
 * Override sw-customer-card to remove last name:
 */
Shopware.Component.override('sw-customer-card', {
    template,
});

/**
 * Shopware sw-customer module overrides for MiniwaldExplorer.
 *
 * Customizes the customer list and detail pages to fit the
 * Citizen Science context (observers instead of e-commerce customers).
 */

// Customer create: simplified form (firstName, email, password only)
import './page/sw-customer-create';

// Customer list: simplified columns, firstName-only name display
import './page/sw-customer-list';

// Customer detail: hide Addresses/Orders tabs, add Observations tab
import './page/sw-customer-detail';

// Customer detail base view: remove addresses card, add observation counts
import './view/sw-customer-detail-base';

// Customer base info: remove e-commerce fields
import './component/sw-customer-base-info';
// Customer card: remove last name
import './component/sw-customer-card';

// Customer detail observations view: NEW component (not an override)
import './view/sw-customer-detail-observations';

/**
 * Register the observations child route on the sw-customer module
 * using routeMiddleware to inject into the existing route tree.
 */
const { Module } = Shopware;

Module.register('miniwald-customer-extension', {
    type: 'plugin',
    name: 'miniwald-customer-extension',

    routeMiddleware(next, currentRoute) {
        if (currentRoute.name === 'sw.customer.detail') {
            // Ensure children array exists
            if (!currentRoute.children) {
                currentRoute.children = [];
            }

            // Only add if not already present
            const exists = currentRoute.children.some(
                (child) => child.name === 'sw.customer.detail.observations'
            );

            if (!exists) {
                currentRoute.children.push({
                    name: 'sw.customer.detail.observations',
                    component: 'sw-customer-detail-observations',
                    path: 'observations',
                    meta: {
                        parentPath: 'sw.customer.index',
                        privilege: 'customer.viewer',
                    },
                });
            }
        }

        next(currentRoute);
    },
});

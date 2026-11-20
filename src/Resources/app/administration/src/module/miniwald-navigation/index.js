/**
 * Navigation shell module for MiniwaldExplorer.
 *
 * Shopware's module factory forbids top-level navigation entries for modules
 * with type: 'plugin' (see module.factory.ts line 294). This separate module
 * is registered as type: 'core' with a routeMiddleware (no-op) so that it
 * passes validation and can hold the top-level parent navigation entries.
 *
 * The actual plugin module ('miniwald-explorer') then references these parent
 * IDs in its child navigation entries.
 */

Shopware.Module.register('miniwald-navigation', {
    type: 'core',
    name: 'miniwald-navigation',
    title: 'miniwald-explorer.general.mainMenuItemGeneral',
    color: '#4CAF50',
    icon: 'regular-search',

    // routeMiddleware satisfies the "must have routes or routeMiddleware" check
    // without needing actual route definitions
    routeMiddleware(next) {
        next();
    },

    navigation: [
        // Positions 41-43: after Benutzer (40), before Content (50) / Extensions (60) / Settings (80)
        // Observations Section - Top Level
        {
            id: 'miniwald-explorer-observations',
            label: 'miniwald-explorer.navigation.observations',
            color: '#4CAF50',
            icon: 'regular-file-text',
            position: 41,
        },
        // Species / Entities Section - Top Level
        {
            id: 'miniwald-explorer-entities',
            label: 'miniwald-explorer.navigation.speciesNavigation',
            color: '#4CAF50',
            icon: 'regular-flask',
            position: 42,
        },
        // Data Export Section - Top Level
        {
            id: 'miniwald-explorer-export',
            label: 'miniwald-explorer.navigation.dataExport',
            color: '#4CAF50',
            icon: 'regular-circle-download',
            position: 43,
        },
    ],
});

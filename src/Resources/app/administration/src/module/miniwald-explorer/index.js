import './page/species-category-list';
import './page/species-category-detail';
import './page/species-list';
import './page/species-detail';
import './page/subspecies-list';
import './page/subspecies-detail';
import './page/plant-list';
import './page/plant-detail';
import './page/miniwald-list';
import './page/miniwald-detail';
import './page/observation-list';
import './page/observation-detail';
import './component/observation-metadata-card';
import './component/observation-animal-card';
import './component/observation-plant-card';
import './component/observation-ground-card';
import './page/export-csv';
import './page/export-json';
import './page/settings-pdf';

Shopware.Module.register('miniwald-explorer', {
    type: 'plugin',
    name: 'miniwald-explorer',
    title: 'miniwald-explorer.general.mainMenuItemGeneral',
    description: 'miniwald-explorer.general.description',
    color: '#4CAF50',
    icon: 'regular-search',

    routes: {
        // Species Category routes
        'species.category.list': {
            component: 'miniwald-species-category-list',
            path: 'species-category/list',
        },
        'species.category.detail': {
            component: 'miniwald-species-category-detail',
            path: 'species-category/detail/:id?',
            meta: {
                parentPath: 'miniwald.explorer.species.category.list',
            },
        },

        // Species routes
        'species.list': {
            component: 'miniwald-species-list',
            path: 'species/list',
        },
        'species.detail': {
            component: 'miniwald-species-detail',
            path: 'species/detail/:id?',
            meta: {
                parentPath: 'miniwald.explorer.species.list',
            },
        },

        // Subspecies routes
        'subspecies.list': {
            component: 'miniwald-subspecies-list',
            path: 'subspecies/list',
        },
        'subspecies.detail': {
            component: 'miniwald-subspecies-detail',
            path: 'subspecies/detail/:id?',
            meta: {
                parentPath: 'miniwald.explorer.subspecies.list',
            },
        },

        // Plant routes
        'plant.list': {
            component: 'miniwald-plant-list',
            path: 'plant/list',
        },
        'plant.detail': {
            component: 'miniwald-plant-detail',
            path: 'plant/detail/:id?',
            meta: {
                parentPath: 'miniwald.explorer.plant.list',
            },
        },

        // Miniwald routes
        'miniwald.list': {
            component: 'miniwald-miniwald-list',
            path: 'miniwald/list',
        },
        'miniwald.detail': {
            component: 'miniwald-miniwald-detail',
            path: 'miniwald/detail/:id?',
            meta: {
                parentPath: 'miniwald.explorer.miniwald.list',
            },
        },

        // Observation routes
        'observation.list': {
            component: 'miniwald-observation-list',
            path: 'observation/list',
        },
        'observation.detail': {
            component: 'miniwald-observation-detail',
            path: 'observation/detail/:id?',
            meta: {
                parentPath: 'miniwald.explorer.observation.list',
            },
            props: {
                default(route) {
                    return {
                        observationId: route.params.id,
                    };
                },
            },
        },

        // Export routes
        'export.csv': {
            component: 'miniwald-export-csv',
            path: 'export/csv',
        },
        'export.json': {
            component: 'miniwald-export-json',
            path: 'export/json',
        },

        // Settings routes
        'settings.pdf': {
            component: 'miniwald-settings-pdf',
            path: 'settings/pdf',
        },
    },

    navigation: [
        // Child entries under top-level parents defined in miniwald-navigation module.
        // Plugin-type modules MUST have a 'parent' on every navigation entry
        // (Shopware module.factory.ts filters out parentless entries for type:'plugin').
        {
            id: 'miniwald-species-category',
            label: 'miniwald-explorer.navigation.speciesCategories',
            parent: 'miniwald-explorer-entities',
            path: 'miniwald.explorer.species.category.list',
            position: 10,
        },
        {
            id: 'miniwald-species',
            label: 'miniwald-explorer.navigation.species',
            parent: 'miniwald-explorer-entities',
            path: 'miniwald.explorer.species.list',
            position: 20,
        },
        {
            id: 'miniwald-subspecies',
            label: 'miniwald-explorer.navigation.subspecies',
            parent: 'miniwald-explorer-entities',
            path: 'miniwald.explorer.subspecies.list',
            position: 30,
        },
        {
            id: 'miniwald-plants',
            label: 'miniwald-explorer.navigation.plants',
            parent: 'miniwald-explorer-entities',
            path: 'miniwald.explorer.plant.list',
            position: 40,
        },
        {
            id: 'miniwald-locations',
            label: 'miniwald-explorer.navigation.miniwalder',
            parent: 'miniwald-explorer-entities',
            path: 'miniwald.explorer.miniwald.list',
            position: 50,
        },
        {
            id: 'miniwald-observations-list',
            label: 'miniwald-explorer.navigation.observations',
            parent: 'miniwald-explorer-observations',
            path: 'miniwald.explorer.observation.list',
            position: 10,
        },
        {
            id: 'miniwald-export-csv',
            label: 'miniwald-explorer.navigation.csvExport',
            parent: 'miniwald-explorer-export',
            path: 'miniwald.explorer.export.csv',
            position: 10,
        },
        {
            id: 'miniwald-export-json',
            label: 'miniwald-explorer.navigation.jsonExport',
            parent: 'miniwald-explorer-export',
            path: 'miniwald.explorer.export.json',
            position: 20,
        },
        {
            id: 'miniwald-settings-pdf',
            label: 'miniwald-explorer.navigation.pdfSheets',
            parent: 'miniwald-explorer-observations',
            path: 'miniwald.explorer.settings.pdf',
            position: 20,
        },
    ],
});

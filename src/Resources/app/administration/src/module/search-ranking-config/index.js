/**
 * Register search ranking fields for custom MiniwaldExplorer entities.
 * This tells Shopware which fields to search when users type in the global search bar.
 */

const { Application } = Shopware;

Application.addServiceProviderDecorator(
    'searchRankingService',
    (searchRankingService) => {
        const originalGetSearchFieldsByEntity =
            searchRankingService.getSearchFieldsByEntity;

        searchRankingService.getSearchFieldsByEntity = function (entityName) {
            // Define searchable fields for custom entities
            // Note: scientificName is NOT a translated field - it exists only on the main entity
            const customEntitySearchFields = {
                miniwald_explorer_species_category: {
                    'translations.name': 500, // Only name is translated
                },
                miniwald_explorer_species: {
                    'translations.name': 500, // Translated name field
                    scientificName: 400, // Direct field on main entity (not translated)
                },
                miniwald_explorer_subspecies: {
                    'translations.name': 500, // Translated name field
                    scientificName: 400, // Direct field on main entity (not translated)
                },
                miniwald_explorer_plant: {
                    'translations.name': 500, // Translated name field
                    scientificName: 400, // Direct field on main entity (not translated)
                },
            };

            // Return custom search fields if entity is one of ours
            if (customEntitySearchFields[entityName]) {
                return Promise.resolve(customEntitySearchFields[entityName]);
            }

            // Otherwise, call the original method
            return originalGetSearchFieldsByEntity.call(this, entityName);
        };

        return searchRankingService;
    }
);

/**
 * Override sw-search-bar-item to provide detail routes and display values
 * for custom MiniwaldExplorer entities.
 */

Shopware.Component.override('sw-search-bar-item', {
    computed: {
        detailRoute() {
            // Define detail routes for custom entities
            const customEntityRoutes = {
                miniwald_explorer_species_category:
                    'miniwald.explorer.species.category.detail',
                miniwald_explorer_species: 'miniwald.explorer.species.detail',
                miniwald_explorer_subspecies:
                    'miniwald.explorer.subspecies.detail',
                miniwald_explorer_plant: 'miniwald.explorer.plant.detail',
            };

            // Return custom route if this is one of our entities
            if (customEntityRoutes[this.type]) {
                return customEntityRoutes[this.type];
            }

            // Otherwise use the default behavior
            return this.$super('detailRoute');
        },

        displayValue() {
            // For custom entities with translations, use translated.name
            const customEntities = [
                'miniwald_explorer_species_category',
                'miniwald_explorer_species',
                'miniwald_explorer_subspecies',
                'miniwald_explorer_plant',
            ];

            if (customEntities.includes(this.type)) {
                // Try translated.name first (for translated entities)
                if (this.item.translated && this.item.translated.name) {
                    return this.item.translated.name;
                }
                // Fall back to direct name property
                if (this.item.name) {
                    return this.item.name;
                }
                // Last resort: use ID
                return this.item.id;
            }

            // Otherwise use the default behavior
            return this.$super('displayValue');
        },
    },
});

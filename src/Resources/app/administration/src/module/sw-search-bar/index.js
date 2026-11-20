/**
 * Customize the admin search bar entity type list:
 * - Remove e-commerce entities: product, order, promotion
 * - Add custom MiniwaldExplorer entities at the top of the list
 * - The "customer" label is already overridden via global.entities.customer snippet key
 *
 * Strategy: override createdComponent() to reorder the searchTypeService types
 * so custom entities appear first in the dropdown.
 */
Shopware.Component.override('sw-search-bar', {
    methods: {
        async createdComponent() {
            // Get reference to the current type store
            const typeStore = this.searchTypeService.getTypes();

            // Save all existing types (except the ones we want to remove)
            const existingTypes = {};
            Object.entries(typeStore).forEach(([name, config]) => {
                if (!['product', 'order', 'promotion'].includes(name)) {
                    existingTypes[name] = { ...config };
                }
            });

            // Remove ALL types from the store
            Object.keys(typeStore).forEach((name) => {
                this.searchTypeService.removeType(name);
            });

            // Define custom MiniwaldExplorer entity types
            // Labels come from global.entities.* snippet keys in de-DE.json / en-GB.json.
            const customTypes = {
                miniwald_explorer_species_category: {
                    entityName: 'miniwald_explorer_species_category',
                    placeholderSnippet:
                        'global.sw-search-bar.placeholderSearchField',
                    listingRoute: 'miniwald.explorer.species.category.list',
                },
                miniwald_explorer_species: {
                    entityName: 'miniwald_explorer_species',
                    placeholderSnippet:
                        'global.sw-search-bar.placeholderSearchField',
                    listingRoute: 'miniwald.explorer.species.list',
                },
                miniwald_explorer_subspecies: {
                    entityName: 'miniwald_explorer_subspecies',
                    placeholderSnippet:
                        'global.sw-search-bar.placeholderSearchField',
                    listingRoute: 'miniwald.explorer.subspecies.list',
                },
                miniwald_explorer_plant: {
                    entityName: 'miniwald_explorer_plant',
                    placeholderSnippet:
                        'global.sw-search-bar.placeholderSearchField',
                    listingRoute: 'miniwald.explorer.plant.list',
                },
            };

            // Re-add types in desired order: custom types first, then Shopware types
            Object.entries(customTypes).forEach(([name, config]) => {
                this.searchTypeService.upsertType(name, config);
            });

            Object.entries(existingTypes).forEach(([name, config]) => {
                this.searchTypeService.upsertType(name, config);
            });

            // Now call the original createdComponent — it will snapshot the reordered types
            await this.$super('createdComponent');
        },

        /**
         * Override to ensure custom entities load with proper associations for display
         */
        async loadTypeSearchResults(searchTerm) {
            console.warn('loadTypeSearchResults');
            // Check if this is one of our custom entities that needs special handling
            const customEntityNames = [
                'miniwald_explorer_species_category',
                'miniwald_explorer_species',
                'miniwald_explorer_subspecies',
                'miniwald_explorer_plant',
            ];

            if (!customEntityNames.includes(this.currentSearchType)) {
                // Not our entity, use default behavior
                return this.$super('loadTypeSearchResults', searchTerm);
            }

            // Custom handling for our entities to ensure translations are loaded
            this.isLoading = true;
            this.results = [];
            const entityResults = {
                entity: this.currentSearchType,
                total: 0,
            };

            const entityName =
                this.searchTypes[this.currentSearchType].entityName;
            const repository = this.repositoryFactory.create(entityName);

            let criteria = Object.prototype.hasOwnProperty.call(
                this.criteriaCollection,
                entityName
            )
                ? this.criteriaCollection[entityName]
                : new Shopware.Data.Criteria(1, this.searchLimit + 1);

            criteria.setTerm(searchTerm);
            criteria.setLimit(this.searchLimit + 1);
            criteria.setTotalCountMode(0);

            const searchRankingFields =
                await this.searchRankingService.getSearchFieldsByEntity(
                    entityName
                );
            if (
                !searchRankingFields ||
                Object.keys(searchRankingFields).length < 1
            ) {
                entityResults.total = 0;
                this.results.push(entityResults);
                this.isLoading = false;
                if (!this.showTypeSelectContainer) {
                    this.showResultsContainer = true;
                }
                return;
            }

            criteria = this.searchRankingService.buildSearchQueriesForEntity(
                searchRankingFields,
                searchTerm,
                criteria
            );

            const response = await repository.search(criteria, {
                ...Shopware.Context.api,
                inheritance: true,
            });

            entityResults.total = response.total;
            entityResults.entities = response.slice(0, this.searchLimit);

            if (entityResults.total > 0) {
                console.warn('results found...');
                this.results = this.results.filter(
                    (result) => this.currentSearchType !== result.entity
                );
                this.results = [...this.results, entityResults];
            }

            this.isLoading = false;

            if (!this.showTypeSelectContainer) {
                this.showResultsContainer = true;
            }
        },
    },
});

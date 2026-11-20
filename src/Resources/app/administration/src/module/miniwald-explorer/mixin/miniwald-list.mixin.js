/**
 * Reusable mixin for Miniwald Explorer entity list pages.
 *
 * Usage:
 *   import { miniwaldListMixin } from '../../mixin/miniwald-list.mixin';
 *
 *   Component.register('miniwald-species-list', {
 *       template,
 *       mixins: [
 *           Mixin.getByName('listing'),
 *           miniwaldListMixin({
 *               entityName: 'miniwald_explorer_species',
 *               defaultSortField: 'position',
 *               defaultSortDirection: 'ASC',
 *               associations: ['image', 'speciesCategory'],
 *           })
 *       ],
 *       computed: {
 *           columns() { return [...]; }  // entity-specific columns
 *       }
 *   });
 */

const { Criteria } = Shopware.Data;

export function miniwaldListMixin(config) {
    const {
        entityName,
        defaultSortField = 'position',
        defaultSortDirection = 'ASC',
        associations = ['image'],
    } = config;

    return {
        inject: ['repositoryFactory'],

        data() {
            return {
                items: null,
                isLoading: false,
                total: 0,
            };
        },

        computed: {
            entityRepository() {
                return this.repositoryFactory.create(entityName);
            },
        },

        created() {
            this.getList();
        },

        methods: {
            async getList() {
                this.isLoading = true;

                const criteria = new Criteria(this.page, this.limit);
                criteria.addSorting(
                    Criteria.sort(defaultSortField, defaultSortDirection)
                );

                associations.forEach((assoc) => {
                    criteria.addAssociation(assoc);
                });

                // Apply search term if provided (from listing mixin's 'term' property)
                if (this.term) {
                    criteria.setTerm(this.term);
                }

                this.entityRepository
                    .search(criteria, Shopware.Context.api)
                    .then((result) => {
                        this.items = result;
                        this.total = result.total;
                        this.isLoading = false;
                    })
                    .catch(() => {
                        this.isLoading = false;
                    });
            },

            onChangeLanguage() {
                this.getList();
            },

            onInlineEditSave(promise) {
                promise.then(() => {
                    this.getList();
                });
            },
        },
    };
}

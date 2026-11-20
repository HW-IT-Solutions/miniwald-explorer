import template from './species-category-list.html.twig';
import { miniwaldListMixin } from '../../mixin/miniwald-list.mixin';

const { Component, Mixin } = Shopware;

Component.register('miniwald-species-category-list', {
    template,

    mixins: [
        Mixin.getByName('listing'),
        miniwaldListMixin({
            entityName: 'miniwald_explorer_species_category',
            defaultSortField: 'position',
            defaultSortDirection: 'ASC',
            associations: ['image'],
        }),
    ],

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        columns() {
            return [
                {
                    property: 'image',
                    label: this.$tc(
                        'miniwald-explorer.speciesCategory.list.columnImage'
                    ),
                    allowResize: false,
                    sortable: false,
                    width: '72px',
                    align: 'center',
                },
                {
                    property: 'name',
                    dataIndex: 'name',
                    label: this.$tc(
                        'miniwald-explorer.speciesCategory.list.columnName'
                    ),
                    routerLink: 'miniwald.explorer.species.category.detail',
                    inlineEdit: 'string',
                    allowResize: true,
                    primary: true,
                },
                {
                    property: 'position',
                    dataIndex: 'position',
                    label: this.$tc(
                        'miniwald-explorer.speciesCategory.list.columnPosition'
                    ),
                    inlineEdit: 'number',
                    allowResize: true,
                },
                {
                    property: 'active',
                    dataIndex: 'active',
                    label: this.$tc(
                        'miniwald-explorer.speciesCategory.list.columnActive'
                    ),
                    inlineEdit: 'boolean',
                    allowResize: true,
                    align: 'center',
                },
            ];
        },
    },
});

import template from './species-list.html.twig';
import { miniwaldListMixin } from '../../mixin/miniwald-list.mixin';

const { Component, Mixin } = Shopware;

Component.register('miniwald-species-list', {
    template,

    mixins: [
        Mixin.getByName('listing'),
        miniwaldListMixin({
            entityName: 'miniwald_explorer_species',
            defaultSortField: 'position',
            defaultSortDirection: 'ASC',
            associations: ['image', 'speciesCategory'],
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
                        'miniwald-explorer.species.list.columnImage'
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
                        'miniwald-explorer.species.list.columnName'
                    ),
                    routerLink: 'miniwald.explorer.species.detail',
                    inlineEdit: 'string',
                    allowResize: true,
                    primary: true,
                },
                {
                    property: 'scientificName',
                    dataIndex: 'scientificName',
                    label: this.$tc(
                        'miniwald-explorer.species.list.columnScientificName'
                    ),
                    inlineEdit: 'string',
                    allowResize: true,
                },
                {
                    property: 'speciesCategory.name',
                    label: this.$tc(
                        'miniwald-explorer.species.list.columnCategory'
                    ),
                    allowResize: true,
                    sortable: false,
                },
                {
                    property: 'position',
                    dataIndex: 'position',
                    label: this.$tc(
                        'miniwald-explorer.species.list.columnPosition'
                    ),
                    inlineEdit: 'number',
                    allowResize: true,
                },
                {
                    property: 'active',
                    dataIndex: 'active',
                    label: this.$tc(
                        'miniwald-explorer.species.list.columnActive'
                    ),
                    inlineEdit: 'boolean',
                    allowResize: true,
                    align: 'center',
                },
            ];
        },
    },
});

import template from './plant-list.html.twig';
import { miniwaldListMixin } from '../../mixin/miniwald-list.mixin';

const { Component, Mixin } = Shopware;

Component.register('miniwald-plant-list', {
    template,

    mixins: [
        Mixin.getByName('listing'),
        miniwaldListMixin({
            entityName: 'miniwald_explorer_plant',
            defaultSortField: 'sortOrder',
            defaultSortDirection: 'ASC',
            associations: ['image', 'miniwald'],
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
                    label: this.$tc('miniwald-explorer.plant.list.columnImage'),
                    sortable: false,
                    allowResize: true,
                    width: '90px',
                },
                {
                    property: 'name',
                    label: this.$tc('miniwald-explorer.plant.list.columnName'),
                    routerLink: 'miniwald.explorer.plant.detail',
                    inlineEdit: 'string',
                    allowResize: true,
                    primary: true,
                },
                {
                    property: 'scientificName',
                    label: this.$tc(
                        'miniwald-explorer.plant.list.columnScientificName'
                    ),
                    inlineEdit: 'string',
                    allowResize: true,
                },
                {
                    property: 'miniwald.name',
                    label: this.$tc(
                        'miniwald-explorer.plant.list.columnMiniwald'
                    ),
                    sortable: false,
                    allowResize: true,
                },
                {
                    property: 'position',
                    label: this.$tc(
                        'miniwald-explorer.plant.list.columnPosition'
                    ),
                    inlineEdit: 'string',
                    allowResize: true,
                    width: '120px',
                },
                {
                    property: 'sortOrder',
                    label: this.$tc(
                        'miniwald-explorer.plant.list.columnSortOrder'
                    ),
                    inlineEdit: 'number',
                    allowResize: true,
                    width: '120px',
                },
                {
                    property: 'active',
                    label: this.$tc(
                        'miniwald-explorer.plant.list.columnActive'
                    ),
                    inlineEdit: 'boolean',
                    allowResize: true,
                    align: 'center',
                    width: '90px',
                },
            ];
        },
    },
});

import template from './subspecies-list.html.twig';
import { miniwaldListMixin } from '../../mixin/miniwald-list.mixin';

const { Component, Mixin } = Shopware;

Component.register('miniwald-subspecies-list', {
    template,

    mixins: [
        Mixin.getByName('listing'),
        miniwaldListMixin({
            entityName: 'miniwald_explorer_subspecies',
            defaultSortField: 'position',
            defaultSortDirection: 'ASC',
            associations: ['image', 'species'],
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
                        'miniwald-explorer.subspecies.list.columnImage'
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
                        'miniwald-explorer.subspecies.list.columnName'
                    ),
                    routerLink: 'miniwald.explorer.subspecies.detail',
                    inlineEdit: 'string',
                    allowResize: true,
                    primary: true,
                },
                {
                    property: 'scientificName',
                    dataIndex: 'scientificName',
                    label: this.$tc(
                        'miniwald-explorer.subspecies.list.columnScientificName'
                    ),
                    inlineEdit: 'string',
                    allowResize: true,
                },
                {
                    property: 'species.name',
                    label: this.$tc(
                        'miniwald-explorer.subspecies.list.columnSpecies'
                    ),
                    allowResize: true,
                    sortable: false,
                },
                {
                    property: 'position',
                    dataIndex: 'position',
                    label: this.$tc(
                        'miniwald-explorer.subspecies.list.columnPosition'
                    ),
                    inlineEdit: 'number',
                    allowResize: true,
                },
                {
                    property: 'active',
                    dataIndex: 'active',
                    label: this.$tc(
                        'miniwald-explorer.subspecies.list.columnActive'
                    ),
                    inlineEdit: 'boolean',
                    allowResize: true,
                    align: 'center',
                },
            ];
        },
    },
});

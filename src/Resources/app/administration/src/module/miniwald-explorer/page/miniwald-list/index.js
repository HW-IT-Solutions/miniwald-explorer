import template from './miniwald-list.html.twig';
import { miniwaldListMixin } from '../../mixin/miniwald-list.mixin';

const { Component, Mixin } = Shopware;

Component.register('miniwald-miniwald-list', {
    template,

    mixins: [
        Mixin.getByName('listing'),
        miniwaldListMixin({
            entityName: 'miniwald_explorer_miniwald',
            defaultSortField: 'name',
            defaultSortDirection: 'ASC',
            associations: [],
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
                    property: 'name',
                    dataIndex: 'name',
                    label: this.$tc(
                        'miniwald-explorer.miniwald.list.columnName'
                    ),
                    routerLink: 'miniwald.explorer.miniwald.detail',
                    inlineEdit: 'string',
                    allowResize: true,
                    primary: true,
                },
                {
                    property: 'latitude',
                    dataIndex: 'latitude',
                    label: this.$tc(
                        'miniwald-explorer.miniwald.list.columnLatitude'
                    ),
                    allowResize: true,
                    align: 'right',
                },
                {
                    property: 'longitude',
                    dataIndex: 'longitude',
                    label: this.$tc(
                        'miniwald-explorer.miniwald.list.columnLongitude'
                    ),
                    allowResize: true,
                    align: 'right',
                },
                {
                    property: 'radiusMeters',
                    dataIndex: 'radiusMeters',
                    label: this.$tc(
                        'miniwald-explorer.miniwald.list.columnRadius'
                    ),
                    allowResize: true,
                    align: 'right',
                },
            ];
        },
    },
});
